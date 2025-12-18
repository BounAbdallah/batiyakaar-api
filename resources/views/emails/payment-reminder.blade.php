<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .header {
            background-color: #2563eb;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }

        .content {
            padding: 20px;
        }

        .footer {
            font-size: 12px;
            color: #777;
            text-align: center;
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }

        .highlight {
            font-weight: bold;
            color: #2563eb;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            @if(isset($agence) && $agence->logo)
                <img src="{{ $agence->logo_url }}" alt="Logo Agence" style="max-height: 50px; margin-bottom: 10px;">
            @elseif(isset($recipient) && $recipient->agences->first() && $recipient->agences->first()->logo)
                <img src="{{ $recipient->agences->first()->logo_url }}" alt="Logo Agence" style="max-height: 50px; margin-bottom: 10px;">
            @endif
            <h2>{{ $title }}</h2>
        </div>
        <div class="content">
            <p>Bonjour {{ $recipient->prenom }} {{ $recipient->nom }},</p>

            <p>{{ $content }}</p>

            @if(isset($metadata['loyer_attendu']))
                <p>Montant du loyer : <span class="highlight">{{ number_format($metadata['loyer_attendu'], 0, ',', ' ') }}
                        FCFA</span></p>
            @endif

            <p>Veuillez vous connecter à votre espace pour plus de détails.</p>

            <center>
                <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}" class="button">Accéder à mon
                    espace</a>
            </center>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.
        </div>
    </div>
</body>

</html>