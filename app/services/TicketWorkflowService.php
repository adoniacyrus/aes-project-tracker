<?php

class TicketWorkflowService
{
    public static function getCommercialCategories()
    {
        return ['New Feature Request', 'Enhancement Request', 'Technical Support'];
    }

    public static function isCommercialCategory($category)
    {
        return in_array($category, self::getCommercialCategories(), true);
    }

    public static function getAllStatuses()
    {
        return array_merge(self::getSimplifiedStatuses(), [
            'Open',
            'Awaiting Admin Approval',
            'Awaiting Client Review',
            'Awaiting Payment',
            'Payment Confirmed',
            'Approved',
            'In Development',
            'Resolved',
            'Reopened',
            'Closed',
            'Rejected',
            'On Hold',
        ]);
    }

    /**
     * The three simplified workflow statuses shown in the UI.
     */
    public static function getSimplifiedStatuses()
    {
        return ['Initiated', 'Processing', 'Completed'];
    }

    public static function isSimplifiedStatus($status)
    {
        return in_array($status, self::getSimplifiedStatuses(), true);
    }

    /**
     * Map any stored ticket status (legacy or new) to a simplified display status.
     */
    public static function mapToSimplifiedStatus($dbStatus)
    {
        $map = [
            'Initiated' => 'Initiated',
            'Processing' => 'Processing',
            'Completed' => 'Completed',
            'Open' => 'Initiated',
            'Awaiting Admin Approval' => 'Initiated',
            'Awaiting Client Review' => 'Initiated',
            'Awaiting Payment' => 'Initiated',
            'Payment Confirmed' => 'Initiated',
            'Approved' => 'Processing',
            'In Development' => 'Processing',
            'Resolved' => 'Processing',
            'Reopened' => 'Processing',
            'Closed' => 'Completed',
            'Rejected' => 'Initiated',
            'On Hold' => 'Initiated',
        ];

        return $map[$dbStatus] ?? 'Initiated';
    }

    public static function getSimplifiedStatusBadgeClass($displayStatus)
    {
        switch ($displayStatus) {
            case 'Initiated':
                return 'bg-warning text-dark';
            case 'Processing':
                return 'bg-primary text-white';
            case 'Completed':
                return 'bg-success text-white';
            default:
                return 'bg-secondary';
        }
    }

    public static function canAdminChangeSimplifiedStatus($userRole)
    {
        return $userRole === 'admin';
    }

    public static function isValidSimplifiedTransition($ticket, $newStatus, $userRole)
    {
        if (!self::canAdminChangeSimplifiedStatus($userRole)) {
            return false;
        }

        if (!self::isSimplifiedStatus($newStatus)) {
            return false;
        }

        $currentDisplay = self::mapToSimplifiedStatus($ticket['status'] ?? '');

        return $currentDisplay !== $newStatus;
    }

    /**
     * Initial status and visibility when a ticket is created.
     */
    public static function getInitialWorkflowState($category, $creatorRole)
    {
        if ($category === 'Bug Fix') {
            return [
                'status' => 'Open',
                'is_team_visible' => 1,
            ];
        }

        if (self::isCommercialCategory($category)) {
            return [
                'status' => 'Awaiting Admin Approval',
                'is_team_visible' => 0,
            ];
        }

        return [
            'status' => 'Open',
            'is_team_visible' => 1,
        ];
    }

    /**
     * Statuses that unlock ticket visibility for the project team.
     */
    public static function getTeamVisibilityUnlockStatuses()
    {
        return ['Open', 'Payment Confirmed', 'Processing', 'Completed'];
    }

    public static function shouldUnlockTeamVisibility($status)
    {
        return in_array($status, self::getTeamVisibilityUnlockStatuses(), true);
    }

    /**
     * Statuses where developers/interns must not see the ticket.
     */
    public static function getTeamHiddenStatuses()
    {
        return [
            'Initiated',
            'Awaiting Admin Approval',
            'Awaiting Client Review',
            'Awaiting Payment',
            'Rejected',
            'On Hold',
        ];
    }

