<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kohan Mart Verification Code</title>
</head>
<body style="margin:0; padding:0; background-color:#FAF7F2; font-family:'Helvetica Neue', Arial, sans-serif; color:#2E2A25;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF7F2; padding:36px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:520px; background-color:#FFFFFF; border:1px solid #E2D8C3; border-radius:20px; overflow:hidden; box-shadow:0 6px 24px rgba(46,42,37,0.06);">
                    <!-- Top Matcha Header -->
                    <tr>
                        <td style="background-color:#3F5A45; padding:26px 32px; text-align:center;">
                            <div style="display:inline-block; background-color:#FAF7F2; color:#3F5A45; font-weight:bold; font-size:16px; padding:6px 12px; border-radius:10px; letter-spacing:2px; margin-bottom:10px;">
                                湖畔マート
                            </div>
                            <h1 style="margin:0; color:#FAF7F2; font-family:Georgia, 'Times New Roman', serif; font-size:24px; font-weight:bold; letter-spacing:0.5px;">
                                Kohan Mart
                            </h1>
                            <p style="margin:4px 0 0; color:#D7E4DA; font-size:11px; letter-spacing:2px; text-transform:uppercase;">
                                Japanese Mini Mart • Project 8
                            </p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 12px; font-size:15px; color:#2E2A25;">
                                Irasshaimase, <strong>{{ $userName }}</strong>!
                            </p>
                            <p style="margin:0 0 24px; font-size:14px; line-height:1.6; color:#5C4E42;">
                                Thank you for creating your Kohan Mart account. Enter the 6-digit verification code below to activate your profile and claim your <strong>+20 Welcome Loyalty Points</strong> (worth ₱20.00):
                            </p>

                            <!-- OTP Box -->
                            <div style="background-color:#F3EDE0; border:1.5px dashed #6B4E3D; border-radius:16px; padding:22px; text-align:center; margin-bottom:24px;">
                                <span style="display:block; font-size:10px; text-transform:uppercase; letter-spacing:2.5px; color:#6B4E3D; font-weight:bold; margin-bottom:8px;">
                                    One-Time Verification Code
                                </span>
                                <span style="display:inline-block; font-family:'Courier New', Courier, monospace; font-size:34px; font-weight:bold; letter-spacing:10px; color:#3F5A45; padding-left:10px;">
                                    {{ $otpCode }}
                                </span>
                                <span style="display:block; font-size:11px; color:#7A6B5D; margin-top:10px;">
                                    Valid for 10 minutes • Do not share this code
                                </span>
                            </div>

                            <!-- Perk Callout -->
                            <div style="background-color:#E6EFE8; border-left:4px solid #3F5A45; border-radius:8px; padding:12px 16px; font-size:12px; color:#2B4230; line-height:1.5;">
                                <strong>Member Perks Unlocked:</strong> Earn <strong>0.10 pts per ₱1</strong> on every paid pickup order, redeem points anytime for discounts or free items, and enjoy <strong>2× Double Points</strong> during your birthday month!
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#F3EDE0; border-top:1px solid #E2D8C3; padding:18px 32px; text-align:center; font-size:11px; color:#7A6B5D;">
                            41 Insurance, Project 8, Quezon City, Metro Manila<br>
                            If you didn't request this code, you can safely ignore this email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>