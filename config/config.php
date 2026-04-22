<?php
/**
 * Fichier de configuration globale
 */

// Chemins des fichiers de données
define('DATA_DIR', __DIR__ . '/../data/');
define('FICHIER_PRODUITS', DATA_DIR . 'produits.json');
define('FICHIER_FACTURES', DATA_DIR . 'factures.json');
define('FICHIER_UTILISATEURS', DATA_DIR . 'utilisateurs.json');

// Paramètres de TVA
define('TAUX_TVA', 0.18); // 18%

// Monnaie
define('MONNAIE', 'CDF');

// Rôles utilisateurs
define('ROLE_CAISSIER', 'caissier');
define('ROLE_MANAGER', 'manager');
define('ROLE_SUPER_ADMIN', 'super_admin');

// Informations de l'entreprise
define('NOM_ENTREPRISE', 'Super Marché FREEDOM');
define('ADRESSE_ENTREPRISE', '123 Avenue de la Liberté, Kinshasa, RDC');
define('TELEPHONE_ENTREPRISE', '+243 XXX XXX XXX');

// Format de date
define('FORMAT_DATE', 'Y-m-d');
define('FORMAT_DATETIME', 'Y-m-d H:i:s');

// Fuseau horaire
date_default_timezone_set('Africa/Kinshasa');
?>
