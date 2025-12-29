<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rapport Mensuel de Gestion - {{ $month }} {{ $year }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }

        .container {
            padding: 30px;
        }

        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header table {
            width: 100%;
        }

        .agency-info {
            width: 50%;
        }

        .report-title {
            width: 50%;
            text-align: right;
        }

        .report-title h1 {
            color: #2563eb;
            margin: 0;
            font-size: 24px;
        }

        .info-section {
            margin-bottom: 30px;
        }

        .info-section table {
            width: 100%;
        }

        .info-box {
            border: 1px solid #e5e7eb;
            padding: 15px;
            border-radius: 5px;
        }

        .stats-grid {
            margin-bottom: 30px;
        }

        .stats-grid table {
            width: 100%;
            border-collapse: collapse;
        }

        .stat-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            text-align: center;
            border-radius: 5px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }

        .stat-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.data-table th {
            background-color: #f1f5f9;
            color: #475569;
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        table.data-table td {
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        .section-title {
            background-color: #2563eb;
            color: white;
            padding: 5px 10px;
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: bold;
        }

        .footer {
            position: absolute;
            bottom: 30px;
            left: 30px;
            right: 30px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }

        .summary-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 20px;
            margin-top: 30px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .summary-total {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
            border-top: 2px solid #bfdbfe;
            padding-top: 10px;
            margin-top: 10px;
        }

        .badge {
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
        }

        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background-color: #fef9c3;
            color: #854d0e;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(226, 232, 240, 0.4);
            z-index: -1000;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <div class="watermark">{{ $agence->raison_sociale ?? 'BATIYAKAAR' }}</div>

    <div class="container">
        <div class="header">
            <table>
                <tr>
                    <td class="agency-info">
                        <strong>{{ $agence->raison_sociale }}</strong><br>
                        {{ $agence->user->telephone ?? '' }}<br>
                        {{ $agence->user->email ?? '' }}<br>
                        {{ $agence->adresse ?? '' }}
                    </td>
                    <td class="report-title">
                        <h1>RAPPORT MENSUEL</h1>
                        <strong>{{ $month }} {{ $year }}</strong>
                    </td>
                </tr>
            </table>
        </div>

        <div class="info-section">
            <table>
                <tr>
                    <td style="width: 50%; padding-right: 10px;">
                        <div class="info-box">
                            <strong>PROPRIÉTAIRE</strong><br>
                            {{ $bailleur->user->prenom }} {{ $bailleur->user->nom }}<br>
                            Tel: {{ $bailleur->user->telephone }}<br>
                            Email: {{ $bailleur->user->email }}
                        </div>
                    </td>
                    <td style="width: 50%; padding-left: 10px;">
                        <div class="info-box">
                            <strong>RÉSUMÉ FINANCIER</strong><br>
                            Recettes nettes: {{ number_format($stats['total_received'], 0, ',', ' ') }} FCFA<br>
                            Honoraire de Gestion ({{ $stats['commission_rate'] }}%):
                            {{ number_format($stats['total_commission'], 0, ',', ' ') }} FCFA<br>
                            Dépenses: {{ number_format($stats['total_expenses'], 0, ',', ' ') }} FCFA
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section-title">DÉTAILS DES ENCAISSEMENTS</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Bien</th>
                    <th>Locataire</th>
                    <th>Date</th>
                    <th>Montant</th>
                    <th>Commission</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->bail->bien->reference }}</td>
                        <td>{{ $payment->bail->locataire->user->prenom }} {{ $payment->bail->locataire->user->nom }}</td>
                        <td>{{ \Carbon\Carbon::parse($payment->date_paiement)->format('d/m/Y') }}</td>
                        <td>{{ number_format($payment->montant, 0, ',', ' ') }} F</td>
                        <td>
                            {{ number_format($payment->ventilation->montant_agence ?? ($payment->calculated_commission ?? 0), 0, ',', ' ') }}
                            F
                        </td>
                        <td>
                            <span class="badge {{ $payment->statut === 'paye' ? 'badge-success' : 'badge-warning' }}">
                                {{ strtoupper($payment->statut) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center;">Aucun encaissement pour cette période.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(count($missing_payments) > 0)
            <div class="section-title">LOYERS IMPAYÉS (ATTENDUS)</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Bien</th>
                        <th>Locataire</th>
                        <th>Loyer Mensuel</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($missing_payments as $missing)
                        <tr>
                            <td>{{ $missing['bien'] }}</td>
                            <td>{{ $missing['locataire'] }}</td>
                            <td>{{ number_format($missing['montant'], 0, ',', ' ') }} F</td>
                            <td><span class="badge" style="background-color: #fee2e2; color: #991b1b;">IMPAYÉ</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($expenses->count() > 0)
            <div class="section-title">DÉPENSES ET CHARGES</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Réf/Date</th>
                        <th>Description</th>
                        <th>Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenses as $note)
                        <tr>
                            <td>{{ $note->numero }}</td>
                            <td>{{ $note->description }}</td>
                            <td>{{ number_format($note->total_montant, 0, ',', ' ') }} F</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="summary-box">
            <table style="width: 100%;">
                <tr>
                    <td>Total Encaissé locataires :</td>
                    <td style="text-align: right;">{{ number_format($stats['total_received'], 0, ',', ' ') }} FCFA</td>
                </tr>
                <tr>
                    <td>Honoraires de Gestion Agence ({{ $stats['commission_rate'] }}%) :</td>
                    <td style="text-align: right;">- {{ number_format($stats['total_commission'], 0, ',', ' ') }} FCFA
                    </td>
                </tr>
                <tr>
                    <td>Total Dépenses Propriétaire :</td>
                    <td style="text-align: right;">- {{ number_format($stats['total_expenses'], 0, ',', ' ') }} FCFA
                    </td>
                </tr>
                <tr class="summary-total">
                    <td>SOLDE NET À REVERSER :</td>
                    <td style="text-align: right;">{{ number_format($stats['balance'], 0, ',', ' ') }} FCFA</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            Généré le {{ date('d/m/Y à H:i') }} | Rapport de Gestion Noor Immo 
        </div>
    </div>
</body>

</html>