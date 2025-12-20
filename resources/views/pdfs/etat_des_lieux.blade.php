<!DOCTYPE html>
<html lang="fr">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>État des Lieux</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            text-transform: uppercase;
            font-weight: bold;
            font-size: 14px;
            letter-spacing: 2px;
        }

        .section {
            margin-bottom: 15px;
            border: 1px solid #000;
            padding: 10px;
        }

        .row {
            display: table;
            width: 100%;
            border-bottom: 1px dashed #ccc;
            padding: 5px 0;
        }

        .last-row {
            border-bottom: none;
        }

        .col {
            display: table-cell;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            margin-right: 5px;
        }

        .entete-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .entete-table td {
            padding: 5px;
            vertical-align: top;
            border: 1px solid #000;
        }

        .counters-table,
        .keys-table,
        .rooms-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9px;
        }

        .counters-table th,
        .counters-table td,
        .keys-table th,
        .keys-table td,
        .rooms-table th,
        .rooms-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
        }

        .rooms-table th {
            background-color: #eee;
            text-align: center;
        }

        .signature-section {
            margin-top: 30px;
        }

        .signature-box {
            width: 45%;
            display: inline-block;
            vertical-align: top;
            height: 100px;
            border: 1px dashed #000;
            padding: 10px;
        }

        .signature-box.right {
            float: right;
        }

        .dotted-line {
            border-bottom: 1px dotted #000;
            display: inline-block;
            width: 80%;
        }

        /* Helpers */
        .w-50 {
            width: 50%;
        }

        .w-20 {
            width: 20%;
        }

        .w-10 {
            width: 10%;
        }

        .text-center {
            text-align: center;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .bold {
            font-weight: bold;
        }
    </style>
</head>

<body>

    @include('partials.agency_header', ['agence' => $etat->bail->agence])

    <div class="header">
        É T A T &nbsp; D E S &nbsp; L I E U X
    </div>

    <!-- Dates Entrée / Sortie -->
    <table class="entete-table">
        <tr>
            <td width="50%">
                <span class="bold">ENTRÉE</span><br>
                DATE D’ENTRÉE : {{ $etat->type == 'entrant' ? $etat->date_etat_des_lieux->format('d/m/Y') : '' }}
            </td>
            <td width="50%">
                <span class="bold">SORTIE</span><br>
                DATE DE SORTIE : {{ $etat->type == 'sortant' ? $etat->date_etat_des_lieux->format('d/m/Y') : '' }}
            </td>
        </tr>
    </table>

    <!-- Identification -->
    <table class="entete-table">
        <tr>
            <td>
                <span class="label">Adresse du local :</span>
                {{ $etat->bail->bien->adresse ?? '..........................................................' }}
                <br>
                {{ $etat->bail->bien->immeuble->nom ?? '' }}
                {{ $etat->bail->bien->etage ? '- ' . $etat->bail->bien->etage->nom : '' }}
            </td>
        </tr>
        <tr>
            <td>
                <table width="100%">
                    <tr>
                        <td width="50%" style="border:none; vertical-align: top;">
                            <span class="label">Le bailleur (ou son mandataire) :</span><br>
                            Nom :
                            {{ $etat->bail->agence->raison_sociale ?? ($etat->bail->agence->user->name ?? '...........................') }}<br>
                            Adresse :
                            {{ $etat->bail->agence->adresse ?? '..........................................................' }}
                        </td>
                        <td width="50%" style="border:none; vertical-align: top;">
                            <span class="label">Le locataire :</span><br>
                            Nom : {{ $etat->bail->locataire->user->prenom ?? '' }}
                            {{ $etat->bail->locataire->user->nom ?? '...........................' }}<br>
                            Domicile :
                            {{ $etat->bail->locataire->adresse ?? '..........................................................' }}<br>
                            Nouvelle adresse : .....................................................
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Compteurs -->
    <div class="bold" style="margin-bottom: 5px;">ELECTRICITÉ - EAU - INTERNET</div>
    <table class="counters-table">
        <tr>
            <th width="33%">IDENTIFICATION</th>
            <th width="33%">COMPTEURS</th>
            <th width="33%">Relevé / N°</th>
        </tr>
        @php
            $compteurs = $etat->content['compteurs'] ?? [];
            $types = ['electricite' => 'Electricité', 'eau' => 'Eau', 'internet' => 'Internet'];
        @endphp
        @foreach($types as $key => $label)
            <tr>
                <td>{{ $label }}</td>
                <td>{{ $compteurs[$key]['numero'] ?? '................' }}</td>
                <td>{{ $compteurs[$key]['index'] ?? '................' }}</td>
            </tr>
        @endforeach
    </table>

    <!-- Clés -->
    <div class="bold" style="margin-top: 15px; margin-bottom: 5px;">CLÉS</div>
    <table class="keys-table">
        <tr>
            <th width="40%">TYPE DE CLÉ</th>
            <th width="10%">NBRE</th>
            <th width="50%">COMMENTAIRE</th>
        </tr>
        @php
            $cles = $etat->content['cles'] ?? [];
            $defaultKeys = [
                'entree' => 'Clés porte d’entrée',
                'cave' => 'Clés cave',
                'parking' => 'Parking',
                'immeuble' => 'Clés d’immeuble',
                'boite' => 'Boîtes aux lettres',
                'portail' => 'Badge ou clé portail'
            ];
        @endphp
        @foreach($defaultKeys as $key => $label)
            <tr>
                <td>{{ $label }}</td>
                <td>{{ $cles[$key]['nombre'] ?? '' }}</td>
                <td>{{ $cles[$key]['commentaire'] ?? '' }}</td>
            </tr>
        @endforeach
        <tr>
            <td>Autre : .......................</td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <!-- État des pièces -->
    <div class="bold" style="margin-top: 15px; margin-bottom: 5px;">ÉTAT DES PIÈCES</div>
    <div style="font-size: 8px; margin-bottom: 5px;">Légende : (M)auvais - (P)assable - (B)on - (T)rès (B)on</div>

    <table class="rooms-table">
        <thead>
            <tr>
                <th width="20%">Pièce / Élément</th>
                <th width="30%">Description / Détails</th>
                <th width="5%">ETAT (E)</th>
                <th width="20%">Description / Détails (Sortie)</th>
                <th width="5%">ETAT (S)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $pieces = $etat->content['pieces'] ?? [];
                // If empty, we can generate some default rows or loops if needed, 
                // but assuming the controller passes valid structure or we leave blank lines.
                // For the PDF template, we'll iterate provided pieces or show generic blanks if none.
                $defaultPieces = $pieces ?: [
                    ['nom' => 'Pièce 1 (Ex: Salon)', 'elements' => ['Porte', 'Mur', 'Sol', 'Plafond', 'Fenetres', 'Prises']],
                    ['nom' => 'Pièce 2 (Ex: Cuisine)', 'elements' => ['Porte', 'Mur', 'Sol', 'Plafond', 'Evier', 'Placards']]
                ];
            @endphp

            @foreach($defaultPieces as $piece)
                <tr style="background-color: #f9f9f9;">
                    <td colspan="5" class="bold">{{ $piece['nom'] ?? 'Pièce' }}</td>
                </tr>
                @foreach($piece['elements'] ?? [] as $element)
                    @php
                        $isString = is_string($element);
                        $nom = $isString ? $element : ($element['nom'] ?? $element[0] ?? 'Élément');
                        $entreeCom = $isString ? '' : ($element['etat_entree_commentaire'] ?? '');
                        $entreeNote = $isString ? '' : ($element['etat_entree_note'] ?? '');
                        $sortieCom = $isString ? '' : ($element['etat_sortie_commentaire'] ?? '');
                        $sortieNote = $isString ? '' : ($element['etat_sortie_note'] ?? '');
                    @endphp
                    <tr>
                        <td style="padding-left: 15px;">{{ $nom }}</td>
                        <td>{{ $entreeCom }}</td>
                        <td class="text-center">{{ $entreeNote }}</td>
                        <td>{{ $sortieCom }}</td>
                        <td class="text-center">{{ $sortieNote }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <!-- Observations -->
    <div class="section" style="height: 100px;">
        <span class="label">Observations ou réserves :</span><br>
        {{ $etat->observations }}
        <br><br>.............................................................................................................................................................................
        <br>.............................................................................................................................................................................
    </div>

    <!-- Signatures -->
    <div class="signature-section">
        <div class="signature-box">
            <span class="bold">Le Bailleur</span><br>
            <span style="font-size: 9px;">(Date et Signature)</span>
            <br><br><br>
            @if($etat->type == 'entrant' && $etat->date_etat_des_lieux)
                {{ $etat->date_etat_des_lieux->format('d/m/Y') }}
            @endif
        </div>
        <div class="signature-box right">
            <span class="bold">Le Locataire</span><br>
            <span style="font-size: 9px;">(Date et Signature)</span>
            <br><br><br>
            @if($etat->type == 'entrant' && $etat->date_etat_des_lieux)
                {{ $etat->date_etat_des_lieux->format('d/m/Y') }}
            @endif
        </div>
    </div>

</body>

</html>