<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Mandat de Gérance - {{ $immeuble->nom }}</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.3;
            color: #000;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .underline {
            text-decoration: underline;
        }

        .header {
            margin-bottom: 10px;
        }

        .title {
            font-size: 13pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
            text-decoration: underline;
        }

        .section-title {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 8px;
            margin-bottom: 4px;
        }

        .agency-footer {
            margin-top: 25px;
            font-size: 9pt;
            text-align: center;
            border-top: 1px solid #ccc;
            padding-top: 8px;
        }

        .signatures {
            margin-top: 25px;
            width: 100%;
        }

        .col-left {
            float: left;
            width: 45%;
        }

        .col-right {
            float: right;
            width: 45%;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        ul {
            list-style-type: none;
            /* Remove bullets as per text style usually */
            padding-left: 0;
        }

        .checkbox-list {
            margin-left: 20px;
        }

        .header-logo {
            margin-bottom: 10px;
            text-align: center;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }

        .header-logo img {
            max-height: 60px;
            max-width: 200px;
        }

        .property-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 10pt;
        }

        .property-table th,
        .property-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .property-table th {
            background-color: #f9f9f9;
            width: 30%;
            font-weight: bold;
        }

        p {
            margin-top: 0;
            margin-bottom: 6px;
        }
    </style>
</head>

