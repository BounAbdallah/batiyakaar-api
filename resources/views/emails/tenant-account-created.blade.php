<!DOCTYPE html>
<html>

<head>
    <title>Bienvenue sur la plateforme</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb;">Bienvenue sur {{ $agence->raison_sociale }}</h2>

        <p>Bonjour {{ $tenant->prenom }} {{ $tenant->nom }},</p>

        <p>Votre compte locataire a été créé avec succès par <strong>{{ $agence->raison_sociale }}</strong>.</p>

        <div style="background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p style="margin-bottom: 10px;"><strong>Vos identifiants de connexion :</strong></p>
            <p><strong>Email :</strong> {{ $tenant->email }}</p>
            <p><strong>Mot de passe temporaire :</strong> <code
                    style="background-color: #e5e7eb; padding: 2px 6px; border-radius: 4px;">{{ $password }}</code></p>
        </div>

        <p>Vous pouvez maintenant vous connecter à votre espace locataire pour :</p>
        <ul>
            <li>Consulter votre bail et vos informations de location</li>
            <li>Voir l'historique de vos paiements</li>
            <li>Signaler des incidents ou demandes de maintenance</li>
            <li>Communiquer avec votre agence</li>
        </ul>

        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}/login"
                style="display: inline-block; background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Se connecter
            </a>
        </p>

        <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; margin: 20px 0;">
            <p style="margin: 0; color: #92400e;">
                <strong>⚠️ Sécurité :</strong> Pour votre sécurité, nous vous recommandons fortement de changer votre
                mot de passe lors de votre première connexion.
            </p>
        </div>

        <p style="font-size: 12px; color: #666; margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 20px;">
            Si vous n'avez pas demandé la création de ce compte, veuillez contacter
            <strong>{{ $agence->raison_sociale }}</strong> immédiatement.
        </p>
    </div>
</body>

</html>