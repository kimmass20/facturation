<?php
// Dépendances métier nécessaires à l'affichage d'une facture.
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-factures.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';

// Cette page demande au minimum l'accès à ses propres factures.
requiert_permission('consulter_propres_factures');

// Titre de page injecté dans le layout commun.
$titre_page = 'Afficher facture';

// L'identifiant de facture est transmis dans la query string : ?id=...
$id_facture = $_GET['id'] ?? '';
$facture = null;

// Si un identifiant a été fourni, on cherche la facture correspondante.
if ($id_facture) {
    $facture = trouver_facture($id_facture);
}

// Double contrôle de sécurité :
// - la facture doit exister
// - l'utilisateur doit réellement avoir le droit de la consulter
if (!$facture || !peut_consulter_facture($facture)) {
    header('Location: /facturation/index.php');
    exit();
}

// Inclusion du header partagé.
include __DIR__ . '/../../includes/header.php';
?>

<!-- Document de facture prêt à l'affichage et à l'impression -->
<div class="facture-affichage">
    <div class="facture-entete">
        <!-- Les coordonnées viennent des paramètres système dynamiques -->
        <h1><?= htmlspecialchars(parametre_systeme('nom_entreprise')) ?></h1>
        <p><?= htmlspecialchars(parametre_systeme('adresse_entreprise')) ?></p>
        <p><?= htmlspecialchars(parametre_systeme('telephone_entreprise')) ?></p>
        <hr style="margin: 1.5rem 0;">
        <h2>FACTURE</h2>
        <p><strong>N° <?= htmlspecialchars($facture['id_facture']) ?></strong></p>
    </div>

    <!-- Métadonnées de la facture -->
    <div class="facture-info">
        <div>
            <p><strong>Date :</strong> <?= date('d/m/Y', strtotime($facture['date'])) ?></p>
            <p><strong>Heure :</strong> <?= htmlspecialchars($facture['heure']) ?></p>
        </div>
        <div>
            <p><strong>Caissier :</strong> <?= htmlspecialchars($facture['caissier']) ?></p>
        </div>
    </div>

    <!-- Tableau détaillé des articles vendus -->
    <table class="facture-tableau">
        <thead>
            <tr>
                <th>Désignation</th>
                <th style="text-align: right;">Prix unit. HT</th>
                <th style="text-align: right;">Qté</th>
                <th style="text-align: right;">Sous-total HT</th>
            </tr>
        </thead>
        <tbody>
            <!-- Une ligne = un article du snapshot de facture -->
            <?php foreach ($facture['articles'] as $article): ?>
            <tr>
                <td><?= htmlspecialchars($article['nom']) ?></td>
                <td style="text-align: right;"><?= formater_prix($article['prix_unitaire_ht']) ?></td>
                <td style="text-align: right;"><?= $article['quantite'] ?></td>
                <td style="text-align: right;"><?= formater_prix($article['sous_total_ht']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totaux comptables -->
    <div class="facture-totaux">
        <div class="ligne-total">
            <span>Total HT</span>
            <span><?= formater_prix($facture['total_ht']) ?></span>
        </div>
        <div class="ligne-total">
            <span>TVA (<?= (float) parametre_systeme('taux_tva') * 100 ?>%)</span>
            <span><?= formater_prix($facture['tva']) ?></span>
        </div>
        <div class="ligne-total total-final">
            <span>Net à payer</span>
            <span><?= formater_prix($facture['total_ttc']) ?></span>
        </div>
    </div>

    <!-- Bas de facture / pied de ticket -->
    <div style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 2px solid #e5e7eb;">
        <p style="font-size: 0.875rem; color: #6b7280;">Merci de votre visite !</p>
        <p style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.5rem;">
            Cette facture a été générée électroniquement le <?= date('d/m/Y à H:i:s') ?>
        </p>
    </div>
</div>

<!-- Actions secondaires : impression, nouvelle facture, retour accueil -->
<div style="text-align: center; margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
    <button onclick="window.print()" class="btn-primaire"><i class="fa-solid fa-print"></i> Imprimer</button>
    <a href="/facturation/modules/facturation/nouvelle-facture.php" class="btn-secondaire"><i class="fa-solid fa-plus"></i> Nouvelle facture</a>
    <a href="/facturation/index.php" class="btn-secondaire"><i class="fa-solid fa-house"></i> Accueil</a>
</div>

<style>
/* Petit bloc CSS local dédié à l'impression de la facture */
@media print {
    .navbar, .footer, button, a.btn-secondaire {
        display: none !important;
    }

    .container {
        max-width: 100%;
        padding: 0;
    }

    .facture-affichage {
        box-shadow: none;
        border: 1px solid #000;
    }
}
</style>

<!-- Footer partagé -->
<?php include __DIR__ . '/../../includes/footer.php'; ?>
