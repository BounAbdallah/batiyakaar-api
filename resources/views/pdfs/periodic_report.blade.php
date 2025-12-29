<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Rapport Périodique de Dépenses</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            color: #333;
            line-height: 1.5;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            color: #1e40af;
            text-transform: uppercase;
        }

        .period {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }

        .info-section {
            margin-bottom: 30px;
        }

        .info-grid {
            display: table;
            width: 100%;
        }

        .info-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            color: #4b5563;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: bold;
            text-align: left;
            padding: 10px;
            border: 1px solid #e5e7eb;
        }

        td {
            padding: 10px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .month-header {
            background-color: #eff6ff;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .amount {
            font-family: 'Courier', monospace;
            font-weight: bold;
        }

        .total-row {
            background-color: #f9fafb;
            font-weight: bold;
            font-size: 14px;
        }

        .grand-total {
            background-color: #3b82f6;
            color: white;
            font-size: 16px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(229, 231, 235, 0.4);
            z-index: -1000;
            white-space: nowrap;
            font-weight: bold;
            text-transform: uppercase;
            width: 100%;
            text-align: center;
        }
    </style>
</head>

<body>
    @if($agence)
        <div class="watermark">{{ $agence->raison_sociale }}</div>
    @endif
    @if($agence)
        <div style="margin-bottom: 20px;">
            <strong style="font-size: 16px; color: #1e40af;">{{ $agence->raison_sociale }}</strong><br>
            {{ $agence->adresse }}<br>
            Tél : {{ $agence->user->telephone ?? 'N/A' }} | Email : {{ $agence->user->email ?? 'N/A' }}
        </div>
    @endif

    <div class="header">
        <div class="title">Rapport Périodique de Dépenses</div>
        <div class="period">Période : {{ $period['start'] }} au {{ $period['end'] }}</div>
    </div>

    <div class="info-section">
        <div class="info-grid">
            <div class="info-col">
                <span class="label">Bailleur :</span><br>
                <strong>{{ $bailleur->user->prenom }} {{ $bailleur->user->nom }}</strong><br>
                {{ $bailleur->adresse ?? 'Dakar, Sénégal' }}<br>
                Tél : {{ $bailleur->user->telephone ?? 'N/A' }} | Email : {{ $bailleur->user->email ?? 'N/A' }}
            </div>
            <div class="info-col text-right">
                <span class="label">Date du rapport :</span><br>
                {{ date('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Période / Description</th>
                <th>Catégorie</th>
                <th class="text-right">Montant (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($notes as $note)
                <tr class="month-header">
                    <td colspan="2">
                        {{ date('F Y', mktime(0, 0, 0, $note->mois, 1, $note->annee)) }}
                        @if($note->immeuble) - {{ $note->immeuble->nom }} @endif
                    </td>
                    <td class="text-right">{{ number_format($note->total_montant, 0, ',', ' ') }}</td>
                </tr>
                @foreach($note->depenses as $depense)
                    <tr>
                        <td style="padding-left: 30px;">
                            {{ $depense->titre }}<br>
                            <small style="color: #666;">{{ $depense->description }}
                                ({{ $depense->date_depense->format('d/m/Y') }})</small>
                        </td>
                        <td>{{ str_replace('_', ' ', $depense->categorie) }}</td>
                        <td class="text-right">{{ number_format($depense->montant, 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr class="grand-total">
                <td colspan="2" class="text-right">TOTAL GÉNÉRAL SUR LA PÉRIODE</td>
                <td class="text-right amount">{{ number_format($total_period, 0, ',', ' ') }} FCFA</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Document généré par Noor Immo - Système de Gestion Immobilière
    </div>
</body>

</html>