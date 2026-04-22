<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';

requiert_role(ROLE_MANAGER);

$titre_page = 'Liste des produits';

$produits = charger_produits();

// Tri par nom
usort($produits, function($a, $b) {
    return strcmp($a['nom'], $b['nom']);
});

include __DIR__ . '/../../includes/header.php';
?>

<h2>📋 Liste des produits</h2>

<div class="mb-2">
    <a href="/facturation/modules/produits/enregistrer.php" class="btn-primaire">+ Enregistrer un nouveau produit</a>
</div>

<?php if (empty($produits)): ?>
    <div class="message-info">
        Aucun produit enregistré pour le moment.
    </div>
<?php else: ?>
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
                <?php foreach ($produits as $produit): ?>
                <tr class="<?= $produit['quantite_stock'] < 10 ? 'alerte-stock' : '' ?>">
                    <td><code><?= htmlspecialchars($produit['code_barre']) ?></code></td>
                    <td><strong><?= htmlspecialchars($produit['nom']) ?></strong></td>
                    <td><?= formater_prix($produit['prix_unitaire_ht']) ?></td>
                    <td><?= htmlspecialchars($produit['date_expiration']) ?></td>
                    <td>
                        <?= $produit['quantite_stock'] ?>
                        <?php if ($produit['quantite_stock'] < 10): ?>
                            <span style="color: #f59e0b;">⚠️</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y', strtotime($produit['date_enregistrement'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="message-info">
        <strong>Légende :</strong> Les lignes surlignées en jaune indiquent des produits avec un stock faible (moins de 10 unités).
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
