<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Lettre de Mise en Demeure</title>
    <style>
        @page {
            margin: 15mm 10mm;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .header {
            margin-bottom: 15px;
        }

        .header p {
            margin: 2px 0;
            font-size: 9pt;
        }

        .recipient {
            margin: 15px 0;
            padding-left: 60%;
        }

        .recipient p {
            margin: 2px 0;
            font-size: 9pt;
        }

        .title {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            margin: 20px 0 15px 0;
            text-decoration: underline;
        }

        .section {
            margin-bottom: 10px;
            text-align: justify;
        }

        .section p {
            margin: 5px 0;
        }

        .amount-box {
            border: 2px solid #000;
            padding: 8px;
            margin: 10px 0;
            text-align: center;
            background-color: #f9f9f9;
        }

        .months-list {
            margin: 5px 0;
            padding-left: 20px;
        }

        .months-list li {
            margin: 2px 0;
        }

        .signature {
            margin-top: 30px;
            text-align: right;
        }

        .signature p {
            margin: 3px 0;
            font-size: 9pt;
        }

        strong {
            font-weight: bold;
        }

        .header-logo {
            margin-bottom: 15px;
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .header-logo img {
            max-height: 60px;
            max-width: 200px;
        }
    </style>
</head>

<body>
    @include('partials.agency_header', ['agence' => $bail->agence])

    <div class="header">
        @if($bail->agence)
            <p><strong>{{ $bail->agence->raison_sociale }}</strong></p>
            <p>{{ $bail->agence->adresse }}</p>
            <p>Tél : {{ $bail->agence->user->telephone ?? 'N/A' }} – Email : {{ $bail->agence->user->email ?? 'N/A' }}</p>
            <p>NINEA : {{ $bail->agence->ninea }} | RC : {{ $bail->agence->rccm }}</p>
        @else
            <p><strong>{{ $bail->bien->bailleur->user->prenom ?? '' }} {{ $bail->bien->bailleur->user->nom ?? '' }}</strong>
            </p>
            <p>{{ $bail->bien->adresse ?? '' }}</p>
            <p>Tél : {{ $bail->bien->bailleur->user->telephone ?? 'N/A' }} – Email :
                {{ $bail->bien->bailleur->user->email ?? 'N/A' }}
            </p>
        @endif
    </div>

    <div class="recipient">
        <p>À</p>
        <p><strong>{{ $bail->locataire->user->prenom ?? '' }} {{ $bail->locataire->user->nom ?? '' }}</strong></p>
        <p>{{ $bail->bien->adresse ?? '' }}</p>
    </div>

    <p style="margin: 15px 0 5px 0; font-size: 9pt;"><strong>Objet :</strong> Mise en demeure de paiement de loyers
        impayés</p>

    <h1 class="title">LETTRE DE MISE EN DEMEURE</h1>

    <div class="section">
        <p><strong>Monsieur / Madame,</strong></p>
    </div>

    <div class="section">
        <p>Par la présente, nous vous mettons formellement en demeure de procéder au paiement des loyers impayés du
            logement que vous occupez situé à :</p>
        <p style="text-align: center; margin: 8px 0;"><strong>📍 {{ $bail->bien->adresse ?? 'Non renseigné' }}</strong>
        </p>
    </div>

    <div class="section">
        <p>Conformément au contrat de bail signé le
            <strong>{{ \Carbon\Carbon::parse($bail->date_debut)->format('d/m/Y') }}</strong>, le montant du loyer
            mensuel est fixé à <strong>{{ number_format($bail->loyer_mensuel, 0, ',', ' ') }} FCFA</strong>.
        </p>
    </div>

    <div class="section">
        <p>À ce jour, vous restez redevable de la somme totale de :</p>
        <div class="amount-box">
            <p style="font-size: 12pt; margin: 0; font-weight: bold;">
                {{ number_format($montantTotal, 0, ',', ' ') }} FCFA
            </p>
            <p style="font-size: 9pt; margin: 3px 0 0 0;">
                ({{ $montantEnLettres }} francs CFA)
            </p>
        </div>
    </div>

    @if(!empty($moisImpayés))
        <div class="section">
            <p><strong>Correspondant aux loyers des mois suivants :</strong></p>
            <ul class="months-list">
                @foreach($moisImpayés as $mois)
                    <li>{{ $mois }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="section">
        <p>Nous vous demandons de régulariser cette situation dans un <strong>délai de 15 jours</strong> à compter de la
            réception de la présente.</p>
    </div>

    <div class="section">
        <p>À défaut de paiement dans le délai imparti, nous nous verrons contraints d'engager toute procédure légale
            nécessaire, conformément à la législation en vigueur, sans autre avis.</p>
    </div>

    <div class="section">
        <p>Nous restons toutefois disposés à trouver une solution amiable, notamment un paiement échelonné, si vous nous
            contactez rapidement.</p>
    </div>

    <div class="section" style="margin-top: 15px;">
        <p>Veuillez agréer, Monsieur / Madame, l'expression de nos salutations distinguées.</p>
    </div>

    <div class="signature">
        <p>Fait à Dakar, le {{ now()->format('d/m/Y') }}</p>
        <p style="margin-top: 40px;"><strong>Signature et cachet</strong></p>
        <p style="margin-top: 30px;">
            @if($bail->agence)
                {{ $bail->agence->raison_sociale }}
            @else
                {{ $bail->bien->bailleur->user->prenom ?? '' }} {{ $bail->bien->bailleur->user->nom ?? '' }}
            @endif
        </p>
        <p style="font-size: 8pt;">
            @if($bail->agence)
                (Agence immobilière)
            @else
                (Propriétaire)
            @endif
        </p>
    </div>
</body>

</html>