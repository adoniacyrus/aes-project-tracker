<script>
(function () {
    const editUrlTemplate = <?php echo json_encode(route('external-work-logs-edit', ['id' => '__ID__'])); ?>;
    const statusUrlTemplate = <?php echo json_encode(route('external-work-logs-status', ['id' => '__ID__'])); ?>;

    $(document).on('click', '.ewl-edit-btn', function () {
        const id = $(this).data('id');
        const form = document.getElementById('ewlEditForm');
        if (!form || !id) return;

        form.action = editUrlTemplate.replace('__ID__', id);
        if (typeof showLoader === 'function') showLoader();

        $.ajax({
            url: form.action,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (typeof hideLoader === 'function') hideLoader();
                if (!response || !response.success || !response.log) {
                    if (typeof showToast === 'function') showToast(response.message || 'Failed to load work log.', 'danger');
                    return;
                }
                const log = response.log;
                const $form = $(form);
                $form.find('.ewl-project-select').val(String(log.project_id || ''));
                $form.find('.ewl-title').val(log.title || '');
                $form.find('.ewl-description').val(log.description || '');
                $form.find('.ewl-source').val(log.communication_source || 'Email');
                $form.find('.ewl-requested-by').val(log.requested_by || '');
                $form.find('.ewl-assigned-to').val(String(log.assigned_to || ''));
                $form.find('.ewl-work-date').val(log.work_date || '');
                $form.find('.ewl-estimated-hours').val(log.estimated_hours || '');
                $form.find('.ewl-actual-hours').val(log.actual_hours || '');
                $form.find('.ewl-status').val(log.status || 'Pending');
                $form.find('.ewl-client-reference').val(log.client_reference || '');
                $form.find('.ewl-completion-notes').val(log.completion_notes || '');
            },
            error: function () {
                if (typeof hideLoader === 'function') hideLoader();
                if (typeof showToast === 'function') showToast('Failed to load work log.', 'danger');
            }
        });
    });

    $(document).on('click', '.ewl-complete-btn', function () {
        const id = $(this).data('id');
        const title = $(this).data('title') || 'this log';
        const form = document.getElementById('ewlCompleteForm');
        if (!form || !id) return;
        form.action = statusUrlTemplate.replace('__ID__', id);
        $('#ewlCompleteTitle').text(title);
        form.reset();
        $(form).find('input[name="status"]').val('Completed');
    });
})();
</script>
