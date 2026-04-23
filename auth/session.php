<?php
/**
 * Gestion de session et vérification d'authentification
 */

// Une session PHP est un stockage côté serveur associé à un visiteur.
// On la démarre une seule fois pour pouvoir lire/écrire dans $_SESSION.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ce fichier apporte les constantes globales : rôles, chemins, formats, etc.
require_once __DIR__ . '/../config/config.php';

/**
 * Retourne la hiérarchie des rôles.
 */
function hierarchie_roles() {
    // Cette table sert de "mapping" entre un rôle métier et son niveau de privilège.
    // Plus la valeur est élevée, plus le rôle est puissant dans le système.
    return [
        ROLE_CAISSIER => 1,
        ROLE_MANAGER => 2,
        ROLE_SUPER_ADMIN => 3
    ];
}

/**
 * Retourne la matrice des permissions par rôle.
 */
function permissions_par_role() {
    // Ici on utilise une matrice d'autorisations (authorization matrix).
    // Chaque rôle possède une liste de capacités métier précises.
    return [
        ROLE_CAISSIER => [
            'scanner_produit',
            'creer_facture',
            'gerer_facture_en_cours',
            'consulter_propres_factures'
        ],
        ROLE_MANAGER => [
            'scanner_produit',
            'creer_facture',
            'gerer_facture_en_cours',
            'consulter_propres_factures',
            'consulter_toutes_factures',
            'gerer_produits',
            'modifier_stock',
            'voir_rapports'
        ],
        ROLE_SUPER_ADMIN => [
            'scanner_produit',
            'creer_facture',
            'gerer_facture_en_cours',
            'consulter_propres_factures',
            'consulter_toutes_factures',
            'gerer_produits',
            'modifier_stock',
            'voir_rapports',
            'gerer_utilisateurs',
            'configurer_systeme'
        ]
    ];
}

/**
 * Génère ou retourne le jeton CSRF de la session.
 */
