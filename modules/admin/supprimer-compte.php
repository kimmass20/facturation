<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';

requiert_role(ROLE_SUPER_ADMIN);

$id = $_GET['id'] ?? '';

if (empty($id)) {
    header('Location: /facturation/modules/admin/gestion-comptes.php');
    exit();
}

// Vérifie que l'utilisateur existe
$utilisateur = trouver_utilisateur($id);

if (!$utilisateur) {
    header('Location: /facturation/modules/admin/gestion-comptes.php');
    exit();
}

// Vérifie qu'on ne supprime pas soi-même ou un super admin
if ($id === utilisateur_connecte() || $utilisateur['role'] === ROLE_SUPER_ADMIN) {
    header('Location: /facturation/modules/admin/gestion-comptes.php');
    exit();
}

// Suppression
if (supprimer_utilisateur($id)) {
    header('Location: /facturation/modules/admin/gestion-comptes.php');
    exit();
} else {
    die("Erreur lors de la suppression du compte.");
}
?>
