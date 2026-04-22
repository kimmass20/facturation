<?php
session_start();
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';
require_once __DIR__ . '/../../includes/fonctions-factures.php';

requiert_role(ROLE_CAISSIER);

$titre_page = 'Nouvelle facture';

// Initialise le panier de la facture en session
if (!isset($_SESSION['facture_en_cours'])) {
    $_SESSION['facture_en_cours'] = [];
}

$erreur = '';
$message = '';
$produit_trouve = null;

// Traitement de l'ajout d'un article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_article'])) {
    $code_barre = trim($_POST['code_barre'] ?? '');
    $quantite = (int)($_POST['quantite'] ?? 1);

    if (empty($code_barre)) {
        $erreur = "Veuillez scanner ou saisir un code-barres.";
    } elseif ($quantite <= 0) {
        $erreur = "La quantité doit être supérieure à zéro.";
    } else {
        $produit = trouver_produit($code_barre);

        if (!$produit) {
            $erreur = "Produit inconnu. Veuillez demander au Manager de l'enregistrer.";
        } elseif ($produit['quantite_stock'] < $quantite) {
            $erreur = "Stock insuffisant. Stock disponible : " . $produit['quantite_stock'] . " unités.";
        } else {
            // Ajoute l'article à la facture
            $article = [
                'code_barre' => $produit['code_barre'],
                'nom' => $produit['nom'],
                'prix_unitaire_ht' => $produit['prix_unitaire_ht'],
                'quantite' => $quantite,
                'sous_total_ht' => $produit['prix_unitaire_ht'] * $quantite
            ];

            $_SESSION['facture_en_cours'][] = $article;
            $message = "Article ajouté à la facture !";
        }
    }
}

// Suppression d'un article
if (isset($_GET['supprimer']) && is_numeric($_GET['supprimer'])) {
    $index = (int)$_GET['supprimer'];
    if (isset($_SESSION['facture_en_cours'][$index])) {
        unset($_SESSION['facture_en_cours'][$index]);
        $_SESSION['facture_en_cours'] = array_values($_SESSION['facture_en_cours']);
        $message = "Article supprimé de la facture.";
    }
}

// Validation de la facture
if (isset($_POST['valider_facture'])) {
    if (empty($_SESSION['facture_en_cours'])) {
        $erreur = "La facture est vide.";
    } else {
        // Enregistre la facture
        $facture = enregistrer_facture(utilisateur_connecte(), $_SESSION['facture_en_cours']);

        if ($facture) {
            // Décrémente le stock de chaque produit
            foreach ($_SESSION['facture_en_cours'] as $article) {
                decrementer_stock($article['code_barre'], $article['quantite']);
            }

            // Redirige vers l'affichage de la facture
            $_SESSION['facture_en_cours'] = [];
            header('Location: /facturation/modules/facturation/afficher-facture.php?id=' . urlencode($facture['id_facture']));
            exit();
        } else {
            $erreur = "Erreur lors de l'enregistrement de la facture.";
        }
    }
}

// Annulation de la facture
if (isset($_POST['annuler_facture'])) {
    $_SESSION['facture_en_cours'] = [];
    $message = "Facture annulée.";
}

// Vérification d'un code-barres scanné
if (isset($_GET['code_barre']) && !empty($_GET['code_barre'])) {
    $code_barre_scanne = trim($_GET['code_barre']);
    $produit_trouve = trouver_produit($code_barre_scanne);
}

include __DIR__ . '/../../includes/header.php';
?>

<h2>🧾 Nouvelle facture</h2>

<?php if ($message): ?>
    <div class="message-succes">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($erreur): ?>
    <div class="message-erreur">
        <?= htmlspecialchars($erreur) ?>
    </div>
<?php endif; ?>

<div class="scanner-container">
    <h3>Scanner le code-barres</h3>

    <div id="scanner-viewport"></div>

    <div class="scanner-controls">
        <button id="btn-demarrer-scanner" class="btn-primaire">📷 Démarrer le scanner</button>
        <button id="btn-arreter-scanner" class="btn-secondaire" style="display: none;">⏹️ Arrêter le scanner</button>
    </div>

    <div id="scanner-resultat" class="scanner-resultat" style="display: none;">
        <p><strong>Code-barres détecté :</strong> <span id="code-barre-detecte"></span></p>
    </div>
</div>

