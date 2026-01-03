<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Contrat de Bail - {{ $bail->bien->reference ?? 'N/A' }}</title>
    <style>
        @page {
            margin: 15mm 20mm;
        }

        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #2c3e50;
        }

        .top-header {
            display: table;
            width: 100%;
            margin-bottom: 25px;
            border-bottom: 3px solid #2c5aa0;
            padding-bottom: 15px;
        }

        .logo-section {
            display: table-cell;
            width: 35%;
            vertical-align: middle;
        }

        .logo-section img {
            max-height: 65px;
            max-width: 180px;
        }

        .agency-info {
            display: table-cell;
            width: 65%;
            vertical-align: middle;
            text-align: right;
            font-size: 9.5pt;
            color: #2c5aa0;
        }

        .agency-info .agency-name {
            font-size: 13pt;
            font-weight: bold;
            color: #1a4d8f;
            margin-bottom: 3px;
        }

        .contract-title {
            background-color: #f8f9fa;
            border-left: 5px solid #2c5aa0;
            padding: 15px 20px;
            margin: 20px 0;
        }

        .contract-title h1 {
            font-size: 15pt;
            font-weight: bold;
            color: #1a4d8f;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .parties-header {
            background-color: #e8f1f8;
            padding: 10px 15px;
            margin: 20px 0 15px 0;
            border-radius: 3px;
            font-weight: bold;
            color: #1a4d8f;
            font-size: 12pt;
        }

        .party-block {
            margin: 12px 0;
            padding-left: 10px;
            line-height: 1.7;
        }

        .agreement-text {
            text-align: center;
            font-weight: bold;
            font-size: 11.5pt;
            margin: 25px 0;
            padding: 12px;
            background-color: #f8f9fa;
            border-top: 2px solid #2c5aa0;
            border-bottom: 2px solid #2c5aa0;
            color: #1a4d8f;
        }

        .article {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }

        .article-title {
            font-weight: bold;
            color: #1a4d8f;
            font-size: 11.5pt;
            margin-bottom: 8px;
            padding-left: 5px;
            border-left: 4px solid #2c5aa0;
            background-color: #f8f9fa;
            padding: 8px 10px;
        }

        .article-content {
            margin-left: 15px;
            text-align: justify;
        }

        .highlight {
            font-weight: bold;
            color: #1a4d8f;
        }

        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-date {
            text-align: right;
            font-style: italic;
            margin-bottom: 30px;
            color: #555;
        }

        .signature-boxes {
            display: table;
            width: 100%;
            margin-top: 20px;
        }

        .signature-box {
            display: table-cell;
            width: 48%;
            text-align: center;
            padding: 20px 10px;
            border: 1px solid #ddd;
            background-color: #fafafa;
        }

        .signature-box.left {
            margin-right: 4%;
        }

        .signature-label {
            font-weight: bold;
            color: #1a4d8f;
            font-size: 11pt;
            margin-bottom: 50px;
        }

        strong {
            color: #333;
        }
    </style>
</head>

