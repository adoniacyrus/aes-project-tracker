        </div> <!-- Page Body Container End -->
        
        <!-- Footer -->
        <footer class="footer bg-white border-top mt-auto">
            <div class="container-fluid">
                <div class="row align-items-center justify-content-between">
                    <div class="col-12 col-md-6 text-center text-md-start mb-2 mb-md-0">
                        <span class="text-secondary fs-7">&copy; <?php echo date('Y'); ?> <a href="<?php echo route('dashboard'); ?>" class="text-decoration-none text-primary font-weight-medium">AES Project Tracker</a>. All rights reserved.</span>
                    </div>
                    <div class="col-12 col-md-6 text-center text-md-end">
                        <span class="text-secondary fs-7">Version 1.0.0 (Core PHP MVC)</span>
                    </div>
                </div>
            </div>
        </footer>
    </div> <!-- Page Wrapper End -->
</div> <!-- Main Wrapper End -->

<?php if (isset($ticket['id']) && can_access_team_chat()): ?>
    <?php require __DIR__ . '/../tickets/_team_chat_widget.php'; ?>
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

    function ensurePartialUrl(url) {
        if (!url) return url;
        if (url.indexOf('partial=') !== -1) return url;
        return url + (url.indexOf('?') !== -1 ? '&' : '?') + 'partial=1';
    }

    function refreshAjaxPartial(url, targetSelector, callback) {
        const $target = $(targetSelector);
        if (!$target.length || !url) {
            if (callback) callback(false);
            return;
        }
        showLoader();
        $.getJSON(ensurePartialUrl(url), function(response) {
            hideLoader();
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
            hideLoader();
            showToast('Failed to refresh content.', 'danger');
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
            response.refreshes.forEach(function(item) {
                if (item.url && item.target) {
                    refreshAjaxPartial(item.url, item.target);
                }
            });
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

    // Intercept ajax forms
    $(document).on('submit', '.ajax-form', function(e) {
        e.preventDefault();

        const $form = $(this);
        const confirmMessage = $form.data('confirm') || $form.attr('data-confirm');
        if (confirmMessage && !confirm(confirmMessage)) {
            return;
        }

        if (this.checkValidity() === false) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        const url = $form.attr('action') || window.location.href;
        const method = $form.attr('method') || 'POST';
        const formData = new FormData(this);
        const $submitBtn = $form.find('[type="submit"]');
        const originalBtnHtml = $submitBtn.html();
        $submitBtn.prop('disabled', true);
        $submitBtn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...');

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
    });

    // Intercept ajax links (GET actions)
    $(document).on('click', '.ajax-link', function(e) {
        e.preventDefault();
        const $link = $(this);
        const confirmMessage = $link.attr('data-confirm');
        if (confirmMessage && !confirm(confirmMessage)) {
            return;
        }

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

    // Ticket workflow: apply per-option confirm message before AJAX submit
    $(document).on('change', '#ticketWorkflowStatus', function() {
        const confirmMsg = $(this).find('option:selected').data('confirm');
        const $form = $(this).closest('form');
        if (confirmMsg) {
            $form.attr('data-confirm', confirmMsg);
        } else {
            $form.removeAttr('data-confirm');
        }
    });

    // Task status update via dropdown (no page reload)
    $(document).on('change', '.task-status-select', function() {
        const $select = $(this);
        const taskId = $select.data('task-id');
        const newStatus = $select.val();
        const previousStatus = $select.data('previous-status') || $select.find('option:selected').text();

        showLoader();
        $.ajax({
            url: $select.data('status-url') || window.AES_TASK_STATUS_URL_TEMPLATE.replace('__TASK_ID__', taskId),
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
                    $select.data('previous-status', newStatus);
                    const $row = $select.closest('[data-task-id]');
                    const $name = $row.find('.font-weight-semibold, .font-weight-medium').first();
                    if (newStatus === 'Completed') {
                        $name.addClass('text-decoration-line-through text-muted').removeClass('font-weight-semibold font-weight-medium');
                    } else {
                        $name.removeClass('text-decoration-line-through text-muted').addClass('font-weight-semibold');
                    }
                    const $tasksContainer = $('#my-tasks-content');
                    if ($tasksContainer.length) {
                        const refreshUrl = $tasksContainer.attr('data-ajax-refresh-url');
                        if (refreshUrl) {
                            refreshAjaxPartial(refreshUrl, '#my-tasks-content');
                        }
                    }
                    const $ticketDynamic = $('#ticket-dynamic-content');
                    if ($ticketDynamic.length) {
                        const ticketRefreshUrl = $ticketDynamic.attr('data-ajax-refresh-url');
                        if (ticketRefreshUrl) {
                            refreshAjaxPartial(ticketRefreshUrl, '#ticket-dynamic-content');
                        }
                    }
                    const $ticketSidebar = $('#ticket-dynamic-sidebar');
                    if ($ticketSidebar.length) {
                        const sidebarRefreshUrl = $ticketSidebar.attr('data-ajax-refresh-url');
                        if (sidebarRefreshUrl) {
                            refreshAjaxPartial(sidebarRefreshUrl, '#ticket-dynamic-sidebar');
                        }
                    }
                } else {
                    if ($select.data('previous-status')) {
                        $select.val($select.data('previous-status'));
                    }
                    showToast((response && (response.message || response.error)) || 'Failed to update task.', 'danger');
                }
            },
            error: function() {
                hideLoader();
                showToast('Server error while updating task status.', 'danger');
            }
        });
    });

    $(document).on('focus', '.task-status-select', function() {
        $(this).data('previous-status', $(this).val());
    });

    // Admin: edit task on ticket page via modal
    $(document).on('click', '.task-edit-btn', function() {
        const taskId = $(this).data('task-id');
        const $modal = $('#ticketTaskEditModal');
        const $form = $('#ticketTaskEditForm');
        if (!$modal.length || !$form.length) return;

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
                    $('#ticketTaskEditName').val(task.task_name || '');
                    $('#ticketTaskEditAssignee').val(task.assigned_member || '');
                    $('#ticketTaskEditDueDate').val(task.due_date || '');
                    $('#ticketTaskEditStatus').val(task.status || 'Pending');
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
    
    // Auto-dismiss alerts after 5 seconds
    document.addEventListener("DOMContentLoaded", function() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 6000);
        });
    });
</script>

<?php if (isset($ticket['id']) && can_access_team_chat()): ?>
    <?php require __DIR__ . '/../tickets/_team_chat_scripts.php'; ?>
<?php endif; ?>
</body>
</html>
