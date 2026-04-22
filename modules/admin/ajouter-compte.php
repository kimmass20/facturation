<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';

requiert_role(ROLE_SUPER_ADMIN);

$titre_page = 'Ajouter un compte';

$message = '';
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = trim($_POST['identifiant'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';
    $role = $_POST['role'] ?? '';
    $nom_complet = trim($_POST['nom_complet'] ?? '');

    // Validation
    if (empty($identifiant)) {
        $erreurs[] = "L'identifiant est obligatoire.";
    } elseif (trouver_utilisateur($identifiant)) {
        $erreurs[] = "Cet identifiant existe déjà.";
    }

    if (empty($mot_de_passe)) {
        $erreurs[] = "Le mot de passe est obligatoire.";
    } elseif (strlen($mot_de_passe) < 6) {
        $erreurs[] = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($mot_de_passe !== $confirmation) {
        $erreurs[] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($nom_complet)) {
        $erreurs[] = "Le nom complet est obligatoire.";
    }

    if (!in_array($role, [ROLE_CAISSIER, ROLE_MANAGER])) {
        $erreurs[] = "Rôle invalide.";
    }

    if (empty($erreurs)) {
        if (creer_utilisateur($identifiant, $mot_de_passe, $role, $nom_complet)) {
            header('Location: /facturation/modules/admin/gestion-comptes.php');
            exit();
        } else {
            $erreurs[] = "Erreur lors de la création du compte.";
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<h2>➕ Ajouter un compte utilisateur</h2>

<?php if (!empty($erreurs)): ?>
    <div class="message-erreur">
        <strong>Erreurs :</strong>
        <ul style="margin: 0.5rem 0 0 1.5rem;">
            <?php foreach ($erreurs as $erreur): ?>
                <li><?= htmlspecialchars($erreur) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="carte">
    <form method="post">
        <div class="champ-formulaire">
            <label for="identifiant">Identifiant * (nom d'utilisateur)</label>
            <input type="text" id="identifiant" name="identifiant" required
                   value="<?= htmlspecialchars($_POST['identifiant'] ?? '') ?>">
            <small style="color: #6b7280;">Exemple: jean.dupont</small>
        </div>

        <div class="champ-formulaire">
            <label for="nom_complet">Nom complet *</label>
            <input type="text" id="nom_complet" name="nom_complet" required
                   value="<?= htmlspecialchars($_POST['nom_complet'] ?? '') ?>">
        </div>

        <div class="champ-formulaire">
            <label for="role">Rôle *</label>
            <select id="role" name="role" required>
                <option value="">-- Sélectionnez un rôle --</option>
                <option value="<?= ROLE_CAISSIER ?>" <?= ($_POST['role'] ?? '') === ROLE_CAISSIER ? 'selected' : '' ?>>
                    Caissier
                </option>
                <option value="<?= ROLE_MANAGER ?>" <?= ($_POST['role'] ?? '') === ROLE_MANAGER ? 'selected' : '' ?>>
                    Manager
                </option>
            </select>
        </div>

        <div class="champ-formulaire">
            <label for="mot_de_passe">Mot de passe * (minimum 6 caractères)</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" required minlength="6">
        </div>

        <div class="champ-formulaire">
            <label for="confirmation">Confirmer le mot de passe *</label>
            <input type="password" id="confirmation" name="confirmation" required minlength="6">
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn-primaire">✓ Créer le compte</button>
            <a href="/facturation/modules/admin/gestion-comptes.php" class="btn-secondaire">Annuler</a>
        </div>
    </form>
</div>

<div class="message-info">
    <strong>ℹ️ Permissions des rôles :</strong>
    <ul style="margin: 0.5rem 0 0 1.5rem;">
        <li><strong>Caissier :</strong> Lecture de codes-barres, création et consultation de factures</li>
        <li><strong>Manager :</strong> Toutes les permissions du Caissier + enregistrement de produits, modification du stock, consultation des rapports</li>
    </ul>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
