<?php
session_start();
require_once __DIR__ . '/../includes/fonctions-auth.php';

deconnecter_utilisateur();

header('Location: /facturation/auth/login.php');
exit();
?>
