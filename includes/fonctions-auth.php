<?php
/**
 * Fonctions de gestion de l'authentification et des utilisateurs
 */

// On charge les constantes globales du projet.
require_once __DIR__ . '/../config/config.php';

/**
 * Charge les paramètres système.
 */
function charger_parametres_systeme() {
    // Valeurs de fallback : elles servent si le fichier JSON n'existe pas encore
    // ou si son contenu est invalide.
    $parametres_defaut = [
        'nom_entreprise' => PARAM_NOM_ENTREPRISE,
        'adresse_entreprise' => PARAM_ADRESSE_ENTREPRISE,
        'telephone_entreprise' => PARAM_TELEPHONE_ENTREPRISE,
        'taux_tva' => PARAM_TAUX_TVA
    ];

    // Si le fichier n'existe pas, on renvoie directement la configuration par défaut.
    if (!file_exists(FICHIER_PARAMETRES)) {
        return $parametres_defaut;
    }

    // Lecture brute du fichier JSON sur le disque.
    $contenu = file_get_contents(FICHIER_PARAMETRES);

    // Désérialisation JSON -> tableau PHP associatif.
    $parametres = json_decode($contenu, true);

    // Si le décodage a échoué, on retombe encore sur la config par défaut.
    if (!is_array($parametres)) {
        return $parametres_defaut;
    }

    // array_merge permet de compléter un JSON partiel avec les valeurs manquantes par défaut.
    return array_merge($parametres_defaut, $parametres);
}

/**
 * Sauvegarde les paramètres système.
 */
