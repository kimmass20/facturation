<?php
// On démarre la session pour être certain de manipuler la session courante.
session_start();

// On charge la fonction de déconnexion centralisée.
require_once __DIR__ . '/../includes/fonctions-auth.php';

// Cette fonction vide la session et détruit les informations de connexion.
deconnecter_utilisateur();

// Après logout, on redirige l'utilisateur vers l'écran de connexion.
header('Location: /facturation/auth/login.php');
exit();
?>
