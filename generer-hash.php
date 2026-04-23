<?php
/**
 * Script utilitaire pour générer des hashes de mots de passe
 * Usage : php generer-hash.php
 */

// Ce script n'est pas une page web : c'est un script CLI (Command Line Interface).
// On l'exécute dans le terminal pour générer ou régénérer des mots de passe hashés.
// Comment le lire :
// 1. il montre d'abord comment PHP fabrique un hash sécurisé,
// 2. puis il reconstruit un petit jeu de comptes de démonstration,
// 3. enfin il réécrit utilisateurs.json avec ces comptes.
// Ce fichier est donc surtout pédagogique et pratique pour réinitialiser l'environnement.

echo "=== Générateur de hashes de mots de passe ===\n\n";

// Liste des mots de passe de démonstration à convertir en hash sécurisé.
$mots_de_passe = [
    'admin123',
    'manager123',
    'caissier123'
];

// Boucle de génération : pour chaque mot de passe en clair, on calcule un hash.
foreach ($mots_de_passe as $mdp) {
    $hash = password_hash($mdp, PASSWORD_DEFAULT);
    echo "Mot de passe: $mdp\n";
    echo "Hash: $hash\n\n";
}

echo "=== Mise à jour du fichier utilisateurs.json ===\n\n";

// On reconstruit ici un jeu minimal de comptes de test pour le projet.
$utilisateurs = [
    [
        'identifiant' => 'admin',
        'mot_de_passe' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'super_admin',
        'nom_complet' => 'Super Administrateur',
        'date_creation' => '2026-04-17',
        'actif' => true
    ],
    [
        'identifiant' => 'manager',
        'mot_de_passe' => password_hash('manager123', PASSWORD_DEFAULT),
        'role' => 'manager',
        'nom_complet' => 'Jean Manager',
        'date_creation' => '2026-04-17',
        'actif' => true
    ],
    [
        'identifiant' => 'caissier',
        'mot_de_passe' => password_hash('caissier123', PASSWORD_DEFAULT),
        'role' => 'caissier',
        'nom_complet' => 'Marie Caissière',
        'date_creation' => '2026-04-17',
        'actif' => true
    ]
];

// Encodage du tableau PHP en JSON lisible.
$json = json_encode($utilisateurs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Écriture directe dans le fichier utilisateurs.json.
// Attention pédagogique : cette action remplace totalement le contenu précédent du fichier.
file_put_contents(__DIR__ . '/data/utilisateurs.json', $json);

echo "Fichier utilisateurs.json mis à jour avec succès !\n";

?>
