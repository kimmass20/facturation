<?php
// On charge d'abord les dépendances nécessaires à cette page.
// - session.php : gère la session, les permissions et la protection CSRF.
// - fonctions-auth.php : contient les fonctions liées aux utilisateurs.
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';

// Cette page est réservée aux utilisateurs autorisés à gérer les comptes.
// Si l'utilisateur n'a pas cette permission, l'exécution s'arrête ici.
requiert_permission('gerer_utilisateurs');

// Variable utilisée par le header pour afficher le titre de l'onglet/page.
$titre_page = 'Gestion des comptes';

// Cette variable servira à afficher un message de succès après une action.
$message = '';

// Toute la logique suivante s'exécute seulement lorsqu'un formulaire est envoyé.
// En pratique, cela arrive quand on change un rôle ou qu'on active/désactive un compte.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF : on s'assure que le formulaire vient bien de notre application.
    requiert_csrf();

    // On récupère les données du formulaire.
    // L'opérateur ?? permet de définir une valeur par défaut si la clé n'existe pas.
    $action = $_POST['action'] ?? '';
    $identifiant = trim($_POST['identifiant'] ?? '');

    // Si un identifiant a été envoyé, on cherche le compte correspondant.
    // Sinon, on garde null pour éviter des erreurs plus loin.
    $utilisateur_cible = $identifiant ? trouver_utilisateur($identifiant) : null;

    // On applique ici plusieurs garde-fous importants :
    // 1. le compte cible doit exister
    // 2. on ne peut pas agir sur son propre compte depuis cette page
    // 3. on ne peut pas modifier un super administrateur
    if ($utilisateur_cible && $identifiant !== utilisateur_connecte() && $utilisateur_cible['role'] !== ROLE_SUPER_ADMIN) {
        // Cas n°1 : on veut activer ou désactiver un compte.
        if ($action === 'basculer_statut') {
            // On inverse simplement la valeur actuelle : actif devient inactif, et inversement.
            $nouveau_statut = !$utilisateur_cible['actif'];

            // Si l'enregistrement dans le fichier JSON réussit, on journalise l'action.
            if (definir_statut_utilisateur($identifiant, $nouveau_statut)) {
                journaliser_action('changement_statut_compte', $identifiant, [
                    'ancien_statut' => $utilisateur_cible['actif'] ? 'actif' : 'inactif',
                    'nouveau_statut' => $nouveau_statut ? 'actif' : 'inactif'
                ]);

                // Message affiché en haut de la page après rechargement.
                $message = $nouveau_statut ? 'Compte réactivé avec succès.' : 'Compte désactivé avec succès.';
            }
        }

        // Cas n°2 : on veut changer le rôle du compte ciblé.
        if ($action === 'modifier_role') {
            $nouveau_role = $_POST['nouveau_role'] ?? '';

            // Pour la sécurité, on n'accepte ici que les rôles autorisés.
            // On empêche donc d'injecter une valeur arbitraire depuis le formulaire.
            if (in_array($nouveau_role, [ROLE_CAISSIER, ROLE_MANAGER], true)
                && mettre_a_jour_role_utilisateur($identifiant, $nouveau_role)) {
                journaliser_action('changement_role', $identifiant, [
                    'ancien_role' => $utilisateur_cible['role'],
                    'nouveau_role' => $nouveau_role
                ]);

                // Retour utilisateur après mise à jour réussie.
                $message = 'Rôle utilisateur mis à jour avec succès.';
            }
        }
    }
}

// On charge tous les utilisateurs pour les afficher dans le tableau.
$utilisateurs = charger_utilisateurs();

// Tri par rôle puis par nom.
// L'idée est d'afficher d'abord les rôles les plus élevés, puis de trier alphabétiquement.
usort($utilisateurs, function($a, $b) {
    if ($a['role'] !== $b['role']) {
        // Plus la valeur est grande, plus le rôle est prioritaire dans l'affichage.
        $ordre = [ROLE_SUPER_ADMIN => 3, ROLE_MANAGER => 2, ROLE_CAISSIER => 1];
        return $ordre[$b['role']] - $ordre[$a['role']];
    }

    // Si les rôles sont identiques, on compare les noms complets.
    return strcmp($a['nom_complet'], $b['nom_complet']);
});

// On inclut le header commun de l'application.
include __DIR__ . '/../../includes/header.php';
?>

<!-- Titre principal de la page -->
<h2><i class="fa-solid fa-users-gear"></i> Gestion des comptes utilisateurs</h2>

<!-- Ce bloc n'apparaît que si une action a réussi. -->
<?php if ($message): ?>
    <div class="message-succes">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- Raccourci vers la page de création de compte -->
<div class="mb-2">
    <a href="/facturation/modules/admin/ajouter-compte.php" class="btn-primaire"><i class="fa-solid fa-user-plus"></i> Ajouter un compte</a>
</div>

