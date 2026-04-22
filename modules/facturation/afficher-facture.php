<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-factures.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';

requiert_role(ROLE_CAISSIER);

$titre_page = 'Afficher facture';

$id_facture = $_GET['id'] ?? '';
$facture = null;

if ($id_facture) {
    $facture = trouver_facture($id_facture);
}

if (!$facture) {
    header('Location: /facturation/index.php');
    exit();
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="facture-affichage">
    <div class="facture-entete">
        <h1><?= NOM_ENTREPRISE ?></h1>
        <p><?= ADRESSE_ENTREPRISE ?></p>
        <p><?= TELEPHONE_ENTREPRISE ?></p>
        <hr style="margin: 1.5rem 0;">
        <h2>FACTURE</h2>
        <p><strong>N° <?= htmlspecialchars($facture['id_facture']) ?></strong></p>
    </div>

    <div class="facture-info">
        <div>
            <p><strong>Date :</strong> <?= date('d/m/Y', strtotime($facture['date'])) ?></p>
            <p><strong>Heure :</strong> <?= htmlspecialchars($facture['heure']) ?></p>
        </div>
        <div>
            <p><strong>Caissier :</strong> <?= htmlspecialchars($facture['caissier']) ?></p>
        </div>
    </div>

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

    <div class="facture-totaux">
        <div class="ligne-total">
            <span>Total HT</span>
            <span><?= formater_prix($facture['total_ht']) ?></span>
        </div>
        <div class="ligne-total">
            <span>TVA (<?= (TAUX_TVA * 100) ?>%)</span>
            <span><?= formater_prix($facture['tva']) ?></span>
        </div>
        <div class="ligne-total total-final">
            <span>Net à payer</span>
            <span><?= formater_prix($facture['total_ttc']) ?></span>
        </div>
    </div>

    <div style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 2px solid #e5e7eb;">
        <p style="font-size: 0.875rem; color: #6b7280;">Merci de votre visite !</p>
        <p style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.5rem;">
            Cette facture a été générée électroniquement le <?= date('d/m/Y à H:i:s') ?>
        </p>
    </div>
</div>

<div style="text-align: center; margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
    <button onclick="window.print()" class="btn-primaire">🖨️ Imprimer</button>
    <a href="/facturation/modules/facturation/nouvelle-facture.php" class="btn-secondaire">+ Nouvelle facture</a>
    <a href="/facturation/index.php" class="btn-secondaire">🏠 Accueil</a>
</div>

<style>
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>
