<!DOCTYPE html>
<html>

<head>
    <title>Nouvelle quittance</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #10b981;">Nouvelle quittance de loyer</h2>

        <p>Bonjour {{ $tenant->prenom }} {{ $tenant->nom }},</p>

        <p>Une nouvelle quittance de loyer a été générée pour vous :</p>

        <div style="background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p><strong>Numéro de quittance :</strong> {{ $quittance->numero_quittance }}</p>
            <p><strong>Période :</strong> {{ \Carbon\Carbon::parse($quittance->periode_debut)->format('d/m/Y') }} -
                {{ \Carbon\Carbon::parse($quittance->periode_fin)->format('d/m/Y') }}</p>
            <p><strong>Montant :</strong> {{ number_format($quittance->montant, 0, ',', ' ') }} FCFA</p>
            <p><strong>Date d'émission :</strong>
                {{ \Carbon\Carbon::parse($quittance->date_emission)->format('d/m/Y') }}</p>
        </div>

        <p>Vous trouverez la quittance en pièce jointe de cet email.</p>

        <p>Vous pouvez également consulter toutes vos quittances depuis votre espace locataire :</p>

        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}/tenant/receipts"
                style="display: inline-block; background-color: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Voir mes quittances
            </a>
        </p>

        <div style="background-color: #d1fae5; border-left: 4px solid #10b981; padding: 12px; margin: 20px 0;">
            <p style="margin: 0; color: #065f46;">
                <strong>✅ Paiement enregistré</strong><br />
                Merci pour votre paiement. Cette quittance atteste de la réception de votre loyer pour la période
                indiquée.
            </p>
        </div>

        <p style="font-size: 12px; color: #666; margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 20px;">
            Conservez précieusement vos quittances, elles constituent une preuve de paiement de votre loyer.
        </p>
    </div>
</body>

</html>