<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Périodique des Dépenses</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; color: #333; }
        .header { text-align: center; margin-bottom: 24px; border-bottom: 2px solid #0056b3; padding-bottom: 12px; }
        .logo { font-size: 22px; font-weight: bold; color: #0056b3; }
        .title { font-size: 18px; font-weight: bold; margin-top: 8px; }
        .subtitle { font-size: 13px; color: #555; margin-top: 4px; }
        .meta { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .meta td { padding: 4px 8px; vertical-align: top; }
        .section-title { font-size: 14px; font-weight: bold; color: #0056b3; margin: 18px 0 6px; border-bottom: 1px solid #cce; padding-bottom: 4px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.items th { background: #f0f4ff; color: #333; padding: 7px 8px; text-align: left; border: 1px solid #dde; font-size: 12px; }
        table.items td { padding: 6px 8px; border: 1px solid #eee; font-size: 12px; }
        table.items tr.subtotal td { background: #f8f9fa; font-weight: bold; }
        .total-box { text-align: right; margin-top: 16px; font-size: 15px; font-weight: bold; color: #0056b3; border-top: 2px solid #0056b3; padding-top: 8px; }
        .footer { text-align: center; margin-top: 40px; font-size: 11px; color: #999; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">{{ $agence->nom ?? 'Noor Immo' }}</div>
        <div class="title">Rapport Périodique des Dépenses</div>
        <div class="subtitle">
            Période : {{ sprintf('%02d', $request->start_month) }}/{{ $request->start_year }}
            — {{ sprintf('%02d', $request->end_month) }}/{{ $request->end_year }}
        </div>
    </div>

    <table class="meta">
        <tr>
            <td style="width:50%">
                <strong>Bailleur :</strong> {{ $bailleur->user->prenom ?? '' }} {{ $bailleur->user->nom ?? '' }}<br>
                <strong>Email :</strong> {{ $bailleur->user->email ?? '' }}<br>
                <strong>Tél :</strong> {{ $bailleur->user->telephone ?? '—' }}
            </td>
            <td style="width:50%; text-align:right">
                <strong>Agence :</strong> {{ $agence->nom ?? '—' }}<br>
                <strong>Date de génération :</strong> {{ date('d/m/Y') }}
            </td>
        </tr>
    </table>

    @if($notes->isEmpty())
        <p style="color:#888; font-style:italic; text-align:center; margin-top:40px;">
            Aucune dépense enregistrée pour cette période.
        </p>
    @else
        @foreach($notes as $note)
            <div class="section-title">
                Note {{ $note->numero }} — {{ sprintf('%02d', $note->mois) }}/{{ $note->annee }}
                @if($note->immeuble) — {{ $note->immeuble->nom }} @elseif($note->bien) — {{ $note->bien->reference }} @endif
            </div>

            @if($note->description)
                <p style="margin:0 0 6px; font-size:12px; color:#555;">{{ $note->description }}</p>
            @endif

            <table class="items">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Désignation</th>
                        <th>Catégorie</th>
                        <th style="text-align:right">Montant (XOF)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($note->depenses as $d)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($d->date_depense)->format('d/m/Y') }}</td>
                        <td>{{ $d->titre }}</td>
                        <td>{{ ucfirst($d->categorie ?? '—') }}</td>
                        <td style="text-align:right">{{ number_format($d->montant, 0, ',', ' ') }}</td>
                    </tr>
                    @endforeach
                    <tr class="subtotal">
                        <td colspan="3" style="text-align:right">Sous-total</td>
                        <td style="text-align:right">{{ number_format($note->total_montant, 0, ',', ' ') }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach

        <div class="total-box">
            TOTAL GÉNÉRAL : {{ number_format($totalGeneral, 0, ',', ' ') }} XOF
        </div>
    @endif

    <div class="footer">
        Généré par {{ $agence->nom ?? 'Noor Immo' }} le {{ date('d/m/Y à H:i') }}.
    </div>
</body>
</html>
