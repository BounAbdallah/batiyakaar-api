<!DOCTYPE html>
<html>

<head>
    <title>Bienvenue sur Noor Immo</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #2563eb; margin: 0;">Noor<span style="color: #1e40af;">Immo</span></h1>
            <p style="color: #666; font-size: 14px;">Gestion Immobilière Simplifiée</p>
        </div>

        <h2 style="color: #2563eb;">Merci pour votre abonnement ! 🎉</h2>

        <p>Bonjour <strong>{{ $agence->raison_sociale }}</strong>,</p>

        <p>Nous vous remercions d'avoir choisi Noor Immo pour gérer votre agence immobilière.</p>

        <div
            style="background-color: #dbeafe; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
            <p style="margin-bottom: 10px;"><strong>📦 Votre abonnement :</strong></p>
            <p><strong>Plan :</strong> {{ $plan->nom }}</p>
            <p><strong>Prix :</strong> {{ number_format($plan->prix_mensuel, 0, ',', ' ') }} FCFA/mois</p>
            <p><strong>Limite de biens :</strong> {{ $plan->limite_biens === -1 ? 'Illimité' : $plan->limite_biens }}
            </p>
            <p><strong>Limite d'utilisateurs :</strong>
                {{ $plan->limite_utilisateurs === -1 ? 'Illimité' : $plan->limite_utilisateurs }}</p>
        </div>

        <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; color: #92400e;">
                <strong>⏳ En attente d'activation</strong><br>
                Votre compte est actuellement en attente d'activation par notre équipe. Vous recevrez un email de
                confirmation dès que votre compte sera activé.
            </p>
        </div>

        <p><strong>Une fois votre compte activé, vous pourrez :</strong></p>
        <ul>
            <li>Gérer vos biens immobiliers</li>
            <li>Créer et gérer vos contrats de bail</li>
            <li>Suivre les paiements de loyers</li>
            <li>Générer des quittances automatiquement</li>
            <li>Gérer vos locataires et bailleurs</li>
            <li>Accéder à des rapports détaillés</li>
        </ul>

        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.frontend_url', 'https://immo.noorwebservices.com') }}"
                style="display: inline-block; background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Visiter Noor Immo
            </a>
        </p>

        <div style="background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0; font-size: 14px; color: #666;">
                <strong>💡 Besoin d'aide ?</strong><br>
                Notre équipe est disponible pour vous accompagner. Contactez-nous via WhatsApp ou par email.
            </p>
        </div>

        <p
            style="font-size: 12px; color: #666; margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 20px; text-align: center;">
            © {{ date('Y') }} Noor Immo - Tous droits réservés<br>
            Gestion Immobilière Simplifiée pour l'Afrique
        </p>
    </div>
</body>

</html>