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

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(0, 0, 0, 0.05);
            white-space: nowrap;
            z-index: -1000;
            user-select: none;
            pointer-events: none;
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
    @endphp

    @if($paiement->bail->agence)
        @if($paiement->bail->agence->logo)
            @php
                $logoSrc = null;
                try {
                    $logoPath = storage_path('app/public/logos/' . $paiement->bail->agence->logo);
                    if (file_exists($logoPath)) {
                        $logoData = file_get_contents($logoPath);
                        if ($logoData !== false) {
                            $logoMime = mime_content_type($logoPath);
                            $logoSrc = 'data:' . $logoMime . ';base64,' . base64_encode($logoData);
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Logo loading error in quittance: " . $e->getMessage());
                    $logoSrc = null;
                }
            @endphp
        @endif

        <div class="watermark">{{ $paiement->bail->agence->raison_sociale }}</div>

        <div class="agency-header">
            @if(isset($logoSrc) && $logoSrc)
                <div style="text-align: center; margin-bottom: 10px;">
                    <img src="{{ $logoSrc }}" alt="Logo" style="max-height: 60px; max-width: 200px;">
                </div>
            @endif
            <div class="agency-name">{{ $paiement->bail->agence->raison_sociale }}</div>
            <div class="agency-details">
                {{ $paiement->bail->agence->adresse }}<br>
                NINEA: {{ $paiement->bail->agence->ninea }} | RCCM: {{ $paiement->bail->agence->rccm }}
            </div>
        </div>
    @endif

    <div class="header">
        <div class="title">QUITTANCE DE LOYER</div>
        <div class="subtitle">N° {{ $paiement->quittance->numero ?? 'PROVISOIRE' }}</div>
    </div>

    <div class="box">
        <table class="details">
            <tr>
                <td>Période :</td>
                <td>
                    @if($paiement->periode_debut && $paiement->periode_fin)
                        Du {{ \Carbon\Carbon::parse($paiement->periode_debut)->format('d/m/Y') }}
                        au {{ \Carbon\Carbon::parse($paiement->periode_fin)->format('d/m/Y') }}
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
                    {{ $paiement->bail->bien->adresse }} ({{ $paiement->bail->bien->type }})
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
                <td>Statut :</td>
                <td>
                    @if($paiement->statut == 'paye')
                        <span style="color: green; font-weight: bold;">PAYÉ</span>
                    @elseif($paiement->statut == 'partiel')
                        <span style="color: orange; font-weight: bold;">PARTIEL</span>
                    @else
                        <span style="color: red; font-weight: bold;">{{ strtoupper($paiement->statut) }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td>Loyer Mensuel :</td>
                <td>{{ number_format($paiement->montant_attendu ?? $paiement->bail->loyer_mensuel, 0, ',', ' ') }} FCFA
                </td>
            </tr>
            <tr>
                <td>Montant Payé :</td>
                <td class="amount">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
            </tr>
            @php
                $montantAttendu = $paiement->montant_attendu ?? $paiement->bail->loyer_mensuel;
                $reste = $montantAttendu - $paiement->montant;
            @endphp
            @if($reste > 0)
                <tr>
                    <td>Reste à payer :</td>
                    <td style="color: red; font-weight: bold;">{{ number_format($reste, 0, ',', ' ') }} FCFA</td>
                </tr>
            @endif
        </table>

        <div style="margin-top: 20px; text-align: center;">
            <p><strong>Pour valoir ce que de droit.</strong></p>
            <p>Fait le {{ now()->format('d/m/Y') }}
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