    /**
     * Whether a ticket is visible to project developers and interns.
     * Visibility is driven by workflow status; pre-approval and pre-payment statuses stay hidden.
     */
    public static function isVisibleToProjectTeam($ticket)
    {
        $status = $ticket['status'] ?? '';
        return !in_array($status, self::getTeamHiddenStatuses(), true);
    }

    /**
     * Whether a workflow status should keep the ticket visible to the project team.
     */
    public static function isTeamVisibleStatus($status)
    {
        return !in_array($status, self::getTeamHiddenStatuses(), true);
    }

    /**
     * Get allowed target statuses for a ticket based on category, current status, and role.
     */
    public static function getAllowedTransitions($ticket, $userRole)
    {
        $category = $ticket['category'];
        $currentStatus = $ticket['status'];

        if ($userRole === 'admin') {
            $transitions = [];
            foreach (self::getAllStatuses() as $status) {
                if ($status !== $currentStatus) {
                    $transitions[$status] = $status;
                }
            }
            return $transitions;
        }

        $transitions = [];

        if ($userRole === 'client') {
            if ($currentStatus === 'Awaiting Client Review') {
                $transitions['Awaiting Payment'] = 'Approve Proposal';
                $transitions['Rejected'] = 'Reject Proposal';
            }
            if ($currentStatus === 'Resolved') {
                $transitions['Closed'] = 'Close Ticket (Approve Solution)';
                $transitions['Reopened'] = 'Reopen Ticket (Issue Unresolved)';
            }
            if ($currentStatus === 'Closed') {
                $transitions['Reopened'] = 'Reopen Ticket';
            }
            return $transitions;
        }

        if ($userRole === 'developer' || $userRole === 'intern') {
            if (!self::isVisibleToProjectTeam($ticket)) {
                return [];
            }

            if ($currentStatus === 'Reopened') {
                $transitions['In Development'] = 'Resume Work (In Development)';
            }

            if ($category === 'Bug Fix') {
                switch ($currentStatus) {
                    case 'Open':
                        $transitions['In Development'] = 'Start Work (In Development)';
                        break;
                    case 'In Development':
                        $transitions['Resolved'] = 'Mark as Resolved';
                        break;
                }
            }

            if (self::isCommercialCategory($category)) {
                switch ($currentStatus) {
                    case 'Payment Confirmed':
                        $transitions['In Development'] = 'Start Work (In Development)';
                        break;
                    case 'In Development':
                        $transitions['Resolved'] = 'Mark as Resolved';
                        break;
                }
            }

            $activeForReview = ['Open', 'In Development', 'Reopened'];
            if (in_array($currentStatus, $activeForReview, true)) {
                if ($category === 'Bug Fix') {
                    $transitions['__commercial_review__'] = 'Request Commercial Review';
                }
            }
        }

        return $transitions;
    }


    public static function isValidTransition($ticket, $newStatus, $userRole)
    {
        if ($ticket['status'] === $newStatus) {
            return true;
        }

        if (str_starts_with($newStatus, '__')) {
            return ($userRole === 'developer' || $userRole === 'intern')
                && self::isVisibleToProjectTeam($ticket);
        }

        $allowed = self::getAllowedTransitions($ticket, $userRole);
        return isset($allowed[$newStatus]);
    }

    /**
     * Ticket statuses where commercial estimated_cost counts toward project revenue.
     */
    public static function getApprovedRevenueStatuses()
    {
        return [
            'Awaiting Payment',
            'Payment Confirmed',
            'In Development',
            'Resolved',
            'Closed',
            'Reopened',
        ];
    }

    public static function canCreateTicket($userRole)
    {
        return in_array($userRole, ['admin', 'client'], true);
    }

    public static function canViewDiscussion($userRole)
    {
        return in_array($userRole, ['admin', 'client'], true);
    }

    public static function canPostDiscussion($userRole)
    {
        return in_array($userRole, ['admin', 'client'], true);
    }
}
