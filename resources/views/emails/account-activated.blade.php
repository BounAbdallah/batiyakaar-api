<!DOCTYPE html>
<html>

<head>
    <title>Votre compte est activé !</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #2563eb; margin: 0;">Noor<span style="color: #1e40af;">Immo</span></h1>
            <p style="color: #666; font-size: 14px;">Gestion Immobilière Simplifiée</p>
        </div>

        <div
            style="background-color: #d1fae5; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; border-left: 4px solid #10b981;">
            <h2 style="color: #065f46; margin: 0 0 10px 0;">✅ Votre compte est activé !</h2>
            <p style="color: #065f46; margin: 0;">Vous pouvez maintenant accéder à votre espace de gestion</p>
        </div>

        <p>Bonjour <strong>{{ $agence->raison_sociale }}</strong>,</p>

        <p>Excellente nouvelle ! Votre compte Noor Immo a été activé avec succès par notre équipe.</p>

        <div
            style="background-color: #dbeafe; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2563eb;">
            <p style="margin-bottom: 10px;"><strong>📦 Votre plan :</strong></p>
            <p style="font-size: 18px; font-weight: bold; color: #1e40af; margin: 10px 0;">{{ $plan->nom }}</p>
            <p style="font-size: 14px; color: #666; margin: 5px 0;">
                ✓ {{ $plan->limite_biens === -1 ? 'Biens illimités' : $plan->limite_biens . ' biens' }}<br>
                ✓
                {{ $plan->limite_utilisateurs === -1 ? 'Utilisateurs illimités' : $plan->limite_utilisateurs . ' utilisateurs' }}
            </p>

            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #93c5fd;">
                <p style="margin-bottom: 10px; font-weight: bold; color: #1e40af;">Vous pouvez maintenant :</p>
                <ul style="margin: 0; padding-left: 20px; color: #374151;">
                    <li style="margin: 5px 0;">✅ Ajouter vos biens immobiliers</li>
                    <li style="margin: 5px 0;">✅ Créer vos contrats de bail</li>
                    <li style="margin: 5px 0;">✅ Enregistrer vos locataires et bailleurs</li>
                    <li style="margin: 5px 0;">✅ Suivre les paiements de loyers</li>
                    <li style="margin: 5px 0;">✅ Générer des quittances et documents</li>
                    <li style="margin: 5px 0;">✅ Accéder aux rapports et statistiques</li>
                </ul>
            </div>
        </div>

        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.frontend_url', 'https://immo.noorwebservices.com') }}/login"
                style="display: inline-block; background-color: #2563eb; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">
                🚀 Accéder à mon espace
            </a>
        </p>



        <div style="background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0 0 10px 0; font-weight: bold;">💡 Besoin d'aide pour démarrer ?</p>
            <p style="margin: 0; font-size: 14px; color: #666;">
                • Consultez notre guide de démarrage rapide<br>
                • Contactez notre support via WhatsApp<br>
                • Regardez nos tutoriels vidéo
            </p>
        </div>

        <p
            style="font-size: 12px; color: #666; margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 20px; text-align: center;">
            © {{ date('Y') }} Noor Immo - Tous droits réservés<br>
            Gestion Immobilière Simplifiée pour l'Afrique | Par Noor Web Services
        </p>
    </div>
</body>

</html>