<div class="carte">
    <h3>Ajouter un article</h3>

    <form method="post">
        <input type="hidden" name="code_barre" id="input-code-barre" value="<?= isset($code_barre_scanne) ? htmlspecialchars($code_barre_scanne) : '' ?>">

        <div class="champ-formulaire">
            <label>Code-barres</label>
            <input type="text" id="affichage-code-barre" readonly
                   value="<?= isset($code_barre_scanne) ? htmlspecialchars($code_barre_scanne) : '' ?>"
                   placeholder="Scannez un code-barres">
        </div>

        <?php if ($produit_trouve): ?>
        <div class="message-succes">
            <strong>✓ Produit trouvé :</strong> <?= htmlspecialchars($produit_trouve['nom']) ?><br>
            <strong>Prix HT :</strong> <?= formater_prix($produit_trouve['prix_unitaire_ht']) ?><br>
            <strong>Stock disponible :</strong> <?= $produit_trouve['quantite_stock'] ?> unités
        </div>

        <div class="champ-formulaire">
            <label for="quantite">Quantité *</label>
            <input type="number" id="quantite" name="quantite" min="1" max="<?= $produit_trouve['quantite_stock'] ?>"
                   value="1" required autofocus>
        </div>

        <button type="submit" name="ajouter_article" class="btn-primaire">+ Ajouter à la facture</button>
        <?php endif; ?>
    </form>
</div>

<?php if (!empty($_SESSION['facture_en_cours'])): ?>
<div class="liste-articles-facture">
    <h3>Articles de la facture</h3>

    <table class="facture-tableau">
        <thead>
            <tr>
                <th>Désignation</th>
                <th>Prix unit. HT</th>
                <th>Qté</th>
                <th>Sous-total HT</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($_SESSION['facture_en_cours'] as $index => $article): ?>
            <tr>
                <td><?= htmlspecialchars($article['nom']) ?></td>
                <td><?= formater_prix($article['prix_unitaire_ht']) ?></td>
                <td><?= $article['quantite'] ?></td>
                <td><?= formater_prix($article['sous_total_ht']) ?></td>
                <td>
                    <a href="?supprimer=<?= $index ?>" class="btn-danger"
                       onclick="return confirm('Supprimer cet article ?')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php
    $total_ht = calculer_total_ht($_SESSION['facture_en_cours']);
    $tva = calculer_tva($total_ht);
    $total_ttc = calculer_total_ttc($total_ht);
    ?>

    <div class="facture-totaux">
        <div class="ligne-total">
            <span>Total HT</span>
            <span><?= formater_prix($total_ht) ?></span>
        </div>
        <div class="ligne-total">
            <span>TVA (<?= (TAUX_TVA * 100) ?>%)</span>
            <span><?= formater_prix($tva) ?></span>
        </div>
        <div class="ligne-total total-final">
            <span>Net à payer</span>
            <span><?= formater_prix($total_ttc) ?></span>
        </div>
    </div>

    <form method="post" style="display: flex; gap: 1rem; margin-top: 1.5rem;">
        <button type="submit" name="valider_facture" class="btn-primaire">
            ✓ Valider la facture
        </button>
        <button type="submit" name="annuler_facture" class="btn-danger"
                onclick="return confirm('Annuler cette facture ?')">
            ✕ Annuler
        </button>
    </form>
</div>
<?php endif; ?>

<?php
$script_supplementaire = '
<script src="https://cdn.jsdelivr.net/npm/@ericblade/quagga2/dist/quagga.min.js"></script>
<script>
// Scanner pour la facturation
let scannerActif = false;

const configQuagga = {
    inputStream: {
        name: "Live",
        type: "LiveStream",
        target: document.querySelector("#scanner-viewport"),
        constraints: {
            width: 640,
            height: 480,
            facingMode: "environment"
        }
    },
    decoder: {
        readers: ["ean_reader", "ean_8_reader", "code_128_reader", "code_39_reader", "upc_reader", "upc_e_reader"]
    },
    locate: true
};

function demarrerScanner() {
    if (scannerActif) return;

    Quagga.init(configQuagga, function(err) {
        if (err) {
            alert("Impossible d\'accéder à la caméra.");
            return;
        }
        Quagga.start();
        scannerActif = true;
        document.getElementById("btn-demarrer-scanner").style.display = "none";
        document.getElementById("btn-arreter-scanner").style.display = "inline-block";
    });

    Quagga.onDetected(function(result) {
        const code = result.codeResult.code;
        document.getElementById("code-barre-detecte").textContent = code;
        document.getElementById("scanner-resultat").style.display = "block";
        document.getElementById("input-code-barre").value = code;
        document.getElementById("affichage-code-barre").value = code;
        arreterScanner();
        window.location.href = "?code_barre=" + encodeURIComponent(code);
    });
}

function arreterScanner() {
    if (!scannerActif) return;
    Quagga.stop();
    scannerActif = false;
    document.getElementById("btn-demarrer-scanner").style.display = "inline-block";
    document.getElementById("btn-arreter-scanner").style.display = "none";
}

document.getElementById("btn-demarrer-scanner").addEventListener("click", demarrerScanner);
document.getElementById("btn-arreter-scanner").addEventListener("click", arreterScanner);
</script>
';

include __DIR__ . '/../../includes/footer.php';
?>
