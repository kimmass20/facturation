<?php
// Dépendances du module de consultation des factures.
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-factures.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';

// Comment lire ce fichier :
// 1. il choisit d'abord quel dataset de factures charger selon le rôle,
// 2. il trie ces factures de la plus récente à la plus ancienne,
// 3. puis il les affiche dans un tableau avec un lien vers le détail.
// C'est donc une page de lecture, pas une page de création.

// L'utilisateur doit au minimum pouvoir consulter ses propres factures.
requiert_permission('consulter_propres_factures');

// Titre utilisé dans le header.
$titre_page = 'Liste des factures';

// Le dataset dépend encore une fois du niveau de permission.
if (a_permission('consulter_toutes_factures')) {
    $factures = charger_factures();
    $titre_bloc = 'Toutes les factures';
} else {
    // Pour un caissier, on applique ici une logique d'ownership :
    // il ne voit que les factures qu'il a lui-même créées.
    $factures = obtenir_factures_utilisateur(utilisateur_connecte());
    $titre_bloc = 'Mes factures';
}

// On trie ici les factures de la plus récente à la plus ancienne.
$factures = obtenir_dernieres_factures($factures, count($factures));

// Header partagé.
include __DIR__ . '/../../includes/header.php';
?>

<!-- Titre principal de la vue -->
<h2><i class="fa-solid fa-receipt"></i> <?= htmlspecialchars($titre_bloc) ?></h2>

<!-- Cas vide : aucune facture accessible pour ce profil -->
<?php if (empty($factures)): ?>
    <div class="message-info">
        Aucune facture disponible pour ce profil.
    </div>
<?php else: ?>
    <!-- Tableau des factures accessibles -->
    <div class="tableau-conteneur">
        <table>
            <thead>
                <tr>
                    <th>N° Facture</th>
                    <th>Date</th>
                    <th>Heure</th>
                    <?php if (a_permission('consulter_toutes_factures')): ?>
                    <th>Caissier</th>
                    <?php endif; ?>
                    <th>Total TTC</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Une ligne = une facture -->
                <?php foreach ($factures as $facture): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($facture['id_facture']) ?></strong></td>
                    <td><?= date('d/m/Y', strtotime($facture['date'])) ?></td>
                    <td><?= htmlspecialchars($facture['heure']) ?></td>
                    <?php if (a_permission('consulter_toutes_factures')): ?>
                    <td><?= htmlspecialchars($facture['caissier']) ?></td>
                    <?php endif; ?>
                    <td><?= formater_prix($facture['total_ttc']) ?></td>
                    <td>
                        <!-- Action de drill-down vers le détail de la facture -->
                        <a href="/facturation/modules/facturation/afficher-facture.php?id=<?= urlencode($facture['id_facture']) ?>" class="btn-secondaire">
                            Ouvrir
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Footer partagé -->
<?php include __DIR__ . '/../../includes/footer.php'; ?>