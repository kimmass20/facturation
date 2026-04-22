<?php
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../includes/fonctions-auth.php';

// Si déjà connecté, redirige vers l'accueil
if (est_connecte()) {
    header('Location: /facturation/index.php');
    exit();
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = trim($_POST['identifiant'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if (empty($identifiant) || empty($mot_de_passe)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } elseif (authentifier_utilisateur($identifiant, $mot_de_passe)) {
        connecter_utilisateur($identifiant);
        header('Location: /facturation/index.php');
        exit();
    } else {
        $erreur = 'Identifiant ou mot de passe incorrect.';
    }
}

$titre_page = 'Connexion';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titre_page ?> - Système de Facturation</title>
    <link rel="stylesheet" href="/facturation/assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1>🔐 Connexion</h1>
            <h2><?= NOM_ENTREPRISE ?></h2>

            <?php if ($erreur): ?>
                <div class="message-erreur">
                    <?= htmlspecialchars($erreur) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="formulaire-login">
                <div class="champ-formulaire">
                    <label for="identifiant">Identifiant</label>
                    <input type="text" id="identifiant" name="identifiant" required autofocus
                           value="<?= htmlspecialchars($_POST['identifiant'] ?? '') ?>">
                </div>

                <div class="champ-formulaire">
                    <label for="mot_de_passe">Mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                </div>

                <button type="submit" class="btn-primaire btn-block">Se connecter</button>
            </form>

            <div class="login-info">
                <p><strong>Comptes de test :</strong></p>
                <ul>
                    <li>Super Admin : <code>admin</code> / <code>admin123</code></li>
                    <li>Manager : <code>manager</code> / <code>manager123</code></li>
                    <li>Caissier : <code>caissier</code> / <code>caissier123</code></li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
