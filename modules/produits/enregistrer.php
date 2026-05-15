<?php
// Dépendances de sécurité, catalogue produit et paramètres système.
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';

// L'enregistrement produit est réservé aux rôles autorisés.
requiert_permission('gerer_produits');

// Variables de page et de feedback UI.
$titre_page = 'Enregistrer un produit';
$message = '';
$erreurs = [];
$produit_existant = null;
$code_barre_scanne = '';

// Message de succès transmis via redirection POST→GET.
if (isset($_GET['succes'])) {
    $message = "Produit enregistré avec succès !";
}

// Contrôleur principal du formulaire d'ajout / mise à jour d'un produit.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer'])) {
    requiert_csrf();

    // Lecture et nettoyage des données du formulaire.
    $code_barre = normaliser_code_barre($_POST['code_barre'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prix_unitaire_ht = trim($_POST['prix_unitaire_ht'] ?? '');
    $date_expiration = trim($_POST['date_expiration'] ?? '');
    $quantite_stock = trim($_POST['quantite_stock'] ?? '');

    // Validation métier générique des données produit.
    $erreurs = valider_produit($nom, $prix_unitaire_ht, $date_expiration, $quantite_stock);

    // Le code-barres est validé ici séparément car il est au cœur du workflow scanner.
    if (empty($code_barre)) {
        $erreurs[] = "Le code-barres est obligatoire.";
    }

    if (empty($erreurs)) {
        // Conversion prévue pour le stockage normalisé.
        $date_expiration_iso = convertir_date_us_vers_iso($date_expiration);

        // Le helper enregistrer_produit agit comme un upsert simplifié.
        if (enregistrer_produit($code_barre, $nom, $prix_unitaire_ht, $date_expiration_iso, $quantite_stock)) {
            // Redirection PRG : évite le re-submit au refresh et remet la page en état
            // "propre" (sans ?code_barre=) pour que le scanner puisse redémarrer automatiquement.
            header('Location: /facturation/modules/produits/enregistrer.php?succes=1');
            exit();
        } else {
            $erreurs[] = "Erreur lors de l'enregistrement du produit.";
        }
    }
}

// Si le scanner a renvoyé un code-barres dans l'URL, on récupère le produit correspondant.
if (isset($_GET['code_barre']) && !empty($_GET['code_barre'])) {
    $code_barre_scanne = normaliser_code_barre($_GET['code_barre']);
    $produit_existant = trouver_produit($code_barre_scanne);
}

// Header partagé.
include __DIR__ . '/../../includes/header.php';
?>

<!-- Titre principal -->
<h2><i class="fa-solid fa-pen-to-square"></i> Enregistrer un produit</h2>

<!-- Message de succès -->
<?php if ($message): ?>
    <div class="message-succes">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- Messages d'erreurs de validation -->
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

<!-- Bloc caméra / scanner -->
<div class="scanner-container">
    <h3>Scanner le code-barres</h3>
    <p class="mb-2">Utilisez la caméra du PC pour scanner le code-barres du produit. En cas d'échec, vous pouvez encore saisir le code manuellement.</p>

    <video id="scanner-viewport" autoplay muted playsinline></video>

    <div class="scanner-controls">
        <button id="btn-demarrer-scanner" class="btn-primaire"><i class="fa-solid fa-camera"></i> Démarrer le scanner</button>
        <button id="btn-arreter-scanner" class="btn-secondaire" style="display: none;"><i class="fa-solid fa-stop"></i> Arrêter le scanner</button>
    </div>

    <div id="scanner-message" class="message-info" style="display: none;"></div>

    <div id="scanner-resultat" class="scanner-resultat" style="display: none;">
        <p><strong>Code-barres détecté :</strong> <span id="code-barre-detecte"></span></p>
    </div>
</div>

<!-- Si un produit existe déjà pour ce code-barres, on prévient l'utilisateur -->
<?php if ($produit_existant): ?>
    <div class="message-info">
        <strong><i class="fa-solid fa-circle-info"></i> Produit existant</strong><br>
        Ce code-barres est déjà enregistré : <strong><?= htmlspecialchars($produit_existant['nom']) ?></strong><br>
        Vous pouvez modifier ses informations ci-dessous.
    </div>
<?php elseif ($code_barre_scanne !== ''): ?>
    <div class="message-info">
        <strong><i class="fa-solid fa-barcode"></i> Nouveau code-barres détecté</strong><br>
        Renseignez les informations du produit puis enregistrez-le dans la base.
    </div>
<?php endif; ?>

<!-- Carte de saisie / édition du produit -->
<div class="carte">
    <h3>Informations du produit</h3>

    <form method="post" id="formulaire-produit">
        <?= champ_csrf() ?>
        <!-- Champ technique réellement envoyé au serveur -->
        <input type="hidden" name="code_barre" id="input-code-barre" value="<?= htmlspecialchars($code_barre_scanne) ?>">

        <div class="champ-formulaire">
            <label>Code-barres</label>
             <input type="text" id="affichage-code-barre" inputmode="numeric" autocomplete="off"
                   value="<?= htmlspecialchars($code_barre_scanne) ?>"
                 placeholder="Scannez ou saisissez un code-barres puis appuyez sur Entrée">
                         <small style="color: #6b7280;">La webcam remplit ce champ automatiquement après lecture du code-barres.</small>
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
                   value="<?= $produit_existant ? htmlspecialchars(convertir_date_iso_vers_us($produit_existant['date_expiration'])) : '' ?>">
            <small style="color: #6b7280;">Format: MM-JJ-AAAA (exemple: 12-31-2026)</small>
        </div>

        <div class="champ-formulaire">
            <label for="quantite_stock">Quantité en stock *</label>
            <input type="number" id="quantite_stock" name="quantite_stock" min="0" required
                   value="<?= $produit_existant ? $produit_existant['quantite_stock'] : '' ?>">
        </div>

        <!-- Actions de persistance et de navigation -->
        <div style="display: flex; gap: 1rem;">
            <button type="submit" name="enregistrer" class="btn-primaire" id="btn-enregistrer" disabled>
                <i class="fa-solid fa-floppy-disk"></i> Enregistrer le produit
            </button>
            <a href="/facturation/modules/produits/liste.php" class="btn-secondaire">
                <i class="fa-solid fa-table-list"></i> Voir la liste des produits
            </a>
        </div>
    </form>
</div>

<?php
// Injection des scripts spécifiques à cette page : ZXing + wrapper scanner local.
$script_supplementaire = '
<script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>
<script src="/facturation/assets/js/scanner.js"></script>
';
// Footer partagé.
include __DIR__ . '/../../includes/footer.php';
?>
