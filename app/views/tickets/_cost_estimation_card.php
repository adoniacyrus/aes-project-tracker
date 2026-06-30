<?php
$userRole = $userRole ?? ($_SESSION['user_role'] ?? '');
if (!can_view_ticket_cost_estimation($userRole)) {
    return;
}
$canEditEstimation = $canEditEstimation ?? can_edit_ticket_estimation($userRole);
?>
<div id="ticket-cost-estimation" data-ajax-container data-ajax-refresh-url="<?php echo e(route('tickets-view', ['id' => $ticket['id'], 'partial' => 'estimation'])); ?>">
    <div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
        <div class="ticket-sidebar-card__head">
            <i class="ti ti-receipt text-primary"></i>
            <span>Ticket Cost Estimation</span>
        </div>
        <div class="ticket-sidebar-card__body">
            <?php if ($canEditEstimation): ?>
                <form id="ticketCostEstimationForm"
                      action="<?php echo route('tickets-save-estimation', ['id' => $ticket['id']]); ?>"
                      method="POST"
                      class="ajax-form ticket-cost-estimation-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="ticket_id" value="<?php echo (int)$ticket['id']; ?>">
                    <div class="mb-2">
                        <label class="ticket-meta-label" for="estimatedCostInput">Estimated Cost (Rs.)</label>
                        <input type="number"
                               step="0.01"
                               min="0.01"
                               name="estimated_cost"
                               id="estimatedCostInput"
                               class="form-control form-control-sm"
                               value="<?php echo e($ticket['estimated_cost'] ?? ''); ?>"
                               required>
                    </div>
                    <div class="mb-2">
                        <label class="ticket-meta-label" for="estimatedDeliveryInput">Estimated Delivery Date</label>
                        <input type="date"
                               name="estimated_delivery_date"
                               id="estimatedDeliveryInput"
                               class="form-control form-control-sm"
                               value="<?php echo e($ticket['estimated_delivery_date'] ?? ''); ?>"
                               required>
                    </div>
                    <div class="mb-3">
                        <label class="ticket-meta-label" for="costChangeReasonInput">Reason for Cost Revision <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="cost_change_reason"
                                  id="costChangeReasonInput"
                                  rows="2"
                                  class="form-control form-control-sm"
                                  placeholder="e.g. Additional feature requested, scope increased, discount applied"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="ti ti-device-floppy me-1"></i> Save Estimation
                    </button>
                </form>
            <?php else: ?>
                <dl class="ticket-meta-grid mb-0">
                    <div class="ticket-meta-grid__item ticket-meta-grid__item--full">
                        <dt>Estimated Cost</dt>
                        <dd>
                            <?php if (!empty($ticket['estimated_cost'])): ?>
                                <?php echo format_rs_currency((float)$ticket['estimated_cost'], 2); ?>
                            <?php else: ?>
                                <span class="text-muted">Not set</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div class="ticket-meta-grid__item ticket-meta-grid__item--full">
                        <dt>Estimated Delivery</dt>
                        <dd>
                            <?php if (!empty($ticket['estimated_delivery_date'])): ?>
                                <?php echo date('M d, Y', strtotime($ticket['estimated_delivery_date'])); ?>
                            <?php else: ?>
                                <span class="text-muted">Not set</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>
            <?php endif; ?>
        </div>
    </div>
</div>
