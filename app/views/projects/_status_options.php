<?php
/** @var string $selected */
/** @var string|null $default */
$selected = $selected ?? '';
$default = $default ?? 'Initiated';
foreach (get_project_statuses() as $projectStatus):
    $isSelected = $selected !== ''
        ? normalize_project_status($selected) === $projectStatus
        : ($default !== null && $projectStatus === $default);
?>
<option value="<?php echo e($projectStatus); ?>"<?php echo $isSelected ? ' selected' : ''; ?>><?php echo e($projectStatus); ?></option>
<?php endforeach; ?>