<body>
    @php
        ini_set('memory_limit', '512M');
    @endphp

    @include('partials.agency_header', ['agence' => $bail->agence])

    {{-- Contract Title --}}
    <div class="contract-title">
        <h1>
            BAIL À USAGE D'HABITATION
            @if($bail->type_duree === 'determinee')
                À DURÉE DÉTERMINÉE
            @else
                À DURÉE INDÉTERMINÉE
            @endif
        </h1>
    </div>

    {{-- Parties --}}
    <div class="parties-header">ENTRE LES SOUSSIGNÉS</div>

    <div class="party-block">
        @if($bail->bien->bailleur->user)
            <strong>Monsieur/Madame {{ $bail->bien->bailleur->user->prenom }}
                {{ $bail->bien->bailleur->user->nom }}</strong>
            propriétaire de l’immeuble sis à {{ $bail->bien->adresse }}
        @else
            <strong>Le Propriétaire</strong>
        @endif

        @if($bail->agence)
            <br>ici représenté par l’agence <strong>{{ $bail->agence->raison_sociale }}</strong>
            sis à {{ $bail->agence->adresse }}
        @endif
        </p>

        <p>
            <strong>Et monsieur/madame {{ $bail->locataire->user->prenom }} {{ $bail->locataire->user->nom }}</strong>
            @if($bail->locataire->user->date_naissance)
                né(e) le {{ \Carbon\Carbon::parse($bail->locataire->user->date_naissance)->locale('fr')->isoFormat('DD MMMM YYYY') }}
                @if($bail->locataire->user->lieu_naissance)
                    à {{ $bail->locataire->user->lieu_naissance }}
                @endif
            @endif
            @if($bail->locataire->user->cin)
                <br>et titulaire de la CIN numéro {{ $bail->locataire->user->cin }}.
            @endif
            <br>Téléphone: {{ $bail->locataire->user->telephone }}
        </p>

        <p class="text-center font-bold" style="margin-top: 20px;">IL A ÉTÉ CONVENU ET ARRÊTE CE QUI SUIT :</p>

        <div class="article">
            <div class="section-title">ARTICLE 1: OBJET DE DÉSIGNATION</div>
            <p>
                Monsieur/Madame {{ $bail->locataire->user->prenom }} {{ $bail->locataire->user->nom }} accepte de
                prendre en location le local mis à sa disposition par le bailleur.
                <br>Les locaux du présent bail se situent à {{ $bail->bien->adresse }} et se présentent comme suit :
                <br>{{ $bail->bien->description ?? $bail->bien->type . ' de ' . $bail->bien->surface . 'm²' }}
                {{-- Note: Ideally we would have a detailed description field for "2 Chambres ...", using Description or
                Type for now --}}
            </p>
            <p>Monsieur/Madame {{ $bail->locataire->user->prenom }} {{ $bail->locataire->user->nom }} accepte
                parfaitement les lieux.</p>
        </div>

        <div class="article">
            <div class="section-title">ARTICLE 2: LOYER</div>
            <p>
                Le présent bail est consenti moyennant un loyer mensuel de
                <strong>{{ number_format($bail->loyer_mensuel, 0, ',', ' ') }} F CFA TTC</strong>.
                Le loyer est payable d’avance au plus tard le 05 du mois en cours
                @if($bail->agence)
                    au siège de l’agence {{ $bail->agence->raison_sociale }}.
                @else
                    au domicile du bailleur.
                @endif
            </p>
            <p>Le prix du loyer sera corrigé à l’inflation tous les deux ans avec une majoration de 2.5%.</p>
        </div>

        <div class="article">
            <div class="section-title">ARTICLE 3: RÉSOLUTION JUDICIAIRE DU BAIL</div>
            <p>
                Au cas où le loyer ne sera pas versé par le preneur à la date convenue entre les deux parties,
                le bail sera résilié de plein droit si bon semble au bailleur après avoir notifié le preneur
                par une simple lettre de mise en demeure ou un acte extrajudiciaire.
                <br>La juridiction compétente sera saisie par le bailleur pour constater la résiliation du bail
                et ordonner l’expulsion du preneur et de tous occupants de son chef.
            </p>
        </div>

        <div class="article">
            <div class="section-title">ARTICLE 4: CAUTION</div>
            <p>
                Le preneur devra verser entre les mains du bailleur, avant son entrée en jouissance des locaux,
                une somme non productible d'intérêt de <strong>{{ number_format($bail->caution, 0, ',', ' ') }} F
                    CFA</strong>
                représentant la caution. Cette caution sera payée avant l'entrée en possession des lieux.
            </p>
            <p>
                Cette caution est restituable en fin de bail au preneur sous réserve des conditions ci-après :
                <br>a) Le preneur doit restituer les lieux loués en parfaite état locatif et rendre les clefs au
                bailleur.
                <br>b) Il devra apporter au bailleur, les justificatifs de la résiliation des contrats d'abonnement
                à l'électricité et à la fourniture d'eau et au téléphone.
            </p>
            <p>
                Le preneur devra garnir et tenir constamment les lieux par du mobilier et des matériels
                ou marchandises en qualité et de Valeur suffisantes pour répondre en tout temps du paiement
                des loyers et des charges du bail.
                <br>Le preneur devra également prendre toutes les dispositions pour amener la sécurité de son
                installation et de ses activités conformément à la règlementation y affèrent.
            </p>
        </div>

        <div class="article">
            <div class="section-title">ARTICLE 5: PRISE D’EFFET ET PRÉAVIS</div>
            <p><strong>Prise d’effet :</strong></p>
            <p>
                Le bail est conclu pour une durée indéterminée et prend effet le
                <strong>{{ \Carbon\Carbon::parse($bail->date_debut)->locale('fr')->isoFormat('DD MMMM YYYY') }}</strong>.
                Le renouvellement se fera après concertation des deux parties.
            </p>
            <p><strong>Préavis :</strong></p>
            <p>
                Chacune des parties pourra y mettre fin en prévenant l'autre par lettre recommandée ou par
                voie extrajudiciaire, deux mois à l'avance pour le preneur et six mois à l'avance pour le bailleur.
                <br>Le preneur n'est en aucun cas tenu de donner les motifs de son départ.
                <br>Le preneur devra respecter la durée du contrat et ne peut pas résilier le bail de façon abusive.
            </p>
        </div>

        <div class="article">
            <div class="section-title">ARTICLE 6: REMISE EN ÉTAT DES LIEUX</div>
            <p>
                Le preneur reconnait par la présente, prendre les lieux en très bon état locative et s’engage
                sous réserve des dispositions de l’article sept (6) à les rendre à son état primitif
                au moment de son départ. Un état des lieux contradictoire sera établi entre les parties
                aussi bien à l’entrée qu’à la sortie des lieux du preneur.
            </p>
        </div>

        <div class="article">
            <div class="section-title">ARTICLE 7: TRANSFORMATION DES LIEUX</div>
            <p>
                Le preneur ne pourra faire aucun aménagement ou travaux sans l’accord ou le consentement
                exprès du bailleur. Tout embellissement ou amélioration accordé par le bailleur au preneur
                reviendra de plein droit au bailleur à la fin du bail.
            </p>
        </div>

        <div class="article">
            <div class="section-title">ARTICLE 8: RÉPARATION ENTRETIEN</div>
            <p>
                Le preneur est tenu de veiller sur et d’effectuer les réparations d’entretien des lieux loues.
                Il répond des dégradations ou des pertes dues à un défaut d’entretien au cours du bail.
            </p>
        </div>

        <div class="article">
            <div class="section-title">ARTICLE 9: GROSSES RÉPARATIONS</div>
            <p>
                Le bailleur est tenu de procéder à ses frais dans les locaux remis en bail a toutes les grosses
                réparations devenues nécessaires et urgentes. Ces réparations sont celles des grands murs,
                des voutes, des toitures, affaissements, étanchéité, des murs de soutènement et des fosses
                septiques. Lorsque le bailleur refuse ou tarde à faire procéder à l’exécution des travaux requis,
                le preneur peut, après avoir informé et présenté le devis estimatif au bailleur, faire réaliser
                lui-même les dits travaux.
                Les frais générés par ceux-ci seront intégralement déduits des loyers à échoir.
            </p>
        </div>

        <div class="article">
            <div class="section-title">ARTICLE 10: PRESCRIPTIONS ADMINISTRATIVES</div>
            <p>
                Le preneur devra satisfaire à toutes les prescriptions des services de police, de voierie
                et d’hygiène auxquelles les locations sont ordinairement astreintes et de les respecter
                afin qu’aucun recours ne puisse être exercé contre le bailleur ou le propriétaire des locaux
                remis à bail.
                <br>La sous location est formellement interdite.
                <br>Le locataire accepte de ne pas vivre en nombre pléthorique, et de ne pas faire de tapages
                nocturnes qui puisse nuire le voisinage.
            </p>
        </div>

        <div class="article">
            <div class="section-title">ARTICLE 11: CONCLUSION</div>
            <p>
                Compétence est expressément donnée au juge des référés du tribunal commercial de Dakar
                pour prononcer la résiliation du bail.
                <br>Les frais, les timbres et l’enregistrement sont à la charge du locataire.
                <br>Aucune commission ne sera remboursée en cas de désistement.
            </p>
        </div>

        <div class="signature-section">
            <div class="signature-date">
                Fait à Dakar, le {{ \Carbon\Carbon::parse($bail->created_at)->locale('fr')->isoFormat('DD MMMM YYYY') }}
            </div>

            <div class="signature-boxes">
                <div class="signature-box" style="float: left; width: 45%; margin-right: 10%;">
                    <div class="signature-label">Le Bailleur</div>
                    <div style="border-top: 1px solid #999; margin-top: 60px; padding-top: 5px;">
                        Signature et cachet
                    </div>
                </div>
                <div class="signature-box" style="float: right; width: 45%;">
                    <div class="signature-label">Le Preneur</div>
                    <div style="border-top: 1px solid #999; margin-top: 60px; padding-top: 5px;">
                        Signature
                    </div>
                </div>
                <div style="clear: both;"></div>
            </div>
        </div>

</body>

</html>