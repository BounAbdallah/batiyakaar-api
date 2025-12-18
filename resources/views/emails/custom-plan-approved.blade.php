<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Personnalisé Approuvé</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 3px solid #4F46E5;
        }

        .header h1 {
            color: #4F46E5;
            margin: 0;
            font-size: 28px;
        }

        .content {
            padding: 30px 0;
        }

        .plan-details {
            background-color: #F3F4F6;
            border-left: 4px solid #4F46E5;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .plan-details h3 {
            margin-top: 0;
            color: #4F46E5;
        }

        .plan-details p {
            margin: 10px 0;
        }

        .cta-button {
            display: inline-block;
            background-color: #4F46E5;
            color: #ffffff !important;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }

        .cta-button:hover {
            background-color: #4338CA;
        }

        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
            color: #6B7280;
            font-size: 14px;
        }

        .warning {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .features {
            list-style: none;
            padding: 0;
        }

        .features li {
            padding: 5px 0;
            padding-left: 25px;
            position: relative;
        }

        .features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10B981;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Votre Plan Personnalisé est Prêt !</h1>
        </div>

        <div class="content">
            <p>Bonjour {{ $customRequest->prenom }} {{ $customRequest->nom }},</p>

            <p>Excellente nouvelle ! Nous avons créé un plan d'abonnement personnalisé spécialement pour vous, adapté à
                vos besoins spécifiques.</p>

            <div class="plan-details">
                <h3>📦 {{ $plan->nom }}</h3>
                <p><strong>Prix mensuel :</strong> {{ number_format($plan->prix_mensuel, 0, ',', ' ') }} FCFA</p>
                <p><strong>Prix annuel :</strong> {{ number_format($plan->prix_annuel, 0, ',', ' ') }} FCFA <span
                        style="color: #10B981;">(Économisez 10%)</span></p>
                <p><strong>Nombre de biens :</strong> Jusqu'à {{ $plan->limite_biens }} biens</p>
                <p><strong>Nombre d'utilisateurs :</strong> {{ $plan->limite_utilisateurs }} utilisateurs</p>
            </div>

            @if($plan->fonctionnalites && is_array(json_decode($plan->fonctionnalites, true)))
                <h3>✨ Fonctionnalités incluses :</h3>
                <ul class="features">
                    @foreach(json_decode($plan->fonctionnalites, true) as $feature)
                        <li>{{ $feature }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="warning">
                <strong>⚠️ Important :</strong> Ce plan a été créé exclusivement pour vous. Le lien ci-dessous est
                personnel et expire dans <strong>7 jours</strong>. Ne le partagez avec personne.
            </div>

            <div style="text-align: center;">
                <a href="{{ $accessUrl }}" class="cta-button">
                    🚀 Activer Mon Plan Maintenant
                </a>
            </div>

            <p style="margin-top: 30px;">Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur
                :</p>
            <p
                style="background-color: #F3F4F6; padding: 10px; border-radius: 5px; word-break: break-all; font-size: 12px;">
                {{ $accessUrl }}
            </p>

            <p style="margin-top: 30px;">Si vous avez des questions ou besoin d'assistance, n'hésitez pas à nous
                contacter.</p>

            <p>Cordialement,<br>
                <strong>L'équipe Batiyakaar</strong>
            </p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Batiyakaar - Système de gestion immobilière</p>
            <p>Cet email a été envoyé à {{ $customRequest->email }}</p>
        </div>
    </div>
</body>

</html>