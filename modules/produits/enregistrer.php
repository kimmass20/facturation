<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';

requiert_role(ROLE_MANAGER);

$titre_page = 'Enregistrer un produit';
$message = '';
$erreurs = [];
$produit_existant = null;
$code_barre_scanne = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer'])) {
    $code_barre = trim($_POST['code_barre'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prix_unitaire_ht = trim($_POST['prix_unitaire_ht'] ?? '');
    $date_expiration = trim($_POST['date_expiration'] ?? '');
    $quantite_stock = trim($_POST['quantite_stock'] ?? '');

    // Validation
    $erreurs = valider_produit($nom, $prix_unitaire_ht, $date_expiration, $quantite_stock);

    if (empty($code_barre)) {
        $erreurs[] = "Le code-barres est obligatoire.";
    }

    if (empty($erreurs)) {
        // Convertir la date au format ISO pour le stockage
        $date_expiration_iso = convertir_date_us_vers_iso($date_expiration);

        if (enregistrer_produit($code_barre, $nom, $prix_unitaire_ht, $date_expiration, $quantite_stock)) {
            $message = "Produit enregistré avec succès !";
            // Réinitialiser le formulaire
            $code_barre_scanne = '';
        } else {
            $erreurs[] = "Erreur lors de l'enregistrement du produit.";
        }
    }
}

// Vérification d'un code-barres scanné
if (isset($_GET['code_barre']) && !empty($_GET['code_barre'])) {
    $code_barre_scanne = trim($_GET['code_barre']);
    $produit_existant = trouver_produit($code_barre_scanne);
}

include __DIR__ . '/../../includes/header.php';
?>

<h2>📝 Enregistrer un produit</h2>

<?php if ($message): ?>
    <div class="message-succes">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

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

<div class="scanner-container">
    <h3>Scanner le code-barres</h3>
    <p class="mb-2">Activez votre caméra pour scanner le code-barres du produit.</p>

    <div id="scanner-viewport"></div>

    <div class="scanner-controls">
        <button id="btn-demarrer-scanner" class="btn-primaire">📷 Démarrer le scanner</button>
        <button id="btn-arreter-scanner" class="btn-secondaire" style="display: none;">⏹️ Arrêter le scanner</button>
    </div>

    <div id="scanner-resultat" class="scanner-resultat" style="display: none;">
        <p><strong>Code-barres détecté :</strong> <span id="code-barre-detecte"></span></p>
    </div>
</div>

<?php if ($produit_existant): ?>
    <div class="message-info">
        <strong>ℹ️ Produit existant</strong><br>
        Ce code-barres est déjà enregistré : <strong><?= htmlspecialchars($produit_existant['nom']) ?></strong><br>
        Vous pouvez modifier ses informations ci-dessous.
    </div>
<?php endif; ?>

<div class="carte">
    <h3>Informations du produit</h3>

    <form method="post" id="formulaire-produit">
        <input type="hidden" name="code_barre" id="input-code-barre" value="<?= htmlspecialchars($code_barre_scanne) ?>">

        <div class="champ-formulaire">
            <label>Code-barres</label>
            <input type="text" id="affichage-code-barre" readonly
                   value="<?= htmlspecialchars($code_barre_scanne) ?>"
                   placeholder="Scannez un code-barres pour commencer">
        </div>

        <div class="champ-formulaire">
            <label for="nom">Nom du produit *</label>
            <input type="text" id="nom" name="nom" required
                   value="<?= $produit_existant ? htmlspecialchars($produit_existant['nom']) : '' ?>">
        </div>

        <div class="champ-formulaire">
            <label for="prix_unitaire_ht">Prix unitaire HT (en <?= MONNAIE ?>) *</label>
            <input type="number" id="prix_unitaire_ht" name="prix_unitaire_ht" min="0" step="0.01" required
                   value="<?= $produit_existant ? $produit_existant['prix_unitaire_ht'] : '' ?>">
        </div>

        <div class="champ-formulaire">
            <label for="date_expiration">Date d'expiration (MM-JJ-AAAA) *</label>
            <input type="text" id="date_expiration" name="date_expiration" placeholder="12-31-2026" required
                   pattern="\d{2}-\d{2}-\d{4}"
                   value="<?= $produit_existant ? htmlspecialchars($produit_existant['date_expiration']) : '' ?>">
            <small style="color: #6b7280;">Format: MM-JJ-AAAA (exemple: 12-31-2026)</small>
        </div>

        <div class="champ-formulaire">
            <label for="quantite_stock">Quantité en stock *</label>
            <input type="number" id="quantite_stock" name="quantite_stock" min="0" required
                   value="<?= $produit_existant ? $produit_existant['quantite_stock'] : '' ?>">
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" name="enregistrer" class="btn-primaire" id="btn-enregistrer" disabled>
                💾 Enregistrer le produit
            </button>
            <a href="/facturation/modules/produits/liste.php" class="btn-secondaire">
                📋 Voir la liste des produits
            </a>
        </div>
    </form>
</div>

<?php
$script_supplementaire = '
<script src="https://cdn.jsdelivr.net/npm/@ericblade/quagga2/dist/quagga.min.js"></script>
<script src="/facturation/assets/js/scanner.js"></script>
';
include __DIR__ . '/../../includes/footer.php';
?>
