<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Note de Dépense</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #0056b3; padding-bottom: 10px; }
        .logo { font-size: 24px; font-weight: bold; color: #0056b3; }
        .title { font-size: 20px; font-weight: bold; margin-top: 10px; }
        .info-section { margin-bottom: 20px; }
        .info-table { width: 100%; }
        .info-table td { padding: 5px; vertical-align: top; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .details-table th, .details-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .details-table th { background-color: #f8f9fa; font-weight: bold; }
        .total-row td { font-weight: bold; background-color: #f8f9fa; }
        .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #777; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">{{ $note->agence->nom ?? 'Immo Plus' }}</div>
        <div class="title">Note de Dépense: {{ $note->numero }}</div>
    </div>

    <div class="info-section">
        <table class="info-table">
            <tr>
                <td style="width: 50%;">
                    <strong>Période:</strong> {{ sprintf('%02d', $note->mois) }} / {{ $note->annee }}<br>
                    <strong>Date d'émission:</strong> {{ $note->created_at->format('d/m/Y') }}<br>
                    <strong>Statut:</strong> {{ ucfirst($note->statut) }}
                </td>
                <td style="width: 50%; text-align: right;">
                    <strong>Bailleur:</strong> {{ $note->bailleur->user->nom ?? '' }} {{ $note->bailleur->user->prenom ?? '' }}<br>
                    <strong>Immeuble:</strong> {{ $note->immeuble->nom ?? 'Tous' }}
                </td>
            </tr>
        </table>
    </div>

    @if($note->description)
    <div style="margin-bottom: 20px;">
        <strong>Description:</strong><br>
        {{ $note->description }}
    </div>
    @endif

    <table class="details-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Désignation</th>
                <th>Catégorie</th>
                <th style="text-align: right;">Montant (XOF)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($note->depenses as $depense)
            <tr>
                <td>{{ \Carbon\Carbon::parse($depense->date_depense)->format('d/m/Y') }}</td>
                <td>{{ $depense->titre }}</td>
                <td>{{ ucfirst($depense->categorie) }}</td>
                <td style="text-align: right;">{{ number_format($depense->montant, 0, ',', ' ') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">TOTAL</td>
                <td style="text-align: right;">{{ number_format($note->total_montant, 0, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Généré par {{ $note->agence->nom ?? 'Noor Immo' }} le {{ date('d/m/Y') }}.
    </div>
</body>
</html>
