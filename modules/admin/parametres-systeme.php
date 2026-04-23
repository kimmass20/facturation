<?php
// Chargement de la couche session/sécurité et des helpers d'administration.
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';

// Comment lire ce fichier :
// 1. la page vérifie d'abord que l'utilisateur a le droit de modifier le système,
// 2. elle charge les paramètres actuels depuis parametres.json,
// 3. si le formulaire part en POST, elle valide puis sauvegarde,
// 4. elle journalise le changement dans audit.json,
// 5. enfin elle génère le HTML du formulaire d'administration.

// Seuls les profils autorisés à configurer le système peuvent accéder à cette page.
requiert_permission('configurer_systeme');

// Métadonnées de page et variables d'interface.
$titre_page = 'Paramètres système';
$message = '';
$erreurs = [];

// On charge les paramètres actuels afin de préremplir le formulaire.
$parametres = charger_parametres_systeme();

// Contrôleur de mise à jour des paramètres système.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requiert_csrf();

    // On reconstruit ici un tableau propre à partir des champs saisis.
    $parametres_saisis = [
        'nom_entreprise' => trim($_POST['nom_entreprise'] ?? ''),
        'adresse_entreprise' => trim($_POST['adresse_entreprise'] ?? ''),
        'telephone_entreprise' => trim($_POST['telephone_entreprise'] ?? ''),
        'taux_tva' => (float) ($_POST['taux_tva'] ?? 0)
    ];

    // Validation métier des paramètres.
    $erreurs = valider_parametres_systeme($parametres_saisis);

    // Si tout est valide, on sauvegarde puis on journalise le changement.
    if (empty($erreurs) && sauvegarder_parametres_systeme($parametres_saisis)) {
        // Ici on conserve un avant/après, ce qui permet de comprendre exactement
        // ce qui a changé plus tard dans le journal d'audit.
        journaliser_action('mise_a_jour_parametres_systeme', 'systeme', [
            'ancien' => $parametres,
            'nouveau' => $parametres_saisis
        ]);

        // On remplace les paramètres courants en mémoire pour refléter immédiatement la mise à jour.
        $parametres = $parametres_saisis;
        $message = 'Paramètres système mis à jour avec succès.';
    } elseif (empty($erreurs)) {
        $erreurs[] = 'Impossible d\'enregistrer les paramètres système.';
    }
}

// Inclusion du layout partagé.
include __DIR__ . '/../../includes/header.php';
?>

<!-- Titre principal -->
<h2><i class="fa-solid fa-sliders"></i> Paramètres système</h2>

<!-- Message de succès -->
<?php if ($message): ?>
    <div class="message-succes"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- Liste des erreurs de validation -->
<?php if (!empty($erreurs)): ?>
    <div class="message-erreur">
        <strong>Erreurs :</strong>
        <ul style="margin: 0.5rem 0 0 1.5rem;">
            <?php foreach ($erreurs as $erreur): ?>
                <li><?= htmlspecialchars($erreur) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Formulaire d'administration des paramètres globaux -->
<div class="carte">
    <form method="post">
        <?= champ_csrf() ?>
        <div class="champ-formulaire">
            <label for="nom_entreprise">Nom de l'entreprise *</label>
            <input type="text" id="nom_entreprise" name="nom_entreprise" required value="<?= htmlspecialchars($parametres['nom_entreprise']) ?>">
        </div>

        <div class="champ-formulaire">
            <label for="adresse_entreprise">Adresse *</label>
            <input type="text" id="adresse_entreprise" name="adresse_entreprise" required value="<?= htmlspecialchars($parametres['adresse_entreprise']) ?>">
        </div>

        <div class="champ-formulaire">
            <label for="telephone_entreprise">Téléphone *</label>
            <input type="text" id="telephone_entreprise" name="telephone_entreprise" required value="<?= htmlspecialchars($parametres['telephone_entreprise']) ?>">
        </div>

        <div class="champ-formulaire">
            <label for="taux_tva">Taux TVA *</label>
            <input type="number" id="taux_tva" name="taux_tva" min="0" max="1" step="0.01" required value="<?= htmlspecialchars((string) $parametres['taux_tva']) ?>">
            <small style="color: #6b7280;">Valeur décimale entre 0 et 1. Exemple: 0.18 pour 18%.</small>
        </div>

        <button type="submit" class="btn-primaire">Enregistrer les paramètres</button>
    </form>
</div>

<!-- Footer partagé -->
<?php include __DIR__ . '/../../includes/footer.php'; ?>