function jeton_csrf() {
    // Un jeton CSRF est une valeur aléatoire stockée en session.
    // On l'envoie dans les formulaires pour s'assurer que la requête vient bien de notre interface.
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Retourne le champ HTML du jeton CSRF.
 */
function champ_csrf() {
    // On fabrique ici directement le champ hidden prêt à être injecté dans le formulaire HTML.
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(jeton_csrf(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Vérifie le jeton CSRF envoyé.
 */
function verifier_csrf($jeton) {
    // hash_equals protège contre certaines attaques de timing lors de la comparaison.
    return isset($_SESSION['csrf_token'])
        && is_string($jeton)
        && hash_equals($_SESSION['csrf_token'], $jeton);
}

/**
 * Exige un jeton CSRF valide pour les requêtes POST.
 */
function requiert_csrf() {
    // Guard clause : si ce n'est pas une requête POST, il n'y a rien à vérifier.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    // On récupère le token envoyé par le formulaire, ou une chaîne vide par défaut.
    $jeton = $_POST['csrf_token'] ?? '';

    // En cas d'échec, on renvoie un code HTTP 419 et on stoppe complètement le script.
    if (!verifier_csrf($jeton)) {
        http_response_code(419);
        echo '<!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Session expirée</title>
            <link rel="stylesheet" href="/facturation/assets/css/style.css">
        </head>
        <body>
            <div class="container">
                <div class="erreur-acces">
                    <h1>Session expirée</h1>
                    <p>Le formulaire a expiré ou la requête est invalide. Veuillez réessayer.</p>
                    <a href="/facturation/index.php" class="btn-primaire">Retour à l\'accueil</a>
                </div>
            </div>
        </body>
        </html>';
        exit();
    }
}

/**
 * Vérifie si l'utilisateur est connecté
 */
function est_connecte() {
    // Ici, être connecté signifie que les informations minimales de session existent.
    return isset($_SESSION['utilisateur']) && isset($_SESSION['role']);
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 */
function a_role($role_requis) {
    // Si personne n'est connecté, on refuse immédiatement.
    if (!est_connecte()) {
        return false;
    }

    $role_actuel = $_SESSION['role'];

    // Règle d'héritage : le super admin surclasse tous les rôles.
    if ($role_actuel === ROLE_SUPER_ADMIN) {
        return true;
    }

    // Règle d'héritage métier : le manager peut faire ce qu'un caissier peut faire.
    if ($role_actuel === ROLE_MANAGER && $role_requis === ROLE_CAISSIER) {
        return true;
    }

    // Sinon, on vérifie une égalité stricte entre le rôle courant et le rôle demandé.
    return $role_actuel === $role_requis;
}

/**
 * Vérifie si l'utilisateur possède une permission.
 */
function a_permission($permission) {
    // Une permission est plus fine qu'un rôle.
    // C'est ce mécanisme qu'on préfère pour sécuriser les pages métier.
    if (!est_connecte()) {
        return false;
    }

    $role_actuel = $_SESSION['role'];
    $permissions = permissions_par_role();

    // in_array(..., true) active la comparaison stricte pour éviter les ambiguïtés.
    return isset($permissions[$role_actuel]) && in_array($permission, $permissions[$role_actuel], true);
}

/**
 * Vérifie si l'utilisateur a au moins un certain niveau de rôle
 */
function a_au_moins_role($role_minimum) {
    // Cette fonction compare les niveaux de privilèges via la hiérarchie numérique.
    if (!est_connecte()) {
        return false;
    }

    $role_actuel = $_SESSION['role'];

    $hierarchie = hierarchie_roles();

    if (!isset($hierarchie[$role_actuel], $hierarchie[$role_minimum])) {
        return false;
    }

    // Si le niveau courant est supérieur ou égal au niveau demandé, l'accès est autorisé.
    return $hierarchie[$role_actuel] >= $hierarchie[$role_minimum];
}

/**
 * Requiert une connexion - redirige vers login si non connecté
 */
function requiert_connexion() {
    // C'est un middleware minimaliste version PHP procédural.
    if (!est_connecte()) {
        header('Location: /facturation/auth/login.php');
        exit();
    }
}

/**
 * Requiert un rôle spécifique - affiche erreur si non autorisé
 */
function requiert_role($role_requis) {
    // On impose d'abord l'authentification, puis l'autorisation.
    requiert_connexion();

    if (!a_au_moins_role($role_requis)) {
        afficher_erreur_acces();
        exit();
    }
}

/**
 * Requiert une permission métier.
 */
function requiert_permission($permission) {
    // Même principe que requiert_role(), mais avec une granularité plus fine.
    requiert_connexion();

    if (!a_permission($permission)) {
        afficher_erreur_acces();
        exit();
    }
}

/**
 * Vérifie si l'utilisateur connecté peut consulter une facture donnée.
 */
function peut_consulter_facture($facture) {
    // Cette fonction applique une règle d'ownership : un caissier ne voit que ses propres factures.
    if (!est_connecte() || empty($facture)) {
        return false;
    }

    // Les rôles ayant la permission globale voient toutes les factures.
    if (a_permission('consulter_toutes_factures')) {
        return true;
    }

    // Sinon on vérifie la propriété métier de la facture : le caissier créateur.
    return a_permission('consulter_propres_factures')
        && isset($facture['caissier'])
        && $facture['caissier'] === utilisateur_connecte();
}

/**
 * Affiche un message d'erreur d'accès
 */
function afficher_erreur_acces() {
    // On renvoie une réponse HTTP 403 pour signaler un accès interdit.
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
                <h1>Accès refusé</h1>
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
    // Helper : évite d'accéder directement à $_SESSION partout dans le code métier.
    return est_connecte() ? $_SESSION['utilisateur'] : null;
}

/**
 * Obtient le rôle de l'utilisateur connecté
 */
function role_connecte() {
    // Même logique ici, mais pour le rôle courant.
    return est_connecte() ? $_SESSION['role'] : null;
}
?>
