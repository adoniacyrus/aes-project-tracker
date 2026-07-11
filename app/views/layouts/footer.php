        </div> <!-- Page Body Container End -->
        
        <!-- Footer -->
        <footer class="footer bg-white border-top mt-auto">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-12 text-center text-md-start">
                        <span class="text-secondary fs-7">&copy; <?php echo date('Y'); ?> <a href="<?php echo route('dashboard'); ?>" class="text-decoration-none text-primary font-weight-medium">AES Project Tracker</a>. All rights reserved.</span>
                    </div>
                </div>
            </div>
        </footer>
    </div> <!-- Page Wrapper End -->
</div> <!-- Main Wrapper End -->

<?php if (isset($ticket['id']) && (can_access_client_chat() || can_access_team_chat() || can_access_admin_dev_chat(null, $ticket))): ?>
    <div id="floating-chat-dock" class="floating-chat-dock">
    <?php if (!empty($showClientChatWidget) && can_access_client_chat()): ?>
        <?php
            $floatingChat = floating_chat_config($ticket, $clientComments ?? [], 'client', 'client');
            require __DIR__ . '/../tickets/_team_chat_widget.php';
        ?>
    <?php endif; ?>
    <?php if (!empty($showAdminDevChatWidget) && can_access_admin_dev_chat(null, $ticket)): ?>
        <?php
            $floatingChat = floating_chat_config($ticket, $adminDevComments ?? [], 'admin_dev', 'admin_dev');
            require __DIR__ . '/../tickets/_team_chat_widget.php';
        ?>
    <?php endif; ?>
    <?php if (!empty($showTeamChatWidget) && can_access_team_chat()): ?>
        <?php
            $floatingChat = floating_chat_config($ticket, $teamComments ?? $comments ?? [], 'team', 'team');
            require __DIR__ . '/../tickets/_team_chat_widget.php';
        ?>
    <?php endif; ?>
    </div>
    <?php require __DIR__ . '/../tickets/_team_chat_attachment_modal.php'; ?>
<?php endif; ?>

<script>
    window.AES_CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;
    window.AES_TASK_STATUS_URL_TEMPLATE = <?php echo json_encode(route('tasks-status', ['id' => '__TASK_ID__'])); ?>;
</script>

