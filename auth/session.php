<?php
/**
 * Gestion de session et vérification d'authentification
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

/**
 * Vérifie si l'utilisateur est connecté
 */
function est_connecte() {
    return isset($_SESSION['utilisateur']) && isset($_SESSION['role']);
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 */
function a_role($role_requis) {
    if (!est_connecte()) {
        return false;
    }

    $role_actuel = $_SESSION['role'];

    // Super admin a accès à tout
    if ($role_actuel === ROLE_SUPER_ADMIN) {
        return true;
    }

    // Manager a accès aux fonctions caissier
    if ($role_actuel === ROLE_MANAGER && $role_requis === ROLE_CAISSIER) {
        return true;
    }

    return $role_actuel === $role_requis;
}

/**
 * Vérifie si l'utilisateur a au moins un certain niveau de rôle
 */
function a_au_moins_role($role_minimum) {
    if (!est_connecte()) {
        return false;
    }

    $role_actuel = $_SESSION['role'];

    $hierarchie = [
        ROLE_CAISSIER => 1,
        ROLE_MANAGER => 2,
        ROLE_SUPER_ADMIN => 3
    ];

    return $hierarchie[$role_actuel] >= $hierarchie[$role_minimum];
}

/**
 * Requiert une connexion - redirige vers login si non connecté
 */
function requiert_connexion() {
    if (!est_connecte()) {
        header('Location: /facturation/auth/login.php');
        exit();
    }
}

/**
 * Requiert un rôle spécifique - affiche erreur si non autorisé
 */
function requiert_role($role_requis) {
    requiert_connexion();

    if (!a_au_moins_role($role_requis)) {
        afficher_erreur_acces();
        exit();
    }
}

/**
 * Affiche un message d'erreur d'accès
 */
function afficher_erreur_acces() {
    http_response_code(403);
    echo '<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Accès refusé</title>
        <link rel="stylesheet" href="/facturation/assets/css/style.css">
    </head>
    <body>
        <div class="container">
            <div class="erreur-acces">
                <h1>❌ Accès refusé</h1>
                <p>Vous n\'avez pas les permissions nécessaires pour accéder à cette page.</p>
                <a href="/facturation/index.php" class="btn-primaire">Retour à l\'accueil</a>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Obtient l'utilisateur connecté
 */
function utilisateur_connecte() {
    return est_connecte() ? $_SESSION['utilisateur'] : null;
}

/**
 * Obtient le rôle de l'utilisateur connecté
 */
function role_connecte() {
    return est_connecte() ? $_SESSION['role'] : null;
}
?>