function sauvegarder_parametres_systeme($parametres) {
    // On sérialise les données PHP en JSON lisible pour un humain.
    $json = json_encode($parametres, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents(FICHIER_PARAMETRES, $json) !== false;
}

/**
 * Retourne une valeur de paramètre système.
 */
function parametre_systeme($cle) {
    // Petit helper d'accès pour éviter de charger manuellement tout le tableau partout.
    $parametres = charger_parametres_systeme();
    return $parametres[$cle] ?? null;
}

/**
 * Valide les paramètres système saisis.
 */
function valider_parametres_systeme($parametres) {
    // On accumule les erreurs dans un tableau, plutôt que de s'arrêter à la première.
    $erreurs = [];

    if (empty(trim($parametres['nom_entreprise'] ?? ''))) {
        $erreurs[] = "Le nom de l'entreprise est obligatoire.";
    }

    if (empty(trim($parametres['adresse_entreprise'] ?? ''))) {
        $erreurs[] = "L'adresse de l'entreprise est obligatoire.";
    }

    if (empty(trim($parametres['telephone_entreprise'] ?? ''))) {
        $erreurs[] = "Le téléphone de l'entreprise est obligatoire.";
    }

    // Le taux de TVA est stocké sous forme décimale : 0.18 = 18%.
    $taux_tva = $parametres['taux_tva'] ?? null;
    if (!is_numeric($taux_tva) || $taux_tva < 0 || $taux_tva > 1) {
        $erreurs[] = "Le taux de TVA doit être un nombre entre 0 et 1.";
    }

    return $erreurs;
}

/**
 * Charge le journal d'audit.
 */
function charger_journal_audit() {
    // Le journal d'audit est une piste d'audit (audit trail) stockée en JSON.
    if (!file_exists(FICHIER_AUDIT)) {
        return [];
    }

    $contenu = file_get_contents(FICHIER_AUDIT);
    $entrees = json_decode($contenu, true);

    return is_array($entrees) ? $entrees : [];
}

/**
 * Sauvegarde le journal d'audit.
 */
function sauvegarder_journal_audit($entrees) {
    // Même principe de persistance JSON que pour les paramètres.
    $json = json_encode($entrees, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents(FICHIER_AUDIT, $json) !== false;
}

/**
 * Ajoute une entrée au journal d'audit.
 */
function journaliser_action($action, $cible = null, $details = []) {
    // On commence par charger les entrées existantes, puis on append une nouvelle entrée.
    $entrees = charger_journal_audit();

    $entrees[] = [
        // Le payload de l'audit doit permettre de reconstituer le contexte de l'action.
        'date_heure' => date(FORMAT_DATETIME),
        'utilisateur' => $_SESSION['utilisateur'] ?? 'systeme',
        'role' => $_SESSION['role'] ?? null,
        'action' => $action,
        'cible' => $cible,
        'details' => $details
    ];

    return sauvegarder_journal_audit($entrees);
}

/**
 * Retourne les dernières entrées d'audit.
 */
function obtenir_dernieres_actions_audit($limite = 50) {
    // On inverse le tableau pour avoir les entrées les plus récentes d'abord.
    $entrees = array_reverse(charger_journal_audit());
    return array_slice($entrees, 0, $limite);
}

/**
 * Charge tous les utilisateurs depuis le fichier JSON
 */
function charger_utilisateurs() {
    // Ce projet utilise une persistance fichier au lieu d'une base de données SQL.
    if (!file_exists(FICHIER_UTILISATEURS)) {
        return [];
    }

    $contenu = file_get_contents(FICHIER_UTILISATEURS);
    $utilisateurs = json_decode($contenu, true);

    // Si le JSON est vide ou invalide, on renvoie un tableau vide.
    return $utilisateurs ?: [];
}

/**
 * Sauvegarde les utilisateurs dans le fichier JSON
 */
function sauvegarder_utilisateurs($utilisateurs) {
    // Toute écriture des comptes passe par ce point central.
    $json = json_encode($utilisateurs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents(FICHIER_UTILISATEURS, $json) !== false;
}

/**
 * Trouve un utilisateur par son identifiant
 */
function trouver_utilisateur($identifiant) {
    // Lookup linéaire : on parcourt la liste jusqu'à trouver une correspondance.
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
function authentifier_utilisateur($identifiant, $mot_de_passe, $role = null) {
    // Étape 1 : lookup de l'utilisateur.
    $utilisateur = trouver_utilisateur($identifiant);

    if (!$utilisateur) {
        return false;
    }

    // Étape 2 : on refuse la connexion si le compte est désactivé.
    if (!$utilisateur['actif']) {
        return false;
    }

    // Étape 3 : si l'interface a demandé un rôle précis, on le vérifie.
    if ($role !== null && $utilisateur['role'] !== $role) {
        return false;
    }

    // Étape 4 : vérification du hash du mot de passe.
    return password_verify($mot_de_passe, $utilisateur['mot_de_passe']);
}

/**
 * Connecte un utilisateur (crée la session)
 */
function connecter_utilisateur($identifiant) {
    // On recharge les infos complètes du compte pour hydrater la session.
    $utilisateur = trouver_utilisateur($identifiant);

    if (!$utilisateur) {
        return false;
    }

    // La session embarque ici les données minimales utiles au reste de l'application.
    $_SESSION['utilisateur'] = $utilisateur['identifiant'];
    $_SESSION['nom_complet'] = $utilisateur['nom_complet'];
    $_SESSION['role'] = $utilisateur['role'];

    return true;
}

/**
 * Déconnecte l'utilisateur
 */
function deconnecter_utilisateur() {
    // session_unset vide les variables, session_destroy détruit la session côté serveur.
    session_unset();
    session_destroy();
}

/**
 * Crée un nouveau compte utilisateur
 */
function creer_utilisateur($identifiant, $mot_de_passe, $role, $nom_complet) {
    // On charge l'état actuel avant mutation.
    $utilisateurs = charger_utilisateurs();

    // Contrôle d'unicité : l'identifiant joue ici le rôle de clé logique.
    if (trouver_utilisateur($identifiant)) {
        return false;
    }

    // Le mot de passe n'est jamais stocké en clair : on persiste uniquement son hash.
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
    // array_filter enlève le compte ciblé, puis array_values réindexe le tableau.
    $utilisateurs = charger_utilisateurs();

    $utilisateurs = array_filter($utilisateurs, function($u) use ($identifiant) {
        return $u['identifiant'] !== $identifiant;
    });

    $utilisateurs = array_values($utilisateurs);

    return sauvegarder_utilisateurs($utilisateurs);
}

/**
 * Met à jour le rôle d'un utilisateur.
 */
function mettre_a_jour_role_utilisateur($identifiant, $nouveau_role) {
    // On whiteliste les rôles autorisés pour éviter l'injection de valeurs illégales.
    if (!in_array($nouveau_role, [ROLE_CAISSIER, ROLE_MANAGER, ROLE_SUPER_ADMIN], true)) {
        return false;
    }

    $utilisateurs = charger_utilisateurs();

    foreach ($utilisateurs as &$utilisateur) {
        if ($utilisateur['identifiant'] === $identifiant) {
            // L'opérateur & crée une référence, donc on modifie directement l'élément courant.
            $utilisateur['role'] = $nouveau_role;
            return sauvegarder_utilisateurs($utilisateurs);
        }
    }

    return false;
}

/**
 * Active ou désactive un compte utilisateur.
 */
function definir_statut_utilisateur($identifiant, $actif) {
    // Même pattern que pour le changement de rôle, mais appliqué au booléen actif.
    $utilisateurs = charger_utilisateurs();

    foreach ($utilisateurs as &$utilisateur) {
        if ($utilisateur['identifiant'] === $identifiant) {
            $utilisateur['actif'] = (bool)$actif;
            return sauvegarder_utilisateurs($utilisateurs);
        }
    }

    return false;
}

/**
 * Obtient le libellé d'un rôle
 */
function libelle_role($role) {
    // On convertit la constante technique en texte lisible pour l'interface.
    $libelles = [
        ROLE_CAISSIER => 'Caissier',
        ROLE_MANAGER => 'Manager',
        ROLE_SUPER_ADMIN => 'Super Administrateur'
    ];

    return $libelles[$role] ?? $role;
}
?>
