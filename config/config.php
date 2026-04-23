<?php
/**
 * Fichier de configuration globale
 */

// Ce fichier centralise les constantes du projet.
// Une constante est une valeur globale immuable, accessible partout après require_once.

// Répertoire racine des données JSON de l'application.
define('DATA_DIR', __DIR__ . '/../data/');

// Chemins absolus vers les différents fichiers de persistance.
// Ici, le projet remplace une base de données par des fichiers JSON.
define('FICHIER_PRODUITS', DATA_DIR . 'produits.json');
define('FICHIER_FACTURES', DATA_DIR . 'factures.json');
define('FICHIER_UTILISATEURS', DATA_DIR . 'utilisateurs.json');
define('FICHIER_PARAMETRES', DATA_DIR . 'parametres.json');
define('FICHIER_AUDIT', DATA_DIR . 'audit.json');

// Taux de TVA par défaut.
// 0.18 signifie 18%.
define('TAUX_TVA', 0.18); // 18%

// Devise utilisée pour afficher les montants dans l'interface.
define('MONNAIE', 'CDF');

// Constantes de rôles.
// Elles servent de "source of truth" pour tout le système d'autorisation.
define('ROLE_CAISSIER', 'caissier');
define('ROLE_MANAGER', 'manager');
define('ROLE_SUPER_ADMIN', 'super_admin');

// Informations d'entreprise par défaut.
// Elles peuvent ensuite être surchargées par les paramètres système modifiables.
define('NOM_ENTREPRISE', 'Super Marché FREEDOM');
define('ADRESSE_ENTREPRISE', '123 Avenue de la Liberté, Kinshasa, RDC');
define('TELEPHONE_ENTREPRISE', '+243 978956023');

// Formats de date standardisés pour le stockage et l'horodatage.
define('FORMAT_DATE', 'Y-m-d');
define('FORMAT_DATETIME', 'Y-m-d H:i:s');

// Le fuseau horaire garantit que toutes les dates générées sont cohérentes.
date_default_timezone_set('Africa/Kinshasa');

// Valeurs de fallback utilisées par les paramètres système.
// Si le fichier parametres.json est manquant ou incomplet, ces constantes servent de base.
define('PARAM_NOM_ENTREPRISE', NOM_ENTREPRISE);
define('PARAM_ADRESSE_ENTREPRISE', ADRESSE_ENTREPRISE);
define('PARAM_TELEPHONE_ENTREPRISE', TELEPHONE_ENTREPRISE);
define('PARAM_TAUX_TVA', TAUX_TVA);
?>
