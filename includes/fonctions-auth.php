<?php
/**
 * Fonctions de gestion de l'authentification et des utilisateurs
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Charge tous les utilisateurs depuis le fichier JSON
 */
function charger_utilisateurs() {
    if (!file_exists(FICHIER_UTILISATEURS)) {
        return [];
    }

    $contenu = file_get_contents(FICHIER_UTILISATEURS);
    $utilisateurs = json_decode($contenu, true);

    return $utilisateurs ?: [];
}

/**
 * Sauvegarde les utilisateurs dans le fichier JSON
 */
function sauvegarder_utilisateurs($utilisateurs) {
    $json = json_encode($utilisateurs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents(FICHIER_UTILISATEURS, $json) !== false;
}

/**
 * Trouve un utilisateur par son identifiant
 */
function trouver_utilisateur($identifiant) {
    $utilisateurs = charger_utilisateurs();

    foreach ($utilisateurs as $utilisateur) {
        if ($utilisateur['identifiant'] === $identifiant) {
            return $utilisateur;
        }
    }

    return null;
}

/**
 * Authentifie un utilisateur
 */
function authentifier_utilisateur($identifiant, $mot_de_passe) {
    $utilisateur = trouver_utilisateur($identifiant);

    if (!$utilisateur) {
        return false;
    }

    if (!$utilisateur['actif']) {
        return false;
    }

    return password_verify($mot_de_passe, $utilisateur['mot_de_passe']);
}

/**
 * Connecte un utilisateur (crée la session)
 */
function connecter_utilisateur($identifiant) {
    $utilisateur = trouver_utilisateur($identifiant);

    if (!$utilisateur) {
        return false;
    }

    $_SESSION['utilisateur'] = $utilisateur['identifiant'];
    $_SESSION['nom_complet'] = $utilisateur['nom_complet'];
    $_SESSION['role'] = $utilisateur['role'];

    return true;
}

/**
 * Déconnecte l'utilisateur
 */
function deconnecter_utilisateur() {
    session_unset();
    session_destroy();
}

/**
 * Crée un nouveau compte utilisateur
 */
function creer_utilisateur($identifiant, $mot_de_passe, $role, $nom_complet) {
    $utilisateurs = charger_utilisateurs();

    // Vérifie si l'identifiant existe déjà
    if (trouver_utilisateur($identifiant)) {
        return false;
    }

    $nouvel_utilisateur = [
        'identifiant' => $identifiant,
        'mot_de_passe' => password_hash($mot_de_passe, PASSWORD_DEFAULT),
        'role' => $role,
        'nom_complet' => $nom_complet,
        'date_creation' => date(FORMAT_DATE),
        'actif' => true
    ];

    $utilisateurs[] = $nouvel_utilisateur;

    return sauvegarder_utilisateurs($utilisateurs);
}

/**
 * Supprime un utilisateur
 */
function supprimer_utilisateur($identifiant) {
    $utilisateurs = charger_utilisateurs();

    $utilisateurs = array_filter($utilisateurs, function($u) use ($identifiant) {
        return $u['identifiant'] !== $identifiant;
    });

    $utilisateurs = array_values($utilisateurs);

    return sauvegarder_utilisateurs($utilisateurs);
}

/**
 * Obtient le libellé d'un rôle
 */
function libelle_role($role) {
    $libelles = [
        ROLE_CAISSIER => 'Caissier',
        ROLE_MANAGER => 'Manager',
        ROLE_SUPER_ADMIN => 'Super Administrateur'
    ];

    return $libelles[$role] ?? $role;
}
?>
