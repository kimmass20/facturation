<?php
require_once __DIR__ . '/auth/session.php';
require_once __DIR__ . '/includes/fonctions-produits.php';
require_once __DIR__ . '/includes/fonctions-factures.php';

requiert_connexion();

$titre_page = 'Accueil';

// Statistiques
$produits = charger_produits();
$factures_jour = obtenir_factures_jour();
$stats_jour = calculer_statistiques_factures($factures_jour);

// Produits en rupture de stock
$produits_faible_stock = array_filter($produits, function($p) {
    return $p['quantite_stock'] < 10;
});

include __DIR__ . '/includes/header.php';
?>

<h2>Tableau de bord</h2>

<div class="statistiques">
    <div class="stat-carte">
        <div class="stat-info">
            <div class="stat-titre">Factures aujourd'hui</div>
            <div class="stat-valeur"><?= $stats_jour['nombre_factures'] ?></div>
        </div>
        <div class="stat-icone stat-icone-bleu">🧾</div>
    </div>

    <div class="stat-carte">
        <div class="stat-info">
            <div class="stat-titre">Total HT aujourd'hui</div>
            <div class="stat-valeur"><?= formater_prix($stats_jour['total_ht']) ?></div>
        </div>
        <div class="stat-icone stat-icone-vert">💰</div>
    </div>

    <div class="stat-carte">
        <div class="stat-info">
            <div class="stat-titre">Total TTC aujourd'hui</div>
            <div class="stat-valeur"><?= formater_prix($stats_jour['total_ttc']) ?></div>
        </div>
        <div class="stat-icone stat-icone-jaune">💵</div>
    </div>

    <div class="stat-carte">
        <div class="stat-info">
            <div class="stat-titre">Produits en stock</div>
            <div class="stat-valeur"><?= count($produits) ?></div>
        </div>
        <div class="stat-icone stat-icone-orange">📦</div>
    </div>
</div>

<div class="grille-dashboard">
    <div class="carte">
        <h3>🚀 Actions rapides</h3>
        <div class="actions-rapides">
            <?php if (a_au_moins_role(ROLE_CAISSIER)): ?>
            <a href="/facturation/modules/facturation/nouvelle-facture.php" class="btn-action-rapide">
                <span class="icone">🧾</span>
                <span>Nouvelle Facture</span>
            </a>
            <?php endif; ?>

            <?php if (a_au_moins_role(ROLE_MANAGER)): ?>
            <a href="/facturation/modules/produits/enregistrer.php" class="btn-action-rapide">
                <span class="icone">📝</span>
                <span>Enregistrer Produit</span>
            </a>
            <a href="/facturation/modules/produits/liste.php" class="btn-action-rapide">
                <span class="icone">📋</span>
                <span>Liste Produits</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (count($produits_faible_stock) > 0): ?>
    <div class="carte">
        <h3>⚠️ Alertes Stock</h3>
        <table class="tableau-simple">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Stock restant</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produits_faible_stock as $produit): ?>
                <tr class="alerte-stock">
                    <td><?= htmlspecialchars($produit['nom']) ?></td>
                    <td><strong><?= $produit['quantite_stock'] ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (count($factures_jour) > 0): ?>
    <div class="carte">
        <h3>📊 Dernières factures</h3>
        <table class="tableau-simple">
            <thead>
                <tr>
                    <th>N° Facture</th>
                    <th>Heure</th>
                    <th>Montant TTC</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $dernieres_factures = array_slice(array_reverse($factures_jour), 0, 5);
                foreach ($dernieres_factures as $facture):
                ?>
                <tr>
                    <td><?= htmlspecialchars($facture['id_facture']) ?></td>
                    <td><?= htmlspecialchars($facture['heure']) ?></td>
                    <td><?= formater_prix($facture['total_ttc']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
