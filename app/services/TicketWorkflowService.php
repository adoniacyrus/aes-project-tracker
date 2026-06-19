<?php

class TicketWorkflowService
{
    /**
     * Get all possible statuses for reference
     */
    public static function getAllStatuses()
    {
        return [
            'Open',
            'Awaiting Admin Approval',
            'Awaiting Payment',
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
     * Get allowed target statuses for a ticket based on its category, current status, and the user's role.
     * Returns an associative array of target_status => label.
     */
    public static function getAllowedTransitions($category, $currentStatus, $userRole)
    {
        // Admin can override and move to any status
        if ($userRole === 'admin') {
            $all = self::getAllStatuses();
            $transitions = [];
            foreach ($all as $status) {
                if ($status !== $currentStatus) {
                    $transitions[$status] = $status;
                }
            }
            return $transitions;
        }

        $transitions = [];

        // 1. "Commercial Review" transition: Developer, Intern, or Client can flag any active ticket
        $activeStatuses = ['Open', 'In Development', 'Reopened', 'On Hold'];
        if (in_array($currentStatus, $activeStatuses)) {
            $transitions['Awaiting Admin Approval'] = 'Flag for Commercial Review';
        }

        // 2. Client transitions
        if ($userRole === 'client') {
            if ($currentStatus === 'Resolved') {
                $transitions['Closed'] = 'Close Ticket (Approve Solution)';
                $transitions['Reopened'] = 'Reopen Ticket (Issue Unresolved)';
            }
            if ($currentStatus === 'Closed') {
                $transitions['Reopened'] = 'Reopen Ticket';
            }
            return $transitions;
        }

        // 3. Developer / Intern transitions
        if ($userRole === 'developer' || $userRole === 'intern') {
            // Reopened can be worked on
            if ($currentStatus === 'Reopened') {
                $transitions['In Development'] = 'Start Work (In Development)';
            }

            // Category-specific workflows
            if ($category === 'Bug Fix' || $category === 'Technical Support') {
                switch ($currentStatus) {
                    case 'Open':
                        $transitions['In Development'] = 'Start Work (In Development)';
                        $transitions['On Hold'] = 'Put On Hold';
                        break;
                    case 'In Development':
                        $transitions['Resolved'] = 'Mark as Resolved';
                        $transitions['On Hold'] = 'Put On Hold';
                        break;
                    case 'On Hold':
                        $transitions['In Development'] = 'Resume Work (In Development)';
                        break;
                    case 'Resolved':
                        // Developers typically don't close, clients or admins do, but they can reopen
                        $transitions['Reopened'] = 'Reopen Ticket';
                        break;
                }
            } elseif ($category === 'New Feature Request' || $category === 'Enhancement Request') {
                // Feature workflow: Open -> Awaiting Admin Approval -> Awaiting Payment -> Approved -> In Development -> Resolved -> Closed
                switch ($currentStatus) {
                    case 'Open':
                        $transitions['Awaiting Admin Approval'] = 'Submit for Admin Approval';
                        break;
                    case 'Approved':
                        $transitions['In Development'] = 'Start Development';
                        break;
                    case 'In Development':
                        $transitions['Resolved'] = 'Mark as Resolved';
                        break;
                }
            }
        }

        return $transitions;
    }

    /**
     * Validate if a specific status transition is allowed
     */
    public static function isValidTransition($category, $currentStatus, $newStatus, $userRole)
    {
        if ($currentStatus === $newStatus) {
            return true;
        }

        $allowed = self::getAllowedTransitions($category, $currentStatus, $userRole);
        return isset($allowed[$newStatus]);
    }
}
