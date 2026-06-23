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
        return [
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
            'On Hold'
        ];
    }

    /**
     * Initial status and visibility when a ticket is created.
     */
    public static function getInitialWorkflowState($category, $creatorRole)
    {
        if ($category === 'Bug Fix') {
            return [
                'status' => 'Awaiting Admin Approval',
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
     * Get allowed target statuses for a ticket based on category, current status, and role.
     */
    public static function getAllowedTransitions($ticket, $userRole)
    {
        $category = $ticket['category'];
        $currentStatus = $ticket['status'];
        $isAssigned = !empty($ticket['assigned_to']);

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
            if ((int)($ticket['is_team_visible'] ?? 1) === 0) {
                return [];
            }

            if ($currentStatus === 'Reopened' && $isAssigned) {
                $transitions['In Development'] = 'Resume Work (In Development)';
            }

            if ($category === 'Bug Fix') {
                switch ($currentStatus) {
                    case 'Open':
                    case 'Approved':
                        if ($isAssigned) {
                            $transitions['In Development'] = 'Start Work (In Development)';
                        }
                        $transitions['On Hold'] = 'Put On Hold';
                        break;
                    case 'In Development':
                        if ($isAssigned) {
                            $transitions['Resolved'] = 'Mark as Resolved';
                        }
                        $transitions['On Hold'] = 'Put On Hold';
                        break;
                    case 'On Hold':
                        if ($isAssigned) {
                            $transitions['In Development'] = 'Resume Work (In Development)';
                        }
                        break;
                }
            }

            $activeForReview = ['Open', 'In Development', 'Reopened', 'On Hold', 'Approved'];
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
                && (int)($ticket['is_team_visible'] ?? 1) === 1;
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
            'Approved',
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
