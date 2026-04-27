<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Seu código MadNutz</title>
</head>
<body style="margin:0;padding:0;background:#0a0a0a;font-family:'Helvetica Neue',Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="480" cellpadding="0" cellspacing="0"
                       style="background:#131313;border-radius:16px;overflow:hidden;border:1px solid rgba(255,255,255,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background:#C82830;padding:28px 40px;text-align:center;">
                            <span style="font-size:28px;font-weight:900;color:#fff;
                                         font-style:italic;letter-spacing:-1px;">
                                MadNutz
                            </span>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:40px;text-align:center;">
                            <p style="margin:0 0 8px;color:rgba(255,255,255,0.5);
                                      font-size:13px;text-transform:uppercase;letter-spacing:2px;">
                                Seu código de acesso
                            </p>
                            <div style="font-size:56px;font-weight:900;letter-spacing:12px;
                                        color:#FFDF00;margin:16px 0 24px;">
                                {{ $code }}
                            </div>
                            <p style="margin:0 0 8px;color:rgba(255,255,255,0.7);font-size:15px;line-height:1.6;">
                                Use este código para entrar na sua conta MadNutz.<br>
                                Ele expira em <strong style="color:#fff;">10 minutos</strong>.
                            </p>
                            <p style="margin:24px 0 0;color:rgba(255,255,255,0.3);font-size:12px;">
                                Se não foi você, ignore este e-mail.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:20px 40px;border-top:1px solid rgba(255,255,255,0.06);
                                   text-align:center;">
                            <span style="color:rgba(255,255,255,0.2);font-size:11px;">
                                © {{ date('Y') }} MadNutz — Nuts sem meio-termo.
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
