<?php
// On démarre la session uniquement si ce n'est pas déjà fait ailleurs.
if (session_status() === PHP_SESSION_NONE) session_start();

// On charge les constantes globales et les helpers d'authentification.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/fonctions-auth.php';

// Le nom de l'entreprise vient des paramètres système modifiables par l'admin.
$nom_entreprise = parametre_systeme('nom_entreprise');

// On récupère le chemin courant pour savoir quel lien de navigation doit être actif.
$chemin_courant = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

function classe_nav_active($chemin_courant, $segments) {
    // Cette fonction joue le rôle de helper de routing visuel.
    foreach ($segments as $segment) {
        if (strpos($chemin_courant, $segment) !== false) {
            return ' active';
        }
    }

    return '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titre_page) ? $titre_page . ' — ' : '' ?><?= htmlspecialchars($nom_entreprise) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/facturation/assets/css/style.css">
</head>
<body>
<?php if (est_connecte()): ?>
<!-- La navbar visible n'affiche ici que la barre supérieure et le bouton burger. -->
<nav class="navbar" data-navbar>
    <div class="navbar-brand-row">
        <div class="navbar-brand">
            <h1><i class="fa-solid fa-bolt"></i> <?= htmlspecialchars($nom_entreprise) ?></h1>
        </div>

        <!-- Le burger pilote le drawer de navigation via JavaScript dans le footer. -->
        <button type="button" class="navbar-toggle hamburger" data-navbar-toggle aria-expanded="false" aria-label="Ouvrir le menu de navigation">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</nav>

<!-- Drawer latéral contenant les liens métier et la zone utilisateur -->
<div class="navbar-panel" data-navbar-panel>
    <ul class="navbar-menu">
        <li>
            <a href="/facturation/index.php" class="nav-link<?= $chemin_courant === '/facturation/index.php' ? ' active' : '' ?>">
                <i class="fa-solid fa-house"></i> Accueil
            </a>
        </li>

        <?php if (a_permission('creer_facture')): ?>
        <li>
            <a href="/facturation/modules/facturation/nouvelle-facture.php" class="nav-link<?= classe_nav_active($chemin_courant, ['/modules/facturation/nouvelle-facture.php']) ?>">
                <i class="fa-solid fa-file-invoice"></i> Nouvelle Facture
            </a>
        </li>
        <li>
            <a href="/facturation/modules/facturation/liste-factures.php" class="nav-link<?= classe_nav_active($chemin_courant, ['/modules/facturation/liste-factures.php', '/modules/facturation/afficher-facture.php']) ?>">
                <i class="fa-solid fa-receipt"></i> Factures
            </a>
        </li>
        <?php endif; ?>

        <?php if (a_permission('gerer_produits')): ?>
        <li>
            <a href="/facturation/modules/produits/enregistrer.php" class="nav-link<?= classe_nav_active($chemin_courant, ['/modules/produits/enregistrer.php']) ?>">
                <i class="fa-solid fa-plus-circle"></i> Enregistrer Produit
            </a>
        </li>
        <li>
            <a href="/facturation/modules/produits/liste.php" class="nav-link<?= classe_nav_active($chemin_courant, ['/modules/produits/liste.php']) ?>">
                <i class="fa-solid fa-boxes-stacked"></i> Produits
            </a>
        </li>
        <?php endif; ?>

        <?php if (a_permission('gerer_utilisateurs')): ?>
        <li>
            <a href="/facturation/modules/admin/gestion-comptes.php" class="nav-link<?= classe_nav_active($chemin_courant, ['/modules/admin/gestion-comptes.php', '/modules/admin/ajouter-compte.php']) ?>">
                <i class="fa-solid fa-users-gear"></i> Comptes
            </a>
        </li>
        <li>
            <a href="/facturation/modules/admin/parametres-systeme.php" class="nav-link<?= classe_nav_active($chemin_courant, ['/modules/admin/parametres-systeme.php']) ?>">
                <i class="fa-solid fa-sliders"></i> Paramètres
            </a>
        </li>
        <li>
            <a href="/facturation/modules/admin/journal-audit.php" class="nav-link<?= classe_nav_active($chemin_courant, ['/modules/admin/journal-audit.php']) ?>">
                <i class="fa-solid fa-clipboard-list"></i> Audit
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="navbar-user">
        <div class="user-badge">
            <i class="fa-solid fa-circle-user"></i>
            <?= htmlspecialchars($_SESSION['nom_complet']) ?>
            <span class="user-role">· <?= libelle_role($_SESSION['role']) ?></span>
        </div>
        <a href="/facturation/auth/logout.php" class="btn-deconnexion">
            <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
        </a>
    </div>
</div>
<!-- Le backdrop est la surcouche cliquable/floutée derrière le menu ouvert -->
<div class="navbar-backdrop" data-navbar-backdrop></div>
<?php endif; ?>

<!-- Le reste des pages sera injecté à l'intérieur de ce conteneur -->
<div class="container">
