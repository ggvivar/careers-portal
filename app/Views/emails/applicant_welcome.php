<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to the JNG Recruitment Hub</title>
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
                            Welcome, <?= esc($applicantName) ?>
                        </h1>

                        <p style="line-height:1.7;">
                            Your applicant account has been created.
                            You can now complete your profile, apply for
                            available positions, and track your applications.
                        </p>

                        <div
                            style="
                                margin:22px 0;
                                padding:18px;
                                background:#f5f6fa;
                                border-radius:10px;
                            "
                        >
                            <div style="font-size:13px;color:#6b7280;">
                                Temporary password
                            </div>

                            <div
                                style="
                                    margin-top:8px;
                                    color:#0a0147;
                                    font-size:20px;
                                    font-weight:bold;
                                    letter-spacing:1px;
                                "
                            >
                                <?= esc($temporaryPassword) ?>
                            </div>
                        </div>

                        <p style="line-height:1.7;">
                            Please sign in and change your temporary password
                            immediately.
                        </p>

                        <p style="margin:28px 0;">
                            <a
                                href="<?= esc($loginUrl) ?>"
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
                                Open Applicant Portal
                            </a>
                        </p>

                        <p
                            style="
                                margin-top:30px;
                                color:#6b7280;
                                font-size:12px;
                                line-height:1.6;
                            "
                        >
                            Do not share your temporary password with anyone.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>