<!-- Bootstrap Bundle with Popper JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Mobile Sidebar Actions Toggle & Global AJAX Helpers -->
<script>
    $.ajaxSetup({
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    $(document).on('ajax:content-updated', function(e, target) {
        const el = typeof target === 'string' ? document.querySelector(target) : target;
        initTooltipsIn(el || document);
    });

    // Global Toast helper
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        let iconClass = 'ti-circle-check';
        if (type === 'danger') iconClass = 'ti-alert-triangle';
        if (type === 'warning') iconClass = 'ti-info-circle';
        if (type === 'info') iconClass = 'ti-info-circle';

        const toast = document.createElement('div');
        toast.className = `toast-custom toast-${type}`;
        toast.innerHTML = `
            <i class="ti ${iconClass} toast-icon"></i>
            <div class="toast-content">${message}</div>
            <button type="button" class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 5000);
    }

    // Show / Hide loading overlay
    function showLoader() {
        const loader = document.getElementById('loading-overlay');
        if (loader) loader.classList.add('show');
    }

    function hideLoader() {
        const loader = document.getElementById('loading-overlay');
        if (loader) loader.classList.remove('show');
    }

    let aesConfirmCallback = null;
    let aesConfirmModalInstance = null;

    function getAesConfirmModal() {
        const el = document.getElementById('aesConfirmModal');
        if (!el) return null;
        if (!aesConfirmModalInstance) {
            aesConfirmModalInstance = new bootstrap.Modal(el);
        }
        return aesConfirmModalInstance;
    }

    function getDestructiveConfirmMessage($el) {
        let message = $el.data('confirm') || $el.attr('data-confirm') || '';
        const $statusSelect = $el.find('select[name="status"]');
        if ($statusSelect.length) {
            const optionConfirm = $statusSelect.find('option:selected').attr('data-confirm');
            if (optionConfirm) {
                message = optionConfirm;
            }
        }
        return message;
    }

    function raiseAesConfirmModalStack() {
        const el = document.getElementById('aesConfirmModal');
        if (!el) return;

        if (el.parentNode !== document.body) {
            document.body.appendChild(el);
        }

        let topZ = 1050;
        document.querySelectorAll('.modal.show').forEach(function(modal) {
            if (modal.id === 'aesConfirmModal') return;
            const z = parseInt(window.getComputedStyle(modal).zIndex, 10);
            if (!isNaN(z) && z > topZ) topZ = z;
        });
        document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
            const z = parseInt(window.getComputedStyle(backdrop).zIndex, 10);
            if (!isNaN(z) && z > topZ) topZ = z;
        });

        const confirmZ = Math.max(topZ + 10, 1200);
        el.style.setProperty('z-index', String(confirmZ), 'important');

        const onShown = function() {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            const lastBackdrop = backdrops[backdrops.length - 1];
            if (lastBackdrop) {
                lastBackdrop.style.setProperty('z-index', String(confirmZ - 5), 'important');
            }
            el.removeEventListener('shown.bs.modal', onShown);
        };
        el.addEventListener('shown.bs.modal', onShown);
    }

    function aesConfirmAction(message, onConfirm, options) {
        options = options || {};
        if (!message && !options.html) {
            if (onConfirm) onConfirm();
            return;
        }
        const modal = getAesConfirmModal();
        if (!modal) {
            if (onConfirm) onConfirm();
            return;
        }
        const titleEl = document.getElementById('aesConfirmModalLabel');
        const messageEl = document.getElementById('aesConfirmModalMessage');
        const confirmBtn = document.getElementById('aesConfirmModalConfirm');
        const previousTitle = titleEl ? titleEl.textContent : '';
        const previousMessageHtml = messageEl ? messageEl.innerHTML : '';
        const previousConfirmLabel = confirmBtn ? confirmBtn.textContent : '';
        const previousConfirmClass = confirmBtn ? confirmBtn.className : '';

        if (titleEl && options.title) {
            titleEl.textContent = options.title;
        }
        if (messageEl) {
            if (options.html) {
                messageEl.innerHTML = options.html;
            } else {
                messageEl.textContent = message;
            }
        }
        if (confirmBtn && options.confirmLabel) {
            confirmBtn.textContent = options.confirmLabel;
        }
        if (confirmBtn && options.confirmClass) {
            confirmBtn.className = options.confirmClass;
        }

        aesConfirmCallback = function() {
            if (titleEl) titleEl.textContent = previousTitle;
            if (messageEl) messageEl.innerHTML = previousMessageHtml;
            if (confirmBtn) {
                confirmBtn.textContent = previousConfirmLabel;
                confirmBtn.className = previousConfirmClass;
            }
            if (onConfirm) onConfirm();
        };

        raiseAesConfirmModalStack();
        modal.show();
    }

    const aesConfirmBtn = document.getElementById('aesConfirmModalConfirm');
    if (aesConfirmBtn) {
        aesConfirmBtn.addEventListener('click', function() {
            const modal = getAesConfirmModal();
            if (modal) modal.hide();
            if (typeof aesConfirmCallback === 'function') {
                const callback = aesConfirmCallback;
                aesConfirmCallback = null;
                callback();
            }
        });
    }

    const aesConfirmModalEl = document.getElementById('aesConfirmModal');
    if (aesConfirmModalEl) {
        aesConfirmModalEl.addEventListener('hidden.bs.modal', function() {
            aesConfirmCallback = null;
        });
    }

    function initTooltipsIn(root) {
        const scope = root || document;
        scope.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
            bootstrap.Tooltip.getOrCreateInstance(el);
        });
    }

    window.buildProjectMemberRemoveCell = function(user, removeUrl) {
        if (user.role === 'admin') {
            return `<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="left" title="System Admin cannot be removed from project teams.">
                <button type="button" class="btn btn-outline-secondary btn-icon border-0" style="width:28px; height:28px; padding:0;" disabled aria-label="System Admin cannot be removed">
                    <i class="ti ti-lock"></i>
                </button>
            </span>`;
        }
        return `<form class="ajax-form d-inline" method="POST" action="${removeUrl}" data-confirm="Are you sure you want to remove this team member?">
            <input type="hidden" name="csrf_token" value="${window.AES_CSRF_TOKEN || ''}">
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="user_id" value="${user.user_id}">
            <button type="submit" class="btn btn-outline-danger btn-icon border-0" style="width:28px; height:28px; padding:0;" title="Remove member">
                <i class="ti ti-user-minus"></i>
            </button>
        </form>`;
    };

    function ensurePartialUrl(url) {
        if (!url) return url;
        if (url.indexOf('partial=') !== -1) return url;
        return url + (url.indexOf('?') !== -1 ? '&' : '?') + 'partial=1';
    }

    function refreshAjaxPartials(items) {
        if (!items || !items.length) return;
        const seen = {};
        const unique = [];
        items.forEach(function(item) {
            if (!item || !item.url || !item.target || seen[item.target]) {
                return;
            }
            seen[item.target] = true;
            unique.push(item);
        });
        unique.forEach(function(item) {
            refreshAjaxPartial(item.url, item.target);
        });
    }

    function refreshAjaxPartial(url, targetSelector, callback, options) {
        options = options || {};
        const $target = $(targetSelector);
        if (!$target.length || !url) {
            if (callback) callback(false);
            return;
        }
        if (!options.quiet) {
            showLoader();
        }
        $.getJSON(ensurePartialUrl(url), function(response) {
            if (!options.quiet) {
                hideLoader();
            }
            $target.removeClass('is-refreshing').attr('aria-busy', 'false');
            if (response && response.html !== undefined) {
                $target.html(response.html);
                if (response.refresh_url) {
                    $target.attr('data-ajax-refresh-url', response.refresh_url);
                }
                $(document).trigger('ajax:content-updated', [targetSelector, response]);
                if (callback) callback(true, response);
            } else if (callback) {
                callback(false);
            }
        }).fail(function() {
            if (!options.quiet) {
                hideLoader();
            }
            $target.removeClass('is-refreshing').attr('aria-busy', 'false');
            if (!options.suppressErrorToast) {
                showToast('Failed to refresh content.', 'danger');
            }
            if (callback) callback(false);
        });
    }

    function getAjaxRefreshUrl($el) {
        return $el.attr('data-ajax-refresh-url') || '';
    }

    function resolveAjaxRefresh($trigger, response) {
        let target = response.target || null;
        let refreshUrl = response.refresh || null;

        const explicitTarget = $trigger.attr('data-ajax-refresh') || $trigger.attr('data-ajax-target');
        if (explicitTarget) {
            target = explicitTarget.charAt(0) === '#' ? explicitTarget : '#' + explicitTarget;
            if (!refreshUrl) {
                refreshUrl = getAjaxRefreshUrl($(target));
            }
        }

        if (!target) {
            const $container = $trigger.closest('[data-ajax-container]');
            if ($container.length) {
                target = '#' + $container.attr('id');
                if (!refreshUrl) {
                    refreshUrl = getAjaxRefreshUrl($container);
                }
            }
        }

        if (!target) {
            const $pageContainer = $('[data-ajax-container]').first();
            if ($pageContainer.length) {
                target = '#' + $pageContainer.attr('id');
                if (!refreshUrl) {
                    refreshUrl = getAjaxRefreshUrl($pageContainer);
                }
            }
        }

        return { target, refreshUrl };
    }

    function getAjaxContainer($el) {
        const explicit = $el.attr('data-ajax-refresh') || $el.attr('data-ajax-target');
        if (explicit) {
            return $(explicit.charAt(0) === '#' ? explicit : '#' + explicit);
        }
        const $closest = $el.closest('[data-ajax-container]');
        if ($closest.length) return $closest;
        return $('[data-ajax-container]').first();
    }

    function handleAjaxSuccess($trigger, response) {
        if (response && response.message) {
            showToast(response.message, response.success ? 'success' : 'danger');
        }
        if (!response || !response.success) return;

        if ($trigger.data('ajax-reload')) {
            const $modal = $trigger.closest('.modal');
            if ($modal.length) {
                $modal.modal('hide');
                if ($trigger.hasClass('ajax-form') && $trigger.data('ajax-reset')) {
                    $trigger[0].reset();
                    $trigger.removeClass('was-validated');
                }
            }
            // Brief delay so the success toast is visible before widgets refresh
            setTimeout(function() {
                window.location.reload();
            }, 900);
            return;
        }

        const $modal = $trigger.closest('.modal');
        if ($modal.length) {
            $modal.modal('hide');
            if ($trigger.hasClass('ajax-form') && $trigger.data('ajax-reset')) {
                $trigger[0].reset();
                $trigger.removeClass('was-validated');
            }
        }

        if (response.user) {
            const u = response.user;
            if (u.full_name) {
                $('.profile-summary-name').text(u.full_name);
                $('.profile-avatar-initials').text(
                    u.full_name.split(' ').filter(Boolean).map(n => n[0]).slice(0, 2).join('').toUpperCase()
                );
            }
            if (u.designation !== undefined) $('.profile-summary-designation').text(u.designation || 'No Title');
            if (u.organization !== undefined) $('.profile-summary-organization').text(u.organization || 'AES');
            if (u.email !== undefined) $('.profile-summary-email').text(u.email);
        }

        if (response.comment) {
            const c = response.comment;
            const isSystem = (c.comment || '').startsWith('System Action:') || (c.comment || '').startsWith('[');
            const html = `
                <div class="d-flex align-items-start gap-2.5 p-3 rounded ${isSystem ? 'bg-light border' : 'bg-white border'}">
                    <div class="avatar ${isSystem ? 'bg-secondary-subtle text-secondary' : 'bg-primary-subtle text-primary'} rounded-circle d-flex align-items-center justify-content-center font-weight-bold" style="width: 36px; height: 36px; font-size: 12px;">
                        ${isSystem ? 'SYS' : (c.full_name || '').split(' ').filter(Boolean).map(n => n[0]).slice(0, 2).join('').toUpperCase()}
                    </div>
                    <div class="flex-fill">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="font-weight-semibold fs-7">${isSystem ? 'System' : (c.full_name || '')}</span>
                            <small class="text-secondary fs-8">Just now</small>
                        </div>
                        <p class="text-secondary mb-0 fs-7" style="white-space: pre-line;">${$('<div>').text(c.comment || '').html()}</p>
                    </div>
                </div>`;
            const $list = $('#ticket-comments-list');
            if ($list.length) {
                $list.find('.empty-comments-placeholder').remove();
                $list.append(html);
            }
            $trigger.find('textarea[name="comment"]').val('');
        }

        if (response.task) {
            appendTicketTask(response.task);
        }

        if (response.skip_refresh) {
            if (response.follow_up_message) {
                showToast(response.follow_up_message, 'info');
            }
            if (response.redirect && (response.allowRedirect || response.redirect_delay)) {
                const delay = parseInt(response.redirect_delay, 10) || 0;
                setTimeout(function() {
                    window.location.href = response.redirect;
                }, delay);
            }
            $(document).trigger('ajax:success', [$trigger, response]);
            return;
        }

        const resolved = resolveAjaxRefresh($trigger, response);
        const target = resolved.target;
        const refreshUrl = resolved.refreshUrl;

        if (response.html && target) {
            $(target).html(response.html);
            if (response.refresh_url) {
                $(target).attr('data-ajax-refresh-url', response.refresh_url);
            }
            $(document).trigger('ajax:content-updated', [target, response]);
        } else if (response.refreshes && Array.isArray(response.refreshes) && response.refreshes.length) {
            refreshAjaxPartials(response.refreshes);
        } else if (refreshUrl && target) {
            refreshAjaxPartial(refreshUrl, target, function(success, response) {
                if (success && response && response.refresh_url) {
                    $(target).attr('data-ajax-refresh-url', response.refresh_url);
                }
            });
        }

        if ($trigger.closest('#projectMembersModal').length && typeof reloadProjectMembersModal === 'function') {
            const code = $('#projectMembersModal').data('project-code');
            if (code) reloadProjectMembersModal(code);
        }

        if (response.redirect && ($trigger.data('ajax-allow-redirect') || response.allowRedirect)) {
            window.location.href = response.redirect;
        }

        $(document).trigger('ajax:success', [$trigger, response]);
    }

    function appendTicketTask(task) {
        const $panel = $('#ticket-tasks-list');
        if (!$panel.length) return;
        $panel.find('.empty-tasks-placeholder').remove();
        const checked = task.status === 'Completed' ? 'checked' : '';
        const strike = task.status === 'Completed' ? 'text-decoration-line-through text-muted' : 'font-weight-medium';
        $panel.append(`
            <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-light-subtle" data-task-id="${task.id}">
                <div class="d-flex align-items-center gap-2">
                    <input type="checkbox" class="form-check-input task-toggle-checkbox" data-task-id="${task.id}" ${checked}>
                    <span class="fs-7 ${strike}">${$('<div>').text(task.task_name || '').html()}</span>
                </div>
                <span class="badge bg-secondary-subtle text-secondary fs-8">${task.status || 'Pending'}</span>
            </div>
        `);
    }

    function submitAjaxForm($form) {
        const $submitBtn = $form.find('[type="submit"]');
        const originalBtnHtml = $submitBtn.html();
        $submitBtn.prop('disabled', true);
        $submitBtn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...');

        const url = $form.attr('action') || window.location.href;
        const method = $form.attr('method') || 'POST';
        const formData = new FormData($form[0]);

        showLoader();
        $.ajax({
            url: url,
            type: method,
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                hideLoader();
                $submitBtn.prop('disabled', false).html(originalBtnHtml);
                handleAjaxSuccess($form, response);
            },
            error: function(xhr) {
                hideLoader();
                $submitBtn.prop('disabled', false).html(originalBtnHtml);
                let errorMessage = 'An error occurred while processing your request.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.message) errorMessage = response.message;
                } catch(e) {}
                showToast(errorMessage, 'danger');
            }
        });
    }

    // Intercept ajax forms
    $(document).on('submit', '.ajax-form', function(e) {
        e.preventDefault();

        const $form = $(this);

        if (this.checkValidity() === false) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        const confirmMessage = getDestructiveConfirmMessage($form);
        if (confirmMessage) {
            const confirmTitle = $form.data('confirm-title') || $form.attr('data-confirm-title') || '';
            aesConfirmAction(confirmMessage, function() {
                submitAjaxForm($form);
            }, confirmTitle ? { title: confirmTitle } : {});
            return;
        }

        submitAjaxForm($form);
    });

    function executeAjaxLink($link) {
        showLoader();
        $.ajax({
            url: $link.attr('href'),
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                hideLoader();
                handleAjaxSuccess($link, response);
            },
            error: function(xhr) {
                hideLoader();
                let errorMessage = 'An error occurred while processing your request.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.message) errorMessage = response.message;
                } catch(e) {}
                showToast(errorMessage, 'danger');
            }
        });
    }

    // Intercept ajax links (GET actions)
    $(document).on('click', '.ajax-link', function(e) {
        e.preventDefault();
        const $link = $(this);
        const confirmMessage = getDestructiveConfirmMessage($link);
        if (confirmMessage) {
            aesConfirmAction(confirmMessage, function() {
                executeAjaxLink($link);
            });
            return;
        }
        executeAjaxLink($link);
    });

    // Partial page links (pagination, clear filters)
    $(document).on('click', '.ajax-partial-link', function(e) {
        e.preventDefault();
        const $link = $(this);
        if ($link.closest('.page-item.disabled').length) return;
        let target = $link.attr('data-ajax-target');
        if (!target) {
            const $container = $link.closest('[data-ajax-container]');
            if ($container.length) target = '#' + $container.attr('id');
        }
        if (!target) {
            const $pageContainer = $('[data-ajax-container]').first();
            if ($pageContainer.length) target = '#' + $pageContainer.attr('id');
        }
        const selector = target ? (target.charAt(0) === '#' ? target : '#' + target) : null;
        if (!selector) return;
        refreshAjaxPartial($link.attr('href'), selector);
    });

    // Filter/search forms without full page reload
    $(document).on('submit', '.ajax-filter-form', function(e) {
        e.preventDefault();
        const $form = $(this);
        let target = $form.attr('data-ajax-target');
        if (!target) return;
        target = target.charAt(0) === '#' ? target : '#' + target;
        const url = $form.attr('action') || window.location.pathname;
        const params = $form.serialize();
        const fetchUrl = url + (url.indexOf('?') !== -1 ? '&' : '?') + params;
        refreshAjaxPartial(fetchUrl, target, function(success, response) {
            if (success && response && response.refresh_url) {
                $(target).attr('data-ajax-refresh-url', response.refresh_url);
            }
        });
    });

    // Auto-submit filter selects
    $(document).on('change', '.ajax-filter-form select', function() {
        $(this).closest('form').trigger('submit');
    });

    // Task status update (admin dropdown + dev/intern checklist)
    function applyTaskRowStatusUi($row, newStatus) {
        if (!$row || !$row.length) return;

        const slug = String(newStatus).toLowerCase().replace(/\s+/g, '-');
        $row.attr('data-task-status', newStatus);
        $row.removeClass('task-row--pending task-row--in-progress task-row--completed task-row--blocked task-row--checklist');
        $row.addClass('task-row--' + slug);
        if ($row.find('.task-checklist-control').length) {
            $row.addClass('task-row--checklist');
        }
        if (newStatus === 'Completed') {
            $row.addClass('task-row--completed');
        }

        const $name = $row.find('.task-name-cell').first();
        if (newStatus === 'Completed') {
            $name.addClass('text-decoration-line-through text-muted').removeClass('font-weight-semibold');
        } else {
            $name.removeClass('text-decoration-line-through text-muted').addClass('font-weight-semibold');
        }
    }

    function refreshTaskViewsAfterStatusUpdate($contextRow, newStatus) {
        if ($contextRow && $contextRow.length && newStatus) {
            applyTaskRowStatusUi($contextRow, newStatus);
        }

        const quietRefresh = { quiet: true, suppressErrorToast: true };
        const $ticketDynamic = $('#ticket-dynamic-content');
        if ($ticketDynamic.length) {
            const ticketRefreshUrl = $ticketDynamic.attr('data-ajax-refresh-url');
            if (ticketRefreshUrl) {
                refreshAjaxPartial(ticketRefreshUrl, '#ticket-dynamic-content', null, quietRefresh);
                return;
            }
        }

        const $tasksContainer = $('#my-tasks-content');
        if ($tasksContainer.length) {
            const refreshUrl = $tasksContainer.attr('data-ajax-refresh-url');
            if (refreshUrl) {
                refreshAjaxPartial(refreshUrl, '#my-tasks-content', null, quietRefresh);
            }
        }
    }

    function postTaskStatusUpdate(taskId, newStatus, $trigger, revertFn) {
        const $row = $trigger ? $trigger.closest('[data-task-id]') : $();
        const $control = $trigger ? $trigger.closest('.task-checklist-control') : $();

        if ($control.length) {
            $control.addClass('is-busy');
        } else if ($trigger && $trigger.prop('disabled') !== undefined) {
            $trigger.prop('disabled', true);
        }

        $.ajax({
            url: (window.AES_TASK_STATUS_URL_TEMPLATE || '').replace('__TASK_ID__', taskId),
            type: 'POST',
            data: {
                csrf_token: window.AES_CSRF_TOKEN || '',
                task_id: taskId,
                status: newStatus
            },
            dataType: 'json',
            success: function(response) {
                if ($control.length) {
                    $control.removeClass('is-busy');
                } else if ($trigger && $trigger.prop('disabled') !== undefined) {
                    $trigger.prop('disabled', false);
                }
                if (response && response.success) {
                    showToast(response.message || 'Task status updated.', 'success');
                    refreshTaskViewsAfterStatusUpdate($row, newStatus);
                } else {
                    if (typeof revertFn === 'function') {
                        revertFn();
                    }
                    showToast((response && (response.message || response.error)) || 'Failed to update task.', 'danger');
                }
            },
            error: function() {
                if ($control.length) {
                    $control.removeClass('is-busy');
                } else if ($trigger && $trigger.prop('disabled') !== undefined) {
                    $trigger.prop('disabled', false);
                }
                if (typeof revertFn === 'function') {
                    revertFn();
                }
                showToast('Server error while updating task status.', 'danger');
            }
        });
    }

    $(document).on('change', '.task-status-select', function() {
        const $select = $(this);
        const taskId = $select.data('task-id');
        const newStatus = $select.val();
        const previousStatus = $select.data('previous-status') || $select.val();

        postTaskStatusUpdate(taskId, newStatus, $select, function() {
            $select.val(previousStatus);
        });
    });

    $(document).on('focus', '.task-status-select', function() {
        $(this).data('previous-status', $(this).val());
    });

    $(document).on('click', '.task-checklist-start', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const $control = $btn.closest('.task-checklist-control');
        postTaskStatusUpdate($control.data('task-id'), 'In Progress', $btn);
    });

    $(document).on('click', '.task-checklist-done', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const $control = $btn.closest('.task-checklist-control');
        if (($control.data('status') || '') !== 'In Progress') {
            return;
        }
        postTaskStatusUpdate($control.data('task-id'), 'Completed', $btn);
    });

    $(document).on('change', '#taskEditAssignee', function() {
        const $select = $(this);
        const original = String($select.data('original-assignee') || '');
        if (original !== '' && String($select.val() || '') !== original) {
            $('#taskEditStatus').val('Pending');
        }
    });

    function populateTaskEditAssignees($select, members, selectedId) {
        $select.empty();
        $select.append('<option value="">-- Select developer or intern --</option>');
        (members || []).forEach(function(member) {
            const label = (member.full_name || '') + (member.role ? ' (' + member.role + ')' : '');
            $select.append($('<option>', { value: member.user_id, text: label }));
        });
        $select.val(selectedId ? String(selectedId) : '').data('original-assignee', selectedId ? String(selectedId) : '');
    }

    function formatTaskDateInput(value) {
        if (!value) return '';
        return String(value).substring(0, 10);
    }

    // Admin: edit task via modal (tasks list + ticket checklist)
    $(document).on('click', '.task-edit-btn', function() {
        const taskId = $(this).data('task-id');
        const $modal = $('#taskEditModal');
        const $form = $('#taskEditForm');
        if (!$modal.length || !$form.length) return;

        const refreshTarget = $('#my-tasks-content').length ? '#my-tasks-content' : '#ticket-dynamic-content';
        $form.attr('data-ajax-refresh', refreshTarget);

        showLoader();
        $.ajax({
            url: '<?php echo route("tasks-edit", ["id" => "__ID__"]); ?>'.replace('__ID__', taskId),
            type: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(response) {
                hideLoader();
                if (response && response.success && response.task) {
                    const task = response.task;
                    $form.attr('action', '<?php echo route("tasks-edit", ["id" => "__ID__"]); ?>'.replace('__ID__', task.id));
                    $('#taskEditName').val(task.task_name || '');
                    populateTaskEditAssignees($('#taskEditAssignee'), response.projectMembers || [], task.assigned_member || '');
                    $('#taskEditDueDate').val(formatTaskDateInput(task.due_date));
                    $('#taskEditStatus').val(task.status || 'Pending');
                    const $ref = $('#taskEditTicketRef');
                    if (task.ticket_title) {
                        $ref.text('Ticket: ' + task.ticket_title).removeClass('d-none');
                    } else {
                        $ref.addClass('d-none').text('');
                    }
                    $modal.modal('show');
                } else {
                    showToast(response.message || 'Failed to load task.', 'danger');
                }
            },
            error: function() {
                hideLoader();
                showToast('Failed to load task details.', 'danger');
            }
        });
    });

    // Legacy checkbox handler (disabled checkboxes should not exist; guard anyway)
    $(document).on('change', '.task-toggle-checkbox', function() {
        const checkbox = $(this);
        const taskId = checkbox.data('task-id');
        const isChecked = checkbox.is(':checked');
        const newStatus = isChecked ? 'Completed' : 'Pending';
        const $row = checkbox.closest('[data-task-id], .list-group-item, .d-flex.align-items-center.justify-content-between');

        showLoader();
        $.ajax({
            url: checkbox.data('status-url') || window.AES_TASK_STATUS_URL_TEMPLATE.replace('__TASK_ID__', taskId),
            type: 'POST',
            data: {
                csrf_token: window.AES_CSRF_TOKEN || '',
                task_id: taskId,
                status: newStatus
            },
            dataType: 'json',
            success: function(response) {
                hideLoader();
                if (response && response.success) {
                    showToast(response.message || 'Task status updated.', 'success');
                    const $label = checkbox.closest('.d-flex').find('span.fs-7, .task-name-label');
                    if (isChecked) {
                        $label.addClass('text-decoration-line-through text-muted').removeClass('font-weight-semibold font-weight-medium');
                    } else {
                        $label.removeClass('text-decoration-line-through text-muted').addClass('font-weight-medium');
                    }
                    $row.find('.badge').last().text(newStatus);
                } else {
                    checkbox.prop('checked', !isChecked);
                    showToast((response && (response.message || response.error)) || 'Failed to update task.', 'danger');
                }
            },
            error: function() {
                hideLoader();
                checkbox.prop('checked', !isChecked);
                showToast('Server error while updating task status.', 'danger');
            }
        });
    });

    function toggleSidebarMobile() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('mobile-open');
    }

    (function() {
        let attachmentPreviewReady = false;

        function initAttachmentPreviewModal() {
            if (attachmentPreviewReady) return;

            const modalEl = document.getElementById('attachmentPreviewModal');
            if (!modalEl || typeof bootstrap === 'undefined') return;

            attachmentPreviewReady = true;

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            const $body = $('#attachmentPreviewBody');
            const $title = $('#attachmentPreviewModalLabel');
            const $meta = $('#attachmentPreviewMeta');
            const $counter = $('#attachmentPreviewCounter');
            const $download = $('#attachmentPreviewDownload');
            const $prev = $('#attachmentPreviewPrev');
            const $next = $('#attachmentPreviewNext');
            let currentIndex = 0;

            function getTriggerForIndex(index) {
                return $('.attachment-preview-trigger[data-attachment-index="' + index + '"]').first();
            }

            function getAttachmentCount() {
                const indices = new Set();
                $('.attachment-preview-trigger').each(function() {
                    const idx = parseInt($(this).attr('data-attachment-index'), 10);
                    if (!Number.isNaN(idx)) {
                        indices.add(idx);
                    }
                });
                return indices.size;
            }

            function escapeHtml(value) {
                return $('<div>').text(value || '').html();
            }

            function buildPreviewHtml(type, url, name) {
                if (type === 'image') {
                    return `<div class="text-center">
                        <img src="${escapeHtml(url)}" alt="${escapeHtml(name)}" class="img-fluid rounded shadow-sm" style="max-height: 72vh; width: auto;">
                    </div>`;
                }
                if (type === 'pdf') {
                    return `<iframe src="${escapeHtml(url)}" class="w-100 rounded border bg-white" style="height: 72vh;" title="${escapeHtml(name)}"></iframe>`;
                }
                return `<div class="text-center py-5">
                    <i class="ti ti-file text-primary" style="font-size: 4rem;"></i>
                    <p class="mt-3 mb-1 font-weight-semibold">${escapeHtml(name)}</p>
                    <p class="text-muted fs-7 mb-0">Preview is not available for this file type. Use "Open in New Tab" to view or download.</p>
                </div>`;
            }

            function updateNavButtons() {
                const total = getAttachmentCount();
                $prev.prop('disabled', currentIndex <= 0);
                $next.prop('disabled', currentIndex >= total - 1);
                $counter.text(total > 1 ? `${currentIndex + 1} of ${total}` : '');
            }

            function showAttachmentAt(index) {
                const $trigger = getTriggerForIndex(index);
                if (!$trigger.length) return;

                currentIndex = index;
                const url = $trigger.attr('data-attachment-url');
                const name = $trigger.attr('data-attachment-name');
                const type = $trigger.attr('data-attachment-type');
                const size = $trigger.attr('data-attachment-size');

                $title.text(name || 'Attachment');
                $meta.text(size || '');
                $download.attr('href', url || '#');
                $body.html(buildPreviewHtml(type, url, name));
                updateNavButtons();
                modal.show();
            }

            $(document).on('click', '.attachment-preview-trigger', function(e) {
                e.preventDefault();
                const index = parseInt($(this).attr('data-attachment-index'), 10);
                if (Number.isNaN(index)) return;
                showAttachmentAt(index);
            });

            $prev.on('click', function() {
                if (currentIndex > 0) {
                    showAttachmentAt(currentIndex - 1);
                }
            });

            $next.on('click', function() {
                const total = getAttachmentCount();
                if (currentIndex < total - 1) {
                    showAttachmentAt(currentIndex + 1);
                }
            });

            modalEl.addEventListener('hidden.bs.modal', function() {
                $body.empty();
                $title.text('');
                $meta.text('');
                $counter.text('');
                $download.attr('href', '#');
            });

            $(document).on('keydown', function(e) {
                if (!modalEl.classList.contains('show')) return;
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    $prev.trigger('click');
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    $next.trigger('click');
                }
            });
        }

        initAttachmentPreviewModal();
    })();

    function downloadProjectFinancialReport(exportUrl, $btn) {
        if (!exportUrl) return;

        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Generating...');

        fetch(exportUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(response) {
            const contentType = response.headers.get('Content-Type') || '';
            if (!response.ok) {
                if (contentType.indexOf('application/json') !== -1) {
                    return response.json().then(function(data) {
                        throw new Error((data && data.message) ? data.message : 'Failed to generate report.');
                    });
                }
                throw new Error('Failed to generate report. Please try again.');
            }

            if (contentType.indexOf('text/csv') === -1 && contentType.indexOf('application/pdf') === -1) {
                throw new Error('Unexpected response from server. Please refresh and try again.');
            }

            const disposition = response.headers.get('Content-Disposition') || '';
            let filename = 'financial-report';
            const match = disposition.match(/filename=\"?([^\";]+)\"?/i);
            if (match && match[1]) {
                filename = match[1];
            }

            return response.blob().then(function(blob) {
                return { blob: blob, filename: filename };
            });
        })
        .then(function(result) {
            const link = document.createElement('a');
            const objectUrl = URL.createObjectURL(result.blob);
            link.href = objectUrl;
            link.download = result.filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(objectUrl);
            showToast('Report downloaded successfully.', 'success');
        })
        .catch(function(error) {
            showToast(error.message || 'Failed to generate report.', 'danger');
        })
        .finally(function() {
            $btn.prop('disabled', false).html(originalHtml);
        });
    }

    $(document).on('click', '.project-financial-export-btn', function(e) {
        e.preventDefault();
        downloadProjectFinancialReport($(this).data('export-url'), $(this));
    });
    
    // Auto-dismiss alerts after 5 seconds
    document.addEventListener("DOMContentLoaded", function() {
        initTooltipsIn(document);

        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 6000);
        });
    });
</script>

<?php if (isset($ticket['id']) && (can_access_client_chat() || can_access_team_chat() || can_access_admin_dev_chat(null, $ticket))): ?>
    <?php require __DIR__ . '/../tickets/_team_chat_scripts.php'; ?>
<?php endif; ?>
</body>
</html>
