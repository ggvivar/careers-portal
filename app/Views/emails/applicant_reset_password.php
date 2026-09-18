<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset your password</title>
</head>

<body
    style="
        margin:0;
        padding:0;
        background:#f4f6fa;
        font-family:Arial,sans-serif;
        color:#252538;
    "
>
<table
    role="presentation"
    width="100%"
    cellspacing="0"
    cellpadding="0"
    style="background:#f4f6fa;padding:30px 15px;"
>
    <tr>
        <td align="center">
            <table
                role="presentation"
                width="100%"
                cellspacing="0"
                cellpadding="0"
                style="
                    max-width:620px;
                    background:#ffffff;
                    border-radius:14px;
                    overflow:hidden;
                "
            >
                <tr>
                    <td
                        style="
                            padding:28px 32px;
                            background:#0a0147;
                            color:#ffffff;
                        "
                    >
                        <div style="font-size:23px;font-weight:bold;">
                            Joy~Nostalg Group
                        </div>

                        <div
                            style="
                                margin-top:5px;
                                color:#ffc91c;
                                font-size:15px;
                            "
                        >
                            Recruitment Hub
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:32px;">
                        <h1
                            style="
                                margin:0 0 18px;
                                color:#0a0147;
                                font-size:24px;
                            "
                        >
                            Reset your password
                        </h1>

                        <p style="line-height:1.7;">
                            Hello <?= esc($applicantName) ?>,
                        </p>

                        <p style="line-height:1.7;">
                            We received a request to reset the password for
                            your applicant account.
                        </p>

                        <p style="margin:28px 0;">
                            <a
                                href="<?= esc($resetUrl) ?>"
                                style="
                                    display:inline-block;
                                    padding:13px 22px;
                                    background:#ffc91c;
                                    color:#0a0147;
                                    border-radius:9px;
                                    font-weight:bold;
                                    text-decoration:none;
                                "
                            >
                                Reset Password
                            </a>
                        </p>

                        <p style="line-height:1.7;">
                            This password-reset link will expire in
                            <?= esc($expiresIn) ?>.
                        </p>

                        <p style="line-height:1.7;">
                            If you did not request a password reset, you may
                            safely ignore this email.
                        </p>

                        <p
                            style="
                                margin-top:30px;
                                color:#6b7280;
                                font-size:12px;
                                line-height:1.6;
                            "
                        >
                            For security, do not forward this email or share
                            the password-reset link.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>