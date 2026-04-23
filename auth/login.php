<?php
// On charge la couche session/sécurité et les helpers liés aux utilisateurs.
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../includes/fonctions-auth.php';

// Fast-path : si l'utilisateur a déjà une session valide, on évite de lui montrer le login.
if (est_connecte()) {
    header('Location: /facturation/index.php');
    exit();
}

// Variable d'état pour afficher une erreur fonctionnelle dans le formulaire.
$erreur = '';

// On préremplit le rôle avec Caissier lors du premier affichage.
$role_selectionne = $_POST['role'] ?? ROLE_CAISSIER;

// Ce bloc est le contrôleur de soumission du formulaire de connexion.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sécurisation anti-CSRF.
    requiert_csrf();

    // On récupère les champs du formulaire.
    $identifiant = trim($_POST['identifiant'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $role_selectionne = $_POST['role'] ?? '';

    // Validation serveur : elle reste indispensable même si le HTML contient "required".
    if (empty($identifiant) || empty($mot_de_passe) || empty($role_selectionne)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } elseif (!in_array($role_selectionne, [ROLE_CAISSIER, ROLE_MANAGER, ROLE_SUPER_ADMIN], true)) {
        // On protège le backend contre une valeur de rôle trafiquée dans la requête.
        $erreur = 'Le rôle sélectionné est invalide.';
    } elseif (authentifier_utilisateur($identifiant, $mot_de_passe, $role_selectionne)) {
        // Si l'authentification réussit, on hydrate la session puis on redirige.
        connecter_utilisateur($identifiant);
        header('Location: /facturation/index.php');
        exit();
    } else {
        // Message volontairement générique pour ne pas donner trop d'indices sur l'échec.
        $erreur = 'Identifiant ou mot de passe incorrect.';
    }
}

// Utilisé pour le titre HTML de la page.
$titre_page = 'Connexion';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titre_page ?> - Système de Facturation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/facturation/assets/css/style.css">
</head>
<body>
    <!-- Écran de connexion principal -->
    <div class="login-container">
        <div class="login-box">
            <!-- Bloc d'identité visuelle de l'application -->
            <div class="login-logo">
                <div class="logo-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <h1>Connexion sécurisée</h1>
                <p><?= htmlspecialchars(parametre_systeme('nom_entreprise')) ?> · Plateforme de facturation</p>
            </div>

            <!-- Affichage conditionnel d'une erreur de validation/authentification -->
            <?php if ($erreur): ?>
                <div class="message-erreur">
                    <?= htmlspecialchars($erreur) ?>
                </div>
            <?php endif; ?>

            <!-- Formulaire d'authentification -->
            <form method="post" class="formulaire-login">
                <?= champ_csrf() ?>
                <div class="champ-formulaire">
                    <!-- Le rôle agit ici comme un contexte d'authentification -->
                    <label for="role">Rôle d'authentification</label>
                    <select id="role" name="role" required>
                        <option value="<?= ROLE_CAISSIER ?>" <?= $role_selectionne === ROLE_CAISSIER ? 'selected' : '' ?>>Caissier</option>
                        <option value="<?= ROLE_MANAGER ?>" <?= $role_selectionne === ROLE_MANAGER ? 'selected' : '' ?>>Manager</option>
                        <option value="<?= ROLE_SUPER_ADMIN ?>" <?= $role_selectionne === ROLE_SUPER_ADMIN ? 'selected' : '' ?>>Super Administrateur</option>
                    </select>
                    <small>Sélectionnez le profil exact à ouvrir avant de saisir vos identifiants.</small>
                </div>

                <div class="champ-formulaire">
                    <label for="identifiant">Identifiant</label>
                    <input type="text" id="identifiant" name="identifiant" required autofocus
                           value="<?= htmlspecialchars($_POST['identifiant'] ?? '') ?>">
                </div>

                <div class="champ-formulaire">
                    <label for="mot_de_passe">Mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                </div>

                <!-- CTA principal -->
                <button type="submit" class="btn-primaire btn-block"><i class="fa-solid fa-arrow-right-to-bracket"></i> Se connecter</button>
            </form>

            <!-- Aide de démonstration pour les comptes de test -->
            <div class="login-info">
                <p><strong>Comptes de test :</strong></p>
                <ul>
                    <li><i class="fa-solid fa-user-shield"></i> Super Admin : <code>admin</code> / <code>admin123</code></li>
                    <li><i class="fa-solid fa-user-tie"></i> Manager : <code>manager</code> / <code>manager123</code></li>
                    <li><i class="fa-solid fa-cash-register"></i> Caissier : <code>caissier</code> / <code>caissier123</code></li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
