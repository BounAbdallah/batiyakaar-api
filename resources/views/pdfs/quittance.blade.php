<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Quittance de Loyer</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 16px;
            color: #555;
        }

        .details {
            margin-bottom: 30px;
            width: 100%;
            border-collapse: collapse;
        }

        .details td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .details td:first-child {
            font-weight: bold;
            width: 40%;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }

        .amount {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }

        .box {
            border: 2px solid #333;
            padding: 20px;
            margin-top: 20px;
        }

        .agency-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .agency-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            color: #2c3e50;
        }

        .agency-details {
            font-size: 10px;
            color: #777;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    @php
        ini_set('memory_limit', '256M');
        $paiement = $quittance->paiementLoyer;
    @endphp

    @if($paiement->bail->agence)
        @include('partials.agency_header', ['agence' => $paiement->bail->agence])
    @endif

    <div class="header">
        <div class="title">QUITTANCE DE LOYER</div>
        <div class="subtitle">N° {{ $quittance->numero_quittance }}</div>
    </div>

    <div class="box">
        <table class="details">
            <tr>
                <td>Période :</td>
                <td>
                    @if($quittance->periode_debut && $quittance->periode_fin)
                        Du {{ \Carbon\Carbon::parse($quittance->periode_debut)->format('d/m/Y') }}
                        au {{ \Carbon\Carbon::parse($quittance->periode_fin)->format('d/m/Y') }}
                    @else
                        {{ \Carbon\Carbon::parse($paiement->date_paiement)->format('F Y') }}
                    @endif
                </td>
            </tr>
            <tr>
                <td>Locataire :</td>
                <td>{{ $paiement->bail->locataire->user->prenom }} {{ $paiement->bail->locataire->user->nom }}</td>
            </tr>
            <tr>
                <td>Adresse du Bien :</td>
                <td>
                    <b>Propriété N° {{ $paiement->bail->bien->reference }}</b><br>
                    {{ $paiement->bail->bien->adresse }} ({{ $paiement->bail->bien->type_bien }})
                </td>
            </tr>
            <tr>
                <td>Bailleur :</td>
                <td>{{ $paiement->bail->bien->bailleur->user->prenom }} {{ $paiement->bail->bien->bailleur->user->nom }}
                </td>
            </tr>
            <tr>
                <td>Date de Paiement :</td>
                <td>{{ \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td>Mode de Paiement :</td>
                <td>{{ ucfirst(str_replace('_', ' ', $paiement->mode_paiement)) }}</td>
            </tr>
            <tr>
                <td>Date d'émission :</td>
                <td>{{ \Carbon\Carbon::parse($quittance->date_emission)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td>Montant Payé :</td>
                <td class="amount">{{ number_format($quittance->montant, 0, ',', ' ') }} FCFA</td>
            </tr>
        </table>

        <div style="margin-top: 20px; text-align: center;">
            <p><strong>Pour valoir ce que de droit.</strong></p>
            <p>Fait le {{ \Carbon\Carbon::parse($quittance->date_emission)->format('d/m/Y') }}
                @if($paiement->bail->agence)
                    par {{ $paiement->bail->agence->raison_sociale }}
                @endif
            </p>
        </div>
    </div>

    <div class="footer">
        <p>Document généré automatiquement par Noor Immo.</p>
    </div>
</body>

</html>