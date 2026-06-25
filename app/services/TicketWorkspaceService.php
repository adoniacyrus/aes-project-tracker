<?php

/**
 * Ticket workspace layout, AJAX refresh groups, and extension hooks.
 * Future notification/reporting integrations should hook into runAfterAction().
 */
class TicketWorkspaceService
{
    private const PARTIAL_TARGETS = [
        'ticket-info' => ['partial' => 'ticket-info', 'target' => '#ticket-information'],
        'workflow' => ['partial' => 'workflow', 'target' => '#ticket-workflow'],
        'assigned-team' => ['partial' => 'assigned-team', 'target' => '#ticket-assigned-team'],
        'estimation' => ['partial' => 'estimation', 'target' => '#ticket-cost-estimation'],
        'assignment' => ['partial' => 'assignment', 'target' => '#ticket-developer-assignment'],
        'review-comment' => ['partial' => 'review-comment', 'target' => '#ticket-latest-review-comment'],
        'workflow-history' => ['partial' => 'workflow-history', 'target' => '#ticket-workflow-history'],
        'tasks' => ['partial' => 'dynamic', 'target' => '#ticket-dynamic-content'],
        'attachments' => ['partial' => 'attachments', 'target' => '#ticket-attachments'],
    ];

    private const PARTIAL_ALIASES = [
        'sidebar' => ['workflow', 'assigned-team'],
    ];

    private const ACTION_GROUPS = [
        'assign_team' => [
            'partials' => ['assignment', 'workflow', 'assigned-team', 'workflow-history'],
            'chat_polls' => ['admin_dev' => true],
        ],
        'cost_updated' => [
            'partials' => ['estimation', 'workflow-history'],
            'chat_polls' => ['client' => true],
        ],
        'review_submitted' => [
            'partials' => ['workflow', 'assigned-team', 'workflow-history', 'review-comment'],
            'chat_polls' => ['admin_dev' => true],
        ],
        'review_approved' => [
            'partials' => ['workflow', 'assigned-team', 'workflow-history'],
            'chat_polls' => ['admin_dev' => true],
            'refresh_dashboard' => true,
        ],
        'return_development' => [
            'partials' => ['workflow', 'assigned-team', 'workflow-history', 'review-comment'],
            'chat_polls' => ['admin_dev' => true],
        ],
        'workflow_status' => [
            'partials' => ['workflow', 'workflow-history'],
            'refresh_dashboard' => true,
        ],
        'reclassify' => [
            'partials' => ['workflow', 'assignment', 'assigned-team', 'workflow-history', 'tasks'],
        ],
        'commercial_review' => [
            'partials' => ['workflow-history'],
        ],
        'ticket_updated' => [
            'partials' => ['ticket-info', 'workflow', 'tasks'],
        ],
        'attachment_updated' => [
            'partials' => ['attachments'],
        ],
        'default' => [
            'partials' => ['workflow', 'assigned-team', 'workflow-history'],
        ],
    ];

    public static function expandPartials(array $partials)
    {
        $expanded = [];
        foreach ($partials as $partial) {
            if (isset(self::PARTIAL_ALIASES[$partial])) {
                foreach (self::PARTIAL_ALIASES[$partial] as $child) {
                    $expanded[] = $child;
                }
                continue;
            }
            $expanded[] = $partial;
        }

        return array_values(array_unique($expanded));
    }

    public static function buildPartialRefreshes($ticketId, array $partials)
    {
        $partials = self::expandPartials($partials);
        $refreshes = [];
        $seen = [];

        foreach ($partials as $partial) {
            if (!isset(self::PARTIAL_TARGETS[$partial])) {
                continue;
            }
            $config = self::PARTIAL_TARGETS[$partial];
            if (isset($seen[$config['target']])) {
                continue;
            }
            $seen[$config['target']] = true;
            $refreshes[] = [
                'url' => route('tickets-view', ['id' => (int)$ticketId, 'partial' => $config['partial']]),
                'target' => $config['target'],
            ];
        }

        return $refreshes;
    }

    public static function buildAjaxPayload($ticketId, $action, $message = '', array $extra = [])
    {
        $group = self::ACTION_GROUPS[$action] ?? self::ACTION_GROUPS['default'];
        $payload = [
            'success' => true,
            'message' => $message,
            'refreshes' => self::buildPartialRefreshes($ticketId, $group['partials'] ?? []),
        ];

        foreach ($group['chat_polls'] ?? [] as $channel => $enabled) {
            if (!$enabled) {
                continue;
            }
            if ($channel === 'client') {
                $payload['client_chat_poll'] = true;
            }
            if ($channel === 'admin_dev') {
                $payload['admin_dev_chat_poll'] = true;
            }
        }

        if (!empty($group['refresh_dashboard'])) {
            $payload['refresh_dashboard_stats'] = true;
        }

        self::runAfterAction($action, (int)$ticketId, $extra);

        return array_merge($payload, $extra);
    }

    /**
     * Extension point for future notifications, reports, etc.
     */
    public static function runAfterAction($action, $ticketId, array $context = [])
    {
        // Reserved for future integrations.
    }
}
