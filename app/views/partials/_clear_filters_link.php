<?php
/**
 * Shared "Clear Filters" link for AJAX list pages.
 *
 * Expected variables:
 * - $clearFiltersUrl (string)
 * - $clearFiltersTarget (string) e.g. #projects-ajax-content
 * - $clearFiltersLabel (string, optional)
 * - $clearFiltersBtnClass (string, optional)
 */
if (empty($clearFiltersUrl) || empty($clearFiltersTarget)) {
    return;
}
$clearFiltersLabel = $clearFiltersLabel ?? 'Clear Filters';
$clearFiltersBtnClass = $clearFiltersBtnClass ?? 'btn btn-outline-primary px-3 py-2 d-inline-flex align-items-center gap-2 ajax-partial-link flex-shrink-0 font-weight-medium text-nowrap';
?>
<a href="<?php echo e($clearFiltersUrl); ?>"
   class="<?php echo e($clearFiltersBtnClass); ?>"
   data-ajax-target="<?php echo e($clearFiltersTarget); ?>"
   title="<?php echo e($clearFiltersLabel); ?>">
    <i class="ti ti-filter-off"></i> <?php echo e($clearFiltersLabel); ?>
</a>