<!-- Conteneur du tableau des utilisateurs -->
<div class="tableau-conteneur">
    <table>
        <thead>
            <tr>
                <!-- En-têtes de colonnes -->
                <th>Identifiant</th>
                <th>Nom complet</th>
                <th>Rôle</th>
                <th>Date de création</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <!-- On parcourt tous les utilisateurs pour créer une ligne par compte. -->
            <?php foreach ($utilisateurs as $utilisateur): ?>
            <tr>
                <!-- htmlspecialchars protège l'affichage contre l'injection HTML -->
                <td><code><?= htmlspecialchars($utilisateur['identifiant']) ?></code></td>
                <td><strong><?= htmlspecialchars($utilisateur['nom_complet']) ?></strong></td>
                <td>
                    <!-- Le badge change de couleur selon le rôle -->
                    <span class="badge
                        <?= $utilisateur['role'] === ROLE_SUPER_ADMIN ? 'statut-paye' :
                            ($utilisateur['role'] === ROLE_MANAGER ? 'statut-attente' : 'statut-retard') ?>">
                        <?= libelle_role($utilisateur['role']) ?>
                    </span>
                </td>
                <!-- On reformate la date pour un affichage plus lisible en français -->
                <td><?= date('d/m/Y', strtotime($utilisateur['date_creation'])) ?></td>
                <td>
                    <!-- Le statut visuel dépend de la valeur booléenne actif -->
                    <?php if ($utilisateur['actif']): ?>
                        <span style="color: #10b981;">● Actif</span>
                    <?php else: ?>
                        <span style="color: #ef4444;">● Inactif</span>
                    <?php endif; ?>
                </td>
                <td>
                    <!--
                        On n'affiche les actions que si :
                        - ce n'est pas le compte connecté
                        - ce n'est pas un super administrateur
                    -->
                    <?php if ($utilisateur['identifiant'] !== utilisateur_connecte() && $utilisateur['role'] !== ROLE_SUPER_ADMIN): ?>
                        <!-- Formulaire de changement de rôle -->
                        <form method="post" style="display: inline-flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                            <?= champ_csrf() ?>
                            <input type="hidden" name="identifiant" value="<?= htmlspecialchars($utilisateur['identifiant']) ?>">
                            <input type="hidden" name="action" value="modifier_role">
                            <select name="nouveau_role">
                                <option value="<?= ROLE_CAISSIER ?>" <?= $utilisateur['role'] === ROLE_CAISSIER ? 'selected' : '' ?>>Caissier</option>
                                <option value="<?= ROLE_MANAGER ?>" <?= $utilisateur['role'] === ROLE_MANAGER ? 'selected' : '' ?>>Manager</option>
                            </select>
                            <button type="submit" class="btn-secondaire">Changer rôle</button>
                        </form>

                        <!-- Formulaire d'activation / désactivation -->
                        <form method="post" style="display: inline-flex; margin-top: 0.5rem;">
                            <?= champ_csrf() ?>
                            <input type="hidden" name="identifiant" value="<?= htmlspecialchars($utilisateur['identifiant']) ?>">
                            <input type="hidden" name="action" value="basculer_statut">
                            <button type="submit" class="btn-secondaire">
                                <?= $utilisateur['actif'] ? 'Désactiver' : 'Réactiver' ?>
                            </button>
                        </form>

                        <!-- Formulaire de suppression définitive du compte -->
                        <form method="post" action="/facturation/modules/admin/supprimer-compte.php" style="display: inline-flex; margin-top: 0.5rem;">
                            <?= champ_csrf() ?>
                            <input type="hidden" name="identifiant" value="<?= htmlspecialchars($utilisateur['identifiant']) ?>">
                            <button type="submit" class="btn-danger"
                                    onclick="return confirm('Supprimer le compte de <?= htmlspecialchars($utilisateur['nom_complet']) ?> ?')">
                                <i class="fa-solid fa-trash"></i> Supprimer
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Tiret simple si aucune action n'est autorisée sur cette ligne -->
                        <span style="color: #9ca3af;">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Bloc d'aide fonctionnelle pour expliquer les règles de gestion à l'utilisateur -->
<div class="message-info">
    <strong><i class="fa-solid fa-circle-info"></i> Informations :</strong>
    <ul style="margin: 0.5rem 0 0 1.5rem;">
        <li>Vous ne pouvez pas supprimer votre propre compte</li>
        <li>Vous pouvez promouvoir ou rétrograder un compte entre Caissier et Manager</li>
        <li>Vous pouvez désactiver un compte sans le supprimer</li>
        <li>Vous ne pouvez pas supprimer les comptes Super Administrateur</li>
        <li>Seuls les comptes Caissier et Manager peuvent être supprimés</li>
    </ul>
</div>

<!-- Footer commun de l'application -->
<?php include __DIR__ . '/../../includes/footer.php'; ?>
