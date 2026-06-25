        <?php if ($userRole !== 'client'): ?>
        <?php
            $canManageTasks = can_manage_tasks($userRole);
            $currentUserId = (int)($_SESSION['user_id'] ?? 0);
            $taskAssignableMembers = filter_ticket_task_assignable_members(
                $ticket,
                $projectMembers ?? [],
                array_column($tasks ?? [], 'assigned_member')
            );
            require __DIR__ . '/_tasks_checklist.php';
        ?>
        <?php endif; ?>
