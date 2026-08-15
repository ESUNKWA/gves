<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
</head>

<body style="margin:0; padding:0; background-color:#f5f5f4; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f5f4; padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0"
                    style="background-color:#ffffff; border-radius:12px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#f990a5; padding:24px 32px;">
                            <span style="color:#ffffff; font-size:18px; font-weight:bold;">{{ config('app.name', 'SIRH') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h1 style="margin:0 0 16px; font-size:20px; color:#1c1917;">
                                Votre espace {{ $tenantName }} est prêt
                            </h1>

                            <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#44403c;">
                                Un compte administrateur a été créé pour vous sur le SIRH de
                                <strong>{{ $tenantName }}</strong>. Voici vos identifiants de connexion :
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                style="background-color:#f5f5f4; border-radius:8px; margin:0 0 24px;">
                                <tr>
                                    <td style="padding:16px;">
                                        <p style="margin:0 0 8px; font-size:13px; color:#78716c;">Email</p>
                                        <p style="margin:0 0 16px; font-size:14px; color:#1c1917; font-family:monospace;">{{ $adminEmail }}</p>

                                        <p style="margin:0 0 8px; font-size:13px; color:#78716c;">Mot de passe</p>
                                        <p style="margin:0; font-size:14px; color:#1c1917; font-family:monospace;">{{ $adminPassword }}</p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="border-radius:8px; background-color:#f990a5;">
                                        <a href="{{ $loginUrl }}"
                                            style="display:inline-block; padding:12px 24px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">
                                            Se connecter
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:24px 0 0; font-size:12px; line-height:1.6; color:#a8a29e;">
                                Pensez à changer ce mot de passe après votre première connexion, depuis votre profil.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
