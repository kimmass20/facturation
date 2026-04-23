<?php
// Dépendances de sécurité et de catalogue produits.
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';

// Cette page est réservée aux rôles pouvant gérer les produits.
requiert_permission('gerer_produits');

// Titre injecté dans le header.
$titre_page = 'Liste des produits';

// Chargement du catalogue complet.
$produits = charger_produits();

// Tri alphabétique par nom pour un affichage plus lisible.
usort($produits, function($a, $b) {
    return strcmp($a['nom'], $b['nom']);
});

// Header partagé.
include __DIR__ . '/../../includes/header.php';
?>

<!-- Titre principal -->
<h2><i class="fa-solid fa-table-list"></i> Liste des produits</h2>

<!-- Action principale de navigation -->
<div class="mb-2">
    <a href="/facturation/modules/produits/enregistrer.php" class="btn-primaire"><i class="fa-solid fa-plus"></i> Enregistrer un nouveau produit</a>
</div>

<!-- Cas vide -->
<?php if (empty($produits)): ?>
    <div class="message-info">
        Aucun produit enregistré pour le moment.
    </div>
<?php else: ?>
    <!-- Tableau du catalogue -->
    <div class="tableau-conteneur">
        <table>
            <thead>
                <tr>
                    <th>Code-barres</th>
                    <th>Nom</th>
                    <th>Prix unitaire HT</th>
                    <th>Date d'expiration</th>
                    <th>Stock</th>
                    <th>Date d'enregistrement</th>
                </tr>
            </thead>
            <tbody>
                <!-- Une ligne = un produit -->
                <?php foreach ($produits as $produit): ?>
                <tr class="<?= $produit['quantite_stock'] < 10 ? 'alerte-stock' : '' ?>">
                    <td><code><?= htmlspecialchars($produit['code_barre']) ?></code></td>
                    <td><strong><?= htmlspecialchars($produit['nom']) ?></strong></td>
                    <td><?= formater_prix($produit['prix_unitaire_ht']) ?></td>
                    <td><?= htmlspecialchars($produit['date_expiration']) ?></td>
                    <td>
                        <?= $produit['quantite_stock'] ?>
                        <?php if ($produit['quantite_stock'] < 10): ?>
                            <span style="color: #f59e0b;"><i class="fa-solid fa-triangle-exclamation"></i></span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y', strtotime($produit['date_enregistrement'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Légende d'interprétation visuelle -->
    <div class="message-info">
        <strong>Légende :</strong> Les lignes surlignées en jaune indiquent des produits avec un stock faible (moins de 10 unités).
    </div>
<?php endif; ?>

<!-- Footer partagé -->
<?php include __DIR__ . '/../../includes/footer.php'; ?>
