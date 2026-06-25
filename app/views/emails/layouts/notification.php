<?php
/** @var string $emailTitle */
/** @var string $greeting */
/** @var string $intro */
/** @var array $rows */
/** @var string|null $message */
/** @var string|null $actionUrl */
/** @var string|null $actionLabel */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($emailTitle); ?></title>
</head>
<body style="margin:0;padding:0;background-color:#f6f8fb;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1d273b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f6f8fb;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background-color:#ffffff;border:1px solid rgba(101,109,119,0.16);border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="background-color:#1d273b;padding:24px 32px;text-align:center;">
                            <table role="presentation" cellspacing="0" cellpadding="0" align="center">
                                <tr>
                                    <td style="vertical-align:middle;padding-right:10px;">
                                        <span style="display:inline-block;width:36px;height:36px;line-height:36px;border-radius:50%;background-color:#206bc4;color:#ffffff;font-size:18px;font-weight:700;text-align:center;">A</span>
                                    </td>
                                    <td style="vertical-align:middle;">
                                        <span style="color:#ffffff;font-size:18px;font-weight:700;letter-spacing:0.2px;">AES Tracker</span>
                                    </td>
                                </tr>
                            </table>
                            <div style="color:#c8d3e0;font-size:12px;margin-top:8px;letter-spacing:0.4px;">AES Project Tracker</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;"><?php echo e($greeting); ?></p>
                            <?php if (!empty($intro)): ?>
                                <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#4b5563;"><?php echo e($intro); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($rows)): ?>
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f6f8fb;border:1px solid rgba(101,109,119,0.12);border-radius:6px;margin-bottom:24px;">
                                    <tr>
                                        <td style="padding:20px 24px;">
                                            <?php require __DIR__ . '/../partials/detail_rows.php'; ?>
                                        </td>
                                    </tr>
                                </table>
                            <?php endif; ?>

                            <?php if (!empty($message)): ?>
                                <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#4b5563;white-space:pre-line;"><?php echo e($message); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($actionUrl) && !empty($actionLabel)): ?>
                                <?php
                                $actionUrl = $actionUrl;
                                $actionLabel = $actionLabel;
                                require __DIR__ . '/../partials/action_button.php';
                                ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;border-top:1px solid rgba(101,109,119,0.12);background-color:#fafbfc;text-align:center;">
                            <p style="margin:0;font-size:13px;color:#6b7280;">Regards,<br><strong style="color:#1d273b;">AES Project Tracker</strong></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
