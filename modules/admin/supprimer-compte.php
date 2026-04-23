<?php
// Dépendances de sécurité et de gestion des comptes.
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';

// Ce fichier n'affiche pas de page HTML.
// Son rôle est d'agir comme une route d'action serveur :
// il reçoit une requête POST, applique plusieurs garde-fous,
// puis supprime le compte ou refuse l'opération.

// La suppression de compte est une action strictement administrative.
requiert_permission('gerer_utilisateurs');

// On refuse l'accès direct en GET : cette action doit venir d'un formulaire POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /facturation/modules/admin/gestion-comptes.php');
    exit();
}

// Vérification du jeton anti-CSRF.
requiert_csrf();

// Lecture de l'identifiant ciblé.
$id = trim($_POST['identifiant'] ?? '');

// Si aucun identifiant n'est fourni, on retourne simplement à la page de gestion.
if (empty($id)) {
    header('Location: /facturation/modules/admin/gestion-comptes.php');
    exit();
}

// Lookup du compte à supprimer.
$utilisateur = trouver_utilisateur($id);

if (!$utilisateur) {
    // Si la cible n'existe plus, on évite toute erreur technique visible et on revient simplement.
    header('Location: /facturation/modules/admin/gestion-comptes.php');
    exit();
}

// Double garde-fou métier :
// - on ne peut pas supprimer son propre compte
// - on ne peut pas supprimer un super administrateur
if ($id === utilisateur_connecte() || $utilisateur['role'] === ROLE_SUPER_ADMIN) {
    header('Location: /facturation/modules/admin/gestion-comptes.php');
    exit();
}

// Si toutes les conditions sont réunies, on exécute la suppression définitive.
if (supprimer_utilisateur($id)) {
    // On trace l'événement dans le journal d'audit.
    journaliser_action('suppression_compte', $id, [
        'role' => $utilisateur['role'],
        'nom_complet' => $utilisateur['nom_complet']
    ]);

    // La redirection finale empêche l'utilisateur de rester sur une route d'action brute.
    header('Location: /facturation/modules/admin/gestion-comptes.php');
    exit();
} else {
    // die() stoppe immédiatement le script et affiche un message brut.
    die("Erreur lors de la suppression du compte.");
}
?>