<body>
    @php
        ini_set('memory_limit', '512M');
    @endphp

    @include('partials.agency_header', ['agence' => $immeuble->agence])

    <div class="header text-center" style="margin-top: 20px;">
        <h1 class="title">MANDAT DE GERANCE</h1>
    </div>

    <div class="content">
        <p><strong>Entre les soussignés ; le mandant et le mandataire</strong></p>

        <p>
            <strong>Le mandant :</strong><br>
            @if($immeuble->bailleur && $immeuble->bailleur->user)
                Monsieur/Madame <strong>{{ $immeuble->bailleur->user->prenom }} {{ $immeuble->bailleur->user->nom }}</strong>,
                propriétaire de l'immeuble désigné ci-après
                @if($immeuble->bailleur->user->cin)
                    , titulaire de la CIN N° {{ $immeuble->bailleur->user->cin }}
                @endif
            @else
                Le Propriétaire (Informations manquantes)
            @endif
        </p>

        <p>
            <strong>Le mandataire :</strong><br>
            @if($immeuble->agence)
                l’agence <strong>{{ $immeuble->agence->raison_sociale }}</strong>
                @if($immeuble->agence->user)
                    représenté par son gérant monsieur <strong>{{ $immeuble->agence->user->prenom }}
                        {{ $immeuble->agence->user->nom }}</strong>
                    @if($immeuble->agence->user->cin)
                        titulaire de la CIN N° {{ $immeuble->agence->user->cin }}
                    @endif
                @endif
                administrateur de biens titulaire du NINEA : {{ $immeuble->agence->ninea }}
                et du RC : {{ $immeuble->agence->rccm }}.
            @else
                L'Agence (Informations manquantes)
            @endif
        </p>

        <p><strong>Il a été fait et convenu ce qui suit :</strong></p>

        <p>
            le mandant confère par la présente au mandataire, qui l’accepte, mandat d’administrer le bien
            désigné ci-dessous :
        </p>

        <div style="margin-top: 15px; margin-bottom: 15px;">
            <div class="section-title">DESCRIPTIF DU BIEN</div>
            <table class="property-table">
                <tr>
                    <th>Nom de l'immeuble</th>
                    <td>{{ $immeuble->nom }}</td>
                </tr>
                <tr>
                    <th>Adresse</th>
                    <td>{{ $immeuble->adresse }}</td>
                </tr>
                <tr>
                    <th>Nombre d'étages</th>
                    <td>{{ $immeuble->nombre_etages }}</td>
                </tr>
                <tr>
                    <th>Nombre de lots/appartements</th>
                    <td>{{ $immeuble->nombre_biens ?? 'Non spécifié' }}</td>
                </tr>
                <tr>
                    <th>Type de Mandat</th>
                    <td>
                        @if($immeuble->type_mandat === 'gerance_totale')
                            Gérance Totale (Recouvrement, réparation et rénovation)
                        @elseif($immeuble->type_mandat === 'recouvrement_seulement')
                            Recouvrement Seulement
                        @elseif($immeuble->type_mandat === 'declaration_impots')
                            Déclaration des Impôts
                        @else
                            Non spécifié
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Durée du Contrat</th>
                    <td>
                        {{ $immeuble->duree_mandat ? $immeuble->duree_mandat . ' mois' : 'Indéterminée' }}
                        @if($immeuble->date_debut_mandat && $immeuble->date_fin_mandat)
                            <br>(Du {{ \Carbon\Carbon::parse($immeuble->date_debut_mandat)->format('d/m/Y') }} 
                            au {{ \Carbon\Carbon::parse($immeuble->date_fin_mandat)->format('d/m/Y') }})
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td>{{ $immeuble->description }}</td>
                </tr>
            </table>
        </div>

        <div class="checkbox-list" style="margin-top: 20px;">
            <strong>TYPE DE MISSION :</strong> <br>
            @if($immeuble->type_mandat === 'gerance_totale')
                GERANCE TOTALE (Recouvrement, déclaration impot, réparation et rénovation)
            @elseif($immeuble->type_mandat === 'declaration_impots')
               DECLARATION DES IMPOTS
            @elseif($immeuble->type_mandat === 'recouvrement_seulement')
                RECOUVREMENT SEULEMENT
             @else
               Non spécifié
            @endif
        </div>

        <div class="title" style="font-size: 12pt; margin-top: 20px;">MISSIONS –POUVOIRES</div>
        <p>
            Ce présent mandat autorise expressément le mandataire à accomplir, pour son compte
            et en son nom, tous actes d’administration notamment :
        </p>

        <p><strong>Article 1 :</strong><br>
            Le bailleur dispose des pouvoirs d’effectuer toutes les démarches en vue d’assurer la location
            des locaux de l’immeuble aux tiers, pour la durée, les prix et toutes les charges et conditions
            qu’il jugera convenable en conformité aux lois locatives.</p>

        <p><strong>Article 2 :</strong><br>
            Dès la signature, le bailleur il peut proroger, renouveler, donner et accepter des congés.
            il peut résilier avec ou sans indemnité tous les baux et locations, même ceux actuellement
            en cours et autoriser toues cessions de baux et toutes locations.</p>

        <p><strong>Article 3 :</strong><br>
            Le bailleur peut émettre des états de lieux et exiger des réparations à la charges des locataires.</p>

        <p><strong>Article 4 :</strong><br>
            Le bailleur peut entretenir l’immeuble, procéder à toutes réparations utiles ou rénovation
            avec l’accord du propriétaire.</p>

        <p><strong>Article 5 :</strong><br>
            Le bailleur encaisse les payments mensuels, dresse l’etat des lieux et si necessaire effectue
            toutes réparations découlant des beaux à loyer en donnant des quittances, faire des décharges
            poursuivre les locataires en retard de payment. Il peut également porter plainte pour
            les divergences avec les locataires ou toutes autre dispositions nécessaires à une gérance
            ordonné et rigoureuse, confier le dossier à un avocat et expulser les locataires récalcitrants.</p>

        <p><strong>Article 6 :</strong><br>
            Le propriétaire ne doit prendre aucun engagement avec les locataires au sujet des baux à loyer
            des réparations ou l’utilisation des locaux, ni encaisser les loyers sans avoir informé
            préalablement le bailleur.</p>

        <p><strong>Article 7 :</strong><br>
            Les cautions des locataires sont verser au propriétaire durant toute la durée du mandat signé
            entre les deux parties.</p>

        <p><strong>Article 8 :</strong><br>
            Le bailleur prélève les honoraires calculés sur tous les encaissements.</p>

        <p><strong>Article 9 :</strong><br>
            La feuille de comptabilité de tous les revenus et dépenses sera remise au plus tard le 15
            de chaque mois au propriétaire accompagné du versement mensuel.
            Le propriétaire se reserve le droit de faire des observations étayées par écrit ou tout autre
            moyen de communication. Les positions de comptes et justificatifs sont en permenance
            a la disposition du propriétaire. Idem pour le bailleur qui en cas d’erreur comptable,
            doit en informer le propriétaire et le mode de correction choisit.</p>

        <p><strong>Article 10 :</strong><br>
            Si le propriétaire contracte un crédit auprès du bailleur, il lui cède en même temps une portion
            ou la totalité des payements encaissés jusqu’au règlement intégral de la créance.</p>

        <p><strong>Article 11 :</strong><br>
            Les locataires désireux de payer par virement bancaire le feront sur un compte du bailleur.</p>

        <p><strong>Article 12 :</strong><br>
            Le bailleur n’assume aucune responsabilité des dégâts qui pourraient advenir dans le logement
            par la faute ou la négligence des réparations d’ordre non locatif pour le propriétaire qui gère
            lui-même et ne répond pas du préjudice qui pourrait être causé de ce fait à ce dernier.
            Cependant il répond à la barre en cas de poursuites entamé par le locataire.
            Pour des interventions urgentes de réparations d’installations, le bailleur s’il n’arrive pas
            à joindre le propriétaire, pour des raisons de sécurité des locataires et de leurs biens
            devra intervenir immédiatement sans son avis.</p>

        <p><strong>Article 13 :</strong><br>
            l’agence s’engage à vouer tous les soins à l’exécution du présent mandat et à travailler
            dans l’intérêt du propriétaire, s’occuper d’une location pleine et durable, mais ne pourra
            être rendu responsable des éventuelles pertes dû aux retards ou non-paiement des locations,
            des vacances de logements, pour autant que le dommage soit survenu sans sa faute
            et qu’elle ait voué tous ses soins à l’exécution de son mandat.</p>

        <p><strong>Article 14 :</strong><br>
            les frais d’assignation et honoraires résultant des consultations expulsion etc. sont à la charge
            du propriétaire, pour autant que ceux-ci ne puisse pas être récupérés auprès du locataire.</p>

        <p><strong>Article 15 :</strong><br>
            l’agence fera parvenir à monsieur {{ $immeuble->bailleur->user->prenom ?? '' }}
            {{ $immeuble->bailleur->user->nom ?? '' }} à la fin de chaque mois,
            
            @if($immeuble->date_debut_mandat && $immeuble->date_fin_mandat)
                 pour la période du {{ \Carbon\Carbon::parse($immeuble->date_debut_mandat)->format('d F Y') }} au
                 {{ \Carbon\Carbon::parse($immeuble->date_fin_mandat)->format('d F Y') }},
            @else
                pour une durée d’un (1) an, commençant du {{ now()->startOfYear()->format('d F Y') }} au
                {{ now()->endOfYear()->format('d F Y') }},
            @endif
            un rapport détaillé de la gestion de son immeuble.
        </p>

        <p><strong>Article 16 :</strong><br>
            s’agissant de la gérance, l’agence {{ $immeuble->agence->raison_sociale ?? 'AGENCE' }} percevra à titre
            d’honoraires
            d’agence le taux de {{ $immeuble->taux_commission ?? ($immeuble->agence->taux_commission_agence ?? '10') }}% sur les
            sommes collectés pour
            chaque mois
            le mandat est renouvelable par tacite reconduction.</p>

        <p><strong>Article 17 :</strong><br>
            le préavis de résiliation est de 3 mois pour le propriétaire et 06 mois pour le bailleur,
            chacune des parties pourra mettre fin au bail en avisant l’autre par lettre recommandée,
            avec avis de réception avant l’expiration de chaque période.</p>

        <p><strong>APPLICATION</strong><br>
            Etabli dans les locaux du mandataire en deux exemplaires originaux.<br>
            Un (1) exemplaire pour l’agence {{ $immeuble->agence->raison_sociale ?? '' }}<br>
            Un (1) exemplaire pour monsieur {{ $immeuble->bailleur->user->prenom ?? '' }}
            {{ $immeuble->bailleur->user->nom ?? '' }}
        </p>

        <p class="text-right" style="margin-top: 30px;">
            Fait à Dakar, le {{ now()->format('d F Y') }}
        </p>

        <div class="signatures clearfix">
            <div class="col-left text-center">
                <strong>LE PROPRIETAIRE</strong>
            </div>
            <div class="col-right text-center">
                <strong>LE GERANT</strong>
            </div>
        </div>
    </div>

    <div class="agency-footer">
        @if($immeuble->agence)
            <strong>{{ $immeuble->agence->raison_sociale }}</strong><br>
            {{ $immeuble->agence->adresse }}<br>
            {{ $immeuble->agence->user->telephone ?? '' }}<br>
            N.I.N.E.A : {{ $immeuble->agence->ninea }} / RC : {{ $immeuble->agence->rccm }}
        @endif
    </div>

</body>

</html>