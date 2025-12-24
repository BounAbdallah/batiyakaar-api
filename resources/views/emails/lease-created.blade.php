<!DOCTYPE html>
<html>

<head>
    <title>Nouveau bail créé</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb;">Nouveau bail créé</h2>

        <p>Bonjour {{ $tenant->prenom }} {{ $tenant->nom }},</p>

        <p>Un nouveau bail a été créé pour vous concernant le bien suivant :</p>

        <div style="background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p><strong>Référence du bien :</strong> {{ $bail->bien->reference }}</p>
            <p><strong>Adresse :</strong> {{ $bail->bien->adresse }}</p>
            <p><strong>Loyer mensuel :</strong> {{ number_format($bail->montant_loyer, 0, ',', ' ') }} FCFA</p>
            <p><strong>Date de début :</strong> {{ \Carbon\Carbon::parse($bail->date_debut)->format('d/m/Y') }}</p>
            <p><strong>Date de fin :</strong> {{ \Carbon\Carbon::parse($bail->date_fin)->format('d/m/Y') }}</p>
        </div>

        <p>Vous trouverez le contrat de bail en pièce jointe de cet email.</p>

        <p>Vous pouvez également consulter votre bail et toutes les informations associées depuis votre espace locataire
            :</p>

        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}/tenant/lease"
                style="display: inline-block; background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Voir mon bail
            </a>
        </p>

        <div style="background-color: #dbeafe; border-left: 4px solid #2563eb; padding: 12px; margin: 20px 0;">
            <p style="margin: 0; color: #1e40af;">
                <strong>📄 Fonctionnalités disponibles :</strong><br />
                • Consulter votre contrat de bail<br />
                • Voir vos quittances de loyer<br />
                • Signaler des incidents<br />
                • Suivre l'historique de vos paiements
            </p>
        </div>

        <p style="font-size: 12px; color: #666; margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 20px;">
            Pour toute question concernant votre bail, n'hésitez pas à contacter votre agence.
        </p>
    </div>
</body>

</html>