<?php
/**
 * Script utilitaire pour générer des hashes de mots de passe
 * Usage : php generer-hash.php
 */

echo "=== Générateur de hashes de mots de passe ===\n\n";

$mots_de_passe = [
    'admin123',
    'manager123',
    'caissier123'
];

foreach ($mots_de_passe as $mdp) {
    $hash = password_hash($mdp, PASSWORD_DEFAULT);
    echo "Mot de passe: $mdp\n";
    echo "Hash: $hash\n\n";
}

echo "=== Mise à jour du fichier utilisateurs.json ===\n\n";

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

$json = json_encode($utilisateurs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents(__DIR__ . '/data/utilisateurs.json', $json);

echo "Fichier utilisateurs.json mis à jour avec succès !\n";
?>
