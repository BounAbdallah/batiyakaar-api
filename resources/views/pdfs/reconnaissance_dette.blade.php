<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reconnaissance de Dette</title>
    <style>
        @page {
            margin: 15mm 10mm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .title {
            font-size: 13pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
            text-decoration: underline;
        }
        .section {
            margin-bottom: 10px;
        }
        .section p {
            margin: 3px 0;
        }
        .amount-box {
            border: 2px solid #000;
            padding: 8px;
            margin: 10px 0;
            text-align: center;
        }
        .signatures {
            margin-top: 20px;
            width: 100%;
            display: table;
        }
        .col-left {
            float: left;
            width: 48%;
            text-align: center;
        }
        .col-right {
            float: right;
            width: 48%;
            text-align: center;
        }
        .signature-line {
            margin-top: 30px;
            border-top: 1px solid #000;
            width: 150px;
            margin-left: auto;
            margin-right: auto;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
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
    @php
        ini_set('memory_limit', '512M');
    @endphp

    @include('partials.agency_header', ['agence' => $paiement->bail->agence])

    <h1 class="title">RECONNAISSANCE DE DETTE</h1>

    <div class="section">
        <p><strong>Je soussigné(e) :</strong></p>
        <p><strong>Nom :</strong> {{ $paiement->bail->locataire->user->prenom ?? '' }} {{ $paiement->bail->locataire->user->nom ?? '' }}
        @if($paiement->bail->locataire->user->cin) | <strong>CNI :</strong> {{ $paiement->bail->locataire->user->cin }}@endif</p>
        <p><strong>Domicilié(e) à :</strong> {{ $paiement->bail->bien->adresse ?? 'Non renseigné' }}</p>
    </div>

    <div class="section">
        <p><strong>Reconnais devoir à :</strong></p>
        @if($paiement->bail->agence)
            <p><strong>{{ $paiement->bail->agence->raison_sociale }}</strong> - {{ $paiement->bail->agence->adresse }}</p>
            <p>NINEA: {{ $paiement->bail->agence->ninea }} | RC: {{ $paiement->bail->agence->rccm }}</p>
        @else
            <p><strong>{{ $paiement->bail->bien->bailleur->user->prenom ?? '' }} {{ $paiement->bail->bien->bailleur->user->nom ?? '' }}</strong></p>
        @endif
    </div>

    <div class="section">
        <p><strong>La somme de :</strong></p>
        <div class="amount-box">
            <p style="font-size: 12pt; margin: 0; font-weight: bold;">
                {{ number_format($montantDette, 0, ',', ' ') }} FCFA
            </p>
            <p style="font-size: 9pt; margin: 3px 0 0 0;">
                ({{ $montantEnLettres }} francs CFA)
            </p>
        </div>
    </div>

    <div class="section">
        <p><strong>Cette somme correspond à :</strong> Loyers impayés @if($paiement->periode_debut && $paiement->periode_fin)du {{ \Carbon\Carbon::parse($paiement->periode_debut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($paiement->periode_fin)->format('d/m/Y') }}@endif pour le logement situé à <strong>{{ $paiement->bail->bien->adresse ?? '' }}</strong>.</p>
    </div>

    <div class="section">
        <p><strong>Je m'engage à rembourser</strong> ladite somme selon les modalités suivantes :</p>
        <p><strong>Mode de paiement :</strong> [espèces / virement / mobile money] | <strong>Échéance :</strong> _______________</p>
    </div>

    <div class="section">
        <p style="font-size: 9pt;">À défaut de paiement à l'échéance convenue, j'accepte que le créancier engage toute procédure légale prévue par la loi pour le recouvrement de la dette. La présente reconnaissance de dette est établie librement, sans contrainte, pour servir et valoir ce que de droit.</p>
    </div>

    <p style="text-align: right; margin-top: 10px; font-size: 9pt;">Fait à Dakar, le {{ now()->format('d/m/Y') }}</p>

    <div class="signatures clearfix">
        <div class="col-left">
            <p><strong>Le débiteur</strong></p>
            <div class="signature-line"></div>
            <p style="font-size: 8pt; margin-top: 3px;">{{ $paiement->bail->locataire->user->prenom ?? '' }} {{ $paiement->bail->locataire->user->nom ?? '' }}</p>
        </div>
        <div class="col-right">
            <p><strong>Le créancier</strong></p>
            <div class="signature-line"></div>
            <p style="font-size: 8pt; margin-top: 3px;">
                @if($paiement->bail->agence){{ $paiement->bail->agence->raison_sociale }}@else{{ $paiement->bail->bien->bailleur->user->prenom ?? '' }} {{ $paiement->bail->bien->bailleur->user->nom ?? '' }}@endif
            </p>
        </div>
    </div>
</body>
</html>