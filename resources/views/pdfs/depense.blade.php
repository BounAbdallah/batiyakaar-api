<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Note de Dépense - {{ $note->numero }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 13px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid #333;
            display: inline-block;
            padding-bottom: 5px;
        }

        .info-section {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 5px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            width: 120px;
            color: #555;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .items-table th {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }

        .items-table td {
            border: 1px solid #ddd;
            padding: 10px;
            font-size: 12px;
        }

        .text-right {
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            background-color: #fafafa;
        }

        .amount {
            font-weight: bold;
            color: #2c3e50;
        }

        .footer {
            margin-top: 40px;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 60px;
            color: rgba(229, 231, 235, 0.4);
            z-index: -1000;
            white-space: nowrap;
            font-weight: bold;
            text-transform: uppercase;
            width: 100%;
            text-align: center;
        }

        .signature-section {
            margin-top: 40px;
            width: 100%;
        }

        .signature-box {
            width: 45%;
            display: inline-block;
            text-align: center;
        }

        .sig-line {
            margin-top: 50px;
            border-top: 1px dashed #999;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>

<body>
    @if($note->agence)
        <div class="watermark">{{ $note->agence->raison_sociale }}</div>
    @endif
    @if($note->agence)
        @include('partials.agency_header', ['agence' => $note->agence])
    @endif

    <div class="header">
        <div class="title">NOTE DE DÉPENSES GROUPÉES</div>
        <p style="margin-top: 5px;">N° {{ $note->numero }} | Période :
            {{ date('F Y', mktime(0, 0, 0, $note->mois, 1, $note->annee)) }}
        </p>
    </div>

    <table class="info-table">
        <tr>
            <td width="50%">
                <div class="label">Bailleur :</div>
                {{ $note->bailleur->user->prenom }} {{ $note->bailleur->user->nom }}
            </td>
            <td width="50%">
                <div class="label">Immeuble :</div>
                {{ $note->immeuble->nom ?? 'N/A' }}
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="label">Description :</div>
                {{ $note->description ?? 'Pas de description supplémentaire' }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Désignation</th>
                <th>Catégorie</th>
                <th>Date</th>
                <th class="text-right">Montant (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($note->depenses as $item)
                <tr>
                    <td>{{ $item->titre }}<br><small style="color: #666;">{{ $item->description }}</small></td>
                    <td>{{ str_replace('_', ' ', $item->categorie) }}</td>
                    <td>{{ $item->date_depense->format('d/m/Y') }}</td>
                    <td class="text-right">{{ number_format($item->montant, 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL GÉNÉRAL</td>
                <td class="text-right amount">{{ number_format($note->total_montant, 0, ',', ' ') }} FCFA</td>
            </tr>
        </tfoot>
    </table>

    <div class="signature-section">
        <div class="signature-box" style="float: left;">
            <p><strong>L'Agence</strong></p>
            <div class="sig-line"></div>
            <p>(Cachet et Signature)</p>
        </div>
        <div class="signature-box" style="float: right;">
            <p><strong>Le Bailleur</strong></p>
            <div class="sig-line"></div>
            <p>(Signature pour acquit)</p>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="footer">
        <p>Document généré par Noor Immo le {{ date('d/m/Y à H:i') }}</p>
        <p>&copy; {{ date('Y') }} - {{ $note->agence->raison_sociale ?? 'Batiyakaar' }}</p>
    </div>
</body>

</html>