<?php
/** @var string $fullName */
/** @var string $email */
/** @var string $temporaryPassword */
/** @var string $loginUrl */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AES Project Tracker - Password Reset</title>
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
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Hello <?php echo e($fullName); ?>,</p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#4b5563;">
                                Your password has been reset by the system administrator. You can now log in using the following credentials.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f6f8fb;border:1px solid rgba(101,109,119,0.12);border-radius:6px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:20px 24px;">
                                        <p style="margin:0 0 8px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;">Login URL</p>
                                        <p style="margin:0 0 20px;font-size:14px;line-height:1.5;">
                                            <a href="<?php echo e($loginUrl); ?>" style="color:#206bc4;text-decoration:none;word-break:break-all;"><?php echo e($loginUrl); ?></a>
                                        </p>

                                        <p style="margin:0 0 8px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;">Email</p>
                                        <p style="margin:0 0 20px;font-size:14px;line-height:1.5;color:#1d273b;"><?php echo e($email); ?></p>

                                        <p style="margin:0 0 8px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;">Temporary Password</p>
                                        <p style="margin:0;font-size:16px;line-height:1.5;font-family:Consolas,'Courier New',monospace;font-weight:600;color:#1d273b;letter-spacing:0.5px;"><?php echo e($temporaryPassword); ?></p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#4b5563;">
                                Please change your password after logging in.
                            </p>

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
                                <tr>
                                    <td style="border-radius:6px;background-color:#206bc4;">
                                        <a href="<?php echo e($loginUrl); ?>" style="display:inline-block;padding:12px 24px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">Sign In to AES Tracker</a>
                                    </td>
                                </tr>
                            </table>
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
