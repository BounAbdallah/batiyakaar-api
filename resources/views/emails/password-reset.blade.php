<!DOCTYPE html>
<html>

<head>
    <title>Réinitialisation de mot de passe</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #2563eb; margin: 0;">Noor<span style="color: #1e40af;">Immo</span></h1>
            <p style="color: #666; font-size: 14px;">Gestion Immobilière Simplifiée</p>
        </div>

        <div style="background-color: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b;">
            <h2 style="color: #92400e; margin: 0 0 10px 0;">🔐 Réinitialisation de votre mot de passe</h2>
            <p style="color: #92400e; margin: 0;">Vous avez demandé à réinitialiser votre mot de passe.</p>
        </div>

        <p>Bonjour <strong>{{ $user->prenom }} {{ $user->nom }}</strong>,</p>

        <p>Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte Noor Immo associé à l'adresse <strong>{{ $user->email }}</strong>.</p>

        <p>Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe. Ce lien est valable pendant <strong>60 minutes</strong>.</p>

        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ $resetUrl }}"
                style="display: inline-block; background-color: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">
                Réinitialiser mon mot de passe
            </a>
        </p>

        <div style="background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0 0 8px 0; font-weight: bold;">⚠️ Important</p>
            <p style="margin: 0; font-size: 14px; color: #666;">
                Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet email. Votre mot de passe restera inchangé.<br><br>
                Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :<br>
                <span style="color: #2563eb; word-break: break-all;">{{ $resetUrl }}</span>
            </p>
        </div>

        <p style="font-size: 12px; color: #666; margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 20px; text-align: center;">
            © {{ date('Y') }} Noor Immo - Tous droits réservés<br>
            Gestion Immobilière Simplifiée pour l'Afrique | Par Noor Web Services
        </p>
    </div>
</body>

</html>
