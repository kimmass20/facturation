<?php
// Dépendances principales du tableau de bord : session, produits et factures.
require_once __DIR__ . '/auth/session.php';
require_once __DIR__ . '/includes/fonctions-produits.php';
require_once __DIR__ . '/includes/fonctions-factures.php';

// Le dashboard est réservé aux utilisateurs connectés.
requiert_connexion();

// Titre utilisé dans le header partagé.
$titre_page = 'Accueil';

// Le contenu affiché dépend du rôle et des permissions de l'utilisateur.
$produits = a_permission('gerer_produits') ? charger_produits() : [];

if (a_permission('voir_rapports')) {
    // Les managers et super admins voient les factures globales du jour.
    $factures_jour = obtenir_factures_jour();
} else {
    // Le caissier voit uniquement ses propres factures du jour.
    $factures_jour = obtenir_factures_utilisateur_periode(utilisateur_connecte(), date('Y-m-d'), date('Y-m-d'));
}

// Agrégat statistique utilisé pour les cartes KPI du haut de page.
$stats_jour = calculer_statistiques_factures($factures_jour);

// On prépare une liste de produits à stock faible pour afficher des alertes.
$produits_faible_stock = array_filter($produits, function($p) {
    return $p['quantite_stock'] < 10;
});

// On limite l'affichage aux 5 dernières factures pertinentes.
$dernieres_factures = obtenir_dernieres_factures($factures_jour, 5);

// Inclusion du header commun.
include __DIR__ . '/includes/header.php';
?>

<!-- Hero section : résumé du contexte métier de la journée -->
<section class="page-hero">
    <div class="page-hero-kicker"><i class="fa-solid fa-wave-square"></i> Pilotage commercial</div>
    <h2><i class="fa-solid fa-chart-line"></i> Tableau de bord</h2>
    <p>
        Suivez l'activité de facturation, gardez un oeil sur les produits sensibles et accédez rapidement aux opérations de votre rôle.
    </p>
    <div class="hero-meta">
        <div class="hero-meta-chip"><i class="fa-solid fa-id-badge"></i> Profil actif: <?= htmlspecialchars(libelle_role(role_connecte())) ?></div>
        <div class="hero-meta-chip"><i class="fa-solid fa-calendar-days"></i> Date: <?= date('d/m/Y') ?></div>
        <div class="hero-meta-chip"><i class="fa-solid fa-building"></i> Entreprise: <?= htmlspecialchars(parametre_systeme('nom_entreprise')) ?></div>
    </div>
</section>

<!-- Cette zone KPI n'est visible que pour les rôles ayant accès aux rapports -->
<?php if (a_permission('voir_rapports')): ?>
<div class="statistiques">
    <div class="stat-carte">
        <div class="stat-info">
            <div class="stat-titre">Factures aujourd'hui</div>
            <div class="stat-valeur"><?= $stats_jour['nombre_factures'] ?></div>
        </div>
        <div class="stat-icone stat-icone-bleu"><i class="fa-solid fa-file-invoice"></i></div>
    </div>

    <div class="stat-carte">
        <div class="stat-info">
            <div class="stat-titre">Total HT aujourd'hui</div>
            <div class="stat-valeur"><?= formater_prix($stats_jour['total_ht']) ?></div>
        </div>
        <div class="stat-icone stat-icone-vert"><i class="fa-solid fa-sack-dollar"></i></div>
    </div>

    <div class="stat-carte">
        <div class="stat-info">
            <div class="stat-titre">Total TTC aujourd'hui</div>
            <div class="stat-valeur"><?= formater_prix($stats_jour['total_ttc']) ?></div>
        </div>
        <div class="stat-icone stat-icone-jaune"><i class="fa-solid fa-wallet"></i></div>
    </div>

    <div class="stat-carte">
        <div class="stat-info">
            <div class="stat-titre">Produits en stock</div>
            <div class="stat-valeur"><?= count($produits) ?></div>
        </div>
        <div class="stat-icone stat-icone-orange"><i class="fa-solid fa-box-archive"></i></div>
    </div>
</div>
<?php else: ?>
<!-- Message explicatif destiné au caissier -->
<div class="message-info">
    <strong>Vue Caissier :</strong> vous avez accès à vos ventes du jour et à la création de factures. Les rapports globaux, le stock et la gestion des produits restent réservés au Manager et au Super Administrateur.
</div>
<?php endif; ?>

<!-- Grille principale du dashboard -->
<div class="grille-dashboard">
    <div class="carte">
        <h3><i class="fa-solid fa-bolt"></i> Actions rapides</h3>
        <div class="actions-rapides">
            <!-- Action minimale accessible au caissier -->
            <?php if (a_au_moins_role(ROLE_CAISSIER)): ?>
            <a href="/facturation/modules/facturation/nouvelle-facture.php" class="btn-action-rapide">
                <span class="icone"><i class="fa-solid fa-file-invoice"></i></span>
                <span>Nouvelle Facture</span>
            </a>
            <?php endif; ?>

            <!-- Actions supplémentaires réservées au manager et au super admin -->
            <?php if (a_au_moins_role(ROLE_MANAGER)): ?>
            <a href="/facturation/modules/produits/enregistrer.php" class="btn-action-rapide">
                <span class="icone"><i class="fa-solid fa-pen-to-square"></i></span>
                <span>Enregistrer Produit</span>
            </a>
            <a href="/facturation/modules/produits/liste.php" class="btn-action-rapide">
                <span class="icone"><i class="fa-solid fa-table-list"></i></span>
                <span>Liste Produits</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Carte d'alertes stock uniquement pour les rôles qui peuvent voir le stock -->
    <?php if (a_permission('voir_rapports') && count($produits_faible_stock) > 0): ?>
    <div class="carte">
        <h3><i class="fa-solid fa-triangle-exclamation"></i> Alertes stock</h3>
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

    <!-- Carte de consultation des dernières factures accessibles -->
    <?php if (count($dernieres_factures) > 0): ?>
    <div class="carte">
        <h3><i class="fa-solid <?= a_permission('consulter_toutes_factures') ? 'fa-chart-column' : 'fa-receipt' ?>"></i> <?= a_permission('consulter_toutes_factures') ? 'Dernières factures' : 'Mes dernières factures' ?></h3>
        <table class="tableau-simple">
            <thead>
                <tr>
                    <th>N° Facture</th>
                    <?php if (a_permission('consulter_toutes_factures')): ?>
                    <th>Caissier</th>
                    <?php endif; ?>
                    <th>Heure</th>
                    <th>Montant TTC</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dernieres_factures as $facture): ?>
                <tr>
                    <td>
                        <a href="/facturation/modules/facturation/afficher-facture.php?id=<?= urlencode($facture['id_facture']) ?>">
                            <?= htmlspecialchars($facture['id_facture']) ?>
                        </a>
                    </td>
                    <?php if (a_permission('consulter_toutes_factures')): ?>
                    <td><?= htmlspecialchars($facture['caissier']) ?></td>
                    <?php endif; ?>
                    <td><?= htmlspecialchars($facture['heure']) ?></td>
                    <td><?= formater_prix($facture['total_ttc']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Lien vers la page complète de consultation -->
        <div class="mb-2" style="margin-top: 1rem;">
            <a href="/facturation/modules/facturation/liste-factures.php" class="btn-secondaire">Voir toutes les factures accessibles</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Footer commun -->
<?php include __DIR__ . '/includes/footer.php'; ?>
