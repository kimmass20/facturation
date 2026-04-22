<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/fonctions-auth.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titre_page) ? $titre_page . ' — ' : '' ?><?= NOM_ENTREPRISE ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/facturation/assets/css/style.css">
</head>
<body>
<?php if (est_connecte()): ?>
<nav class="navbar">
    <div class="navbar-brand">
        <h1><i class="fa-solid fa-bolt"></i> <?= NOM_ENTREPRISE ?></h1>
    </div>

    <div class="navbar-menu">
        <a href="/facturation/index.php" class="nav-link">
            <i class="fa-solid fa-house"></i> Accueil
        </a>

        <?php if (a_au_moins_role(ROLE_CAISSIER)): ?>
        <a href="/facturation/modules/facturation/nouvelle-facture.php" class="nav-link">
            <i class="fa-solid fa-file-invoice"></i> Nouvelle Facture
        </a>
        <?php endif; ?>

        <?php if (a_au_moins_role(ROLE_MANAGER)): ?>
        <a href="/facturation/modules/produits/enregistrer.php" class="nav-link">
            <i class="fa-solid fa-plus-circle"></i> Enregistrer Produit
        </a>
        <a href="/facturation/modules/produits/liste.php" class="nav-link">
            <i class="fa-solid fa-boxes-stacked"></i> Produits
        </a>
        <?php endif; ?>

        <?php if (a_au_moins_role(ROLE_SUPER_ADMIN)): ?>
        <a href="/facturation/modules/admin/gestion-comptes.php" class="nav-link">
            <i class="fa-solid fa-users-gear"></i> Comptes
        </a>
        <?php endif; ?>
    </div>

    <div class="navbar-user">
        <div class="user-badge">
            <i class="fa-solid fa-circle-user"></i>
            <?= htmlspecialchars($_SESSION['nom_complet']) ?>
            <span style="color:var(--text-muted);font-weight:400;">· <?= libelle_role($_SESSION['role']) ?></span>
        </div>
        <a href="/facturation/auth/logout.php" class="btn-deconnexion">
            <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
        </a>
    </div>
</nav>
<?php endif; ?>

<div class="container">
