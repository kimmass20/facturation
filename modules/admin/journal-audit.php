<?php
// Dépendances de sécurité et helpers d'audit.
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';

// Comment lire ce fichier :
// 1. il ne modifie rien, il lit seulement l'historique,
// 2. il récupère les dernières entrées de audit.json via un helper,
// 3. il transforme ensuite ces données en tableau HTML lisible,
// 4. il sert donc surtout d'écran d'observation et de diagnostic.

// Le journal d'audit est réservé à l'administration des comptes.
requiert_permission('gerer_utilisateurs');

// Titre de page et récupération des dernières entrées d'audit.
$titre_page = 'Journal d\'audit';
$entrees_audit = obtenir_dernieres_actions_audit(100);

// 100 est une limite d'affichage pragmatique :
// assez grande pour apprendre et investiguer,
// mais pas trop grande pour éviter un tableau inutilement lourd.

// Header partagé.
include __DIR__ . '/../../includes/header.php';
?>

<!-- Titre principal -->
<h2><i class="fa-solid fa-clipboard-list"></i> Journal d'audit</h2>

<!-- Si aucune action n'a été historisée, on l'indique simplement -->
<?php if (empty($entrees_audit)): ?>
    <div class="message-info">Aucune action sensible enregistrée pour le moment.</div>
<?php else: ?>
    <!-- Tableau des événements d'audit -->
    <div class="tableau-conteneur">
        <table>
            <thead>
                <tr>
                    <th>Date / heure</th>
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                    <th>Action</th>
                    <th>Cible</th>
                    <th>Détails</th>
                </tr>
            </thead>
            <tbody>
                <!-- Une ligne = un événement d'audit -->
                <?php foreach ($entrees_audit as $entree): ?>
                <tr>
                    <td><?= htmlspecialchars($entree['date_heure']) ?></td>
                    <td><?= htmlspecialchars((string) ($entree['utilisateur'] ?? '')) ?></td>
                    <!-- On traduit le rôle technique en libellé lisible -->
                    <td><?= htmlspecialchars(libelle_role((string) ($entree['role'] ?? ''))) ?></td>
                    <td><?= htmlspecialchars((string) ($entree['action'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string) ($entree['cible'] ?? '')) ?></td>
                    <!-- Les détails sont affichés en JSON pour conserver toute l'information contextuelle -->
                    <td><small><?= htmlspecialchars(json_encode($entree['details'] ?? [], JSON_UNESCAPED_UNICODE)) ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Footer partagé -->
<?php include __DIR__ . '/../../includes/footer.php'; ?>