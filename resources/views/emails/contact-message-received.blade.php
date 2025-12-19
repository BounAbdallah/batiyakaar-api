<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Message de Contact</title>
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
            border-bottom: 3px solid #8B5CF6;
        }

        .header h1 {
            color: #8B5CF6;
            margin: 0;
            font-size: 24px;
        }

        .content {
            padding: 30px 0;
        }

        .info-block {
            background-color: #F3F4F6;
            border-left: 4px solid #8B5CF6;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }

        .info-block p {
            margin: 8px 0;
        }

        .info-block strong {
            color: #8B5CF6;
        }

        .message-box {
            background-color: #F9FAFB;
            border: 1px solid #E5E7EB;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
            color: #6B7280;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📧 Nouveau Message de Contact</h1>
        </div>

        <div class="content">
            <p>Vous avez reçu un nouveau message via le formulaire de contact du site Noor-Immo.</p>

            <div class="info-block">
                <p><strong>De :</strong> {{ $contactMessage->prenom }} {{ $contactMessage->nom }}</p>
                <p><strong>Email :</strong> <a
                        href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }}</a></p>
                @if($contactMessage->telephone)
                    <p><strong>Téléphone :</strong> {{ $contactMessage->telephone }}</p>
                @endif
                @if($contactMessage->entreprise)
                    <p><strong>Entreprise :</strong> {{ $contactMessage->entreprise }}</p>
                @endif
                <p><strong>Sujet :</strong> {{ $contactMessage->sujet }}</p>
                <p><strong>Date :</strong> {{ $contactMessage->created_at->format('d/m/Y à H:i') }}</p>
            </div>

            <h3 style="color: #8B5CF6;">Message :</h3>
            <div class="message-box">{{ $contactMessage->message }}</div>

            <p style="margin-top: 30px;">
                <strong>Action requise :</strong> Veuillez répondre à ce message dans les plus brefs délais via
                l'interface d'administration ou directement par email.
            </p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Noor-Immo - Système de gestion immobilière</p>
            <p>Ce message a été envoyé automatiquement depuis le formulaire de contact</p>
        </div>
    </div>
</body>

</html>