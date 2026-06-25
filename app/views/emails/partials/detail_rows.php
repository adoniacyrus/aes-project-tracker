<?php
/** @var array $rows */
foreach ($rows as $label => $value):
    if ($value === null || $value === '') {
        continue;
    }
    $isLast = false;
?>
    <p style="margin:0 0 8px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;"><?php echo e($label); ?></p>
    <p style="margin:0 0 20px;font-size:14px;line-height:1.5;color:#1d273b;"><?php echo e((string) $value); ?></p>
<?php endforeach; ?>
