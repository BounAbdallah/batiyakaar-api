<!DOCTYPE html>
<html>

<head>
    <title>Invitation à rejoindre l'équipe</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb;">Bienvenue chez {{ $manager->agence->raison_sociale ?? 'Bâti Yakaar' }}</h2>

        <p>Bonjour {{ $user->prenom }} {{ $user->nom }},</p>

        <p>{{ $manager->prenom }} {{ $manager->nom }} vous a invité(e) à rejoindre leur équipe sur la plateforme Bâti
            Yakaar.</p>

        <div style="background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p style="margin-bottom: 10px;">Voici vos identifiants de connexion :</p>
            <p><strong>Email :</strong> {{ $user->email }}</p>
            <p><strong>Mot de passe temporaire :</strong> {{ $password }}</p>
        </div>

        <p>Connectez-vous dès maintenant pour accéder à votre espace de travail :</p>

        <p style="text-align: center;">
            <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}/login"
                style="display: inline-block; background-color: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Se connecter
            </a>
        </p>

        <p style="font-size: 12px; color: #666; margin-top: 30px;">
            Pour des raisons de sécurité, nous vous recommandons de changer votre mot de passe après votre première
            connexion.
        </p>
    </div>
</body>

</html>