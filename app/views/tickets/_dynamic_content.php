        <?php if ($userRole !== 'client'): ?>
        <?php
            $canManageTasks = can_manage_tasks($userRole);
            $currentUserId = (int)($_SESSION['user_id'] ?? 0);
            $taskAssignableMembers = filter_task_assignable_members($projectMembers);
            require __DIR__ . '/_tasks_checklist.php';
        ?>
        <?php endif; ?>
