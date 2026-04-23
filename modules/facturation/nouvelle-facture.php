<?php
// Démarre la session PHP et active les helpers d'autorisation.
session_start();

// Dépendances métier : session/sécurité, produits et factures.
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-produits.php';
require_once __DIR__ . '/../../includes/fonctions-factures.php';

// La création de facture est une capability métier réservée aux rôles autorisés.
requiert_permission('creer_facture');

// Titre injecté dans le header partagé.
$titre_page = 'Nouvelle facture';

// On utilise la session comme panier temporaire de facture en cours.
// Si le panier n'existe pas encore, on l'initialise avec un tableau vide.
if (!isset($_SESSION['facture_en_cours'])) {
    $_SESSION['facture_en_cours'] = [];
}

// Variables d'interface pour afficher un retour utilisateur.
$erreur = '';
$message = '';

// Cette variable servira à préremplir l'interface après un scan de code-barres.
$produit_trouve = null;

// Cas d'usage n°1 : ajout d'un article dans le panier de facture.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_article'])) {
    // Protection CSRF sur l'action sensible.
    requiert_csrf();

    // Lecture et normalisation des données du formulaire.
    $code_barre = trim($_POST['code_barre'] ?? '');
    $quantite = (int)($_POST['quantite'] ?? 1);

    // Validation métier basique.
    if (empty($code_barre)) {
        $erreur = "Veuillez scanner ou saisir un code-barres.";
    } elseif ($quantite <= 0) {
        $erreur = "La quantité doit être supérieure à zéro.";
    } else {
        // Lookup produit à partir du code-barres.
        $produit = trouver_produit($code_barre);

        if (!$produit) {
            $erreur = "Produit inconnu. Veuillez demander au Manager de l'enregistrer.";
        } elseif ($produit['quantite_stock'] < $quantite) {
            $erreur = "Stock insuffisant. Stock disponible : " . $produit['quantite_stock'] . " unités.";
        } else {
            // On construit ici un snapshot de l'article au moment de la vente.
            // C'est utile car le prix du produit pourrait changer plus tard dans le catalogue.
            $article = [
                'code_barre' => $produit['code_barre'],
                'nom' => $produit['nom'],
                'prix_unitaire_ht' => $produit['prix_unitaire_ht'],
                'quantite' => $quantite,
                'sous_total_ht' => $produit['prix_unitaire_ht'] * $quantite
            ];

            // On empile l'article dans le panier de facture stocké en session.
            $_SESSION['facture_en_cours'][] = $article;
            $message = "Article ajouté à la facture !";
        }
    }
}

// Cas d'usage n°2 : suppression d'un article du panier.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_article'])) {
    requiert_csrf();

    // L'index est la position de l'article dans le tableau de session.
    $index = (int)($_POST['index_article'] ?? -1);
    if (isset($_SESSION['facture_en_cours'][$index])) {
        unset($_SESSION['facture_en_cours'][$index]);

        // array_values réindexe proprement le tableau après suppression.
        $_SESSION['facture_en_cours'] = array_values($_SESSION['facture_en_cours']);
        $message = "Article supprimé de la facture.";
    }
}

// Cas d'usage n°3 : validation finale de la facture.
if (isset($_POST['valider_facture'])) {
    requiert_csrf();

    // Une facture vide n'a pas de sens métier.
    if (empty($_SESSION['facture_en_cours'])) {
        $erreur = "La facture est vide.";
    } else {
        // Persistance de la facture complète dans le fichier JSON des factures.
        $facture = enregistrer_facture(utilisateur_connecte(), $_SESSION['facture_en_cours']);

        if ($facture) {
            // Transaction fonctionnelle simplifiée : on décrémente le stock après enregistrement.
            foreach ($_SESSION['facture_en_cours'] as $article) {
                decrementer_stock($article['code_barre'], $article['quantite']);
            }

            // Post/Redirect/Get : on redirige pour éviter le resubmit du formulaire au refresh.
            $_SESSION['facture_en_cours'] = [];
            header('Location: /facturation/modules/facturation/afficher-facture.php?id=' . urlencode($facture['id_facture']));
            exit();
        } else {
            $erreur = "Erreur lors de l'enregistrement de la facture.";
        }
    }
}

// Cas d'usage n°4 : annulation complète du panier de facture.
if (isset($_POST['annuler_facture'])) {
    requiert_csrf();
    $_SESSION['facture_en_cours'] = [];
    $message = "Facture annulée.";
}

// Cas d'usage n°5 : retour depuis le scanner avec un code-barres dans l'URL.
if (isset($_GET['code_barre']) && !empty($_GET['code_barre'])) {
    $code_barre_scanne = trim($_GET['code_barre']);
    $produit_trouve = trouver_produit($code_barre_scanne);
}

// Inclusion du header partagé.
include __DIR__ . '/../../includes/header.php';
?>

<!-- Titre principal de l'écran de facturation -->
<h2><i class="fa-solid fa-file-invoice"></i> Nouvelle facture</h2>

<!-- Message de succès après action -->
<?php if ($message): ?>
    <div class="message-succes">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- Message d'erreur fonctionnelle -->
<?php if ($erreur): ?>
    <div class="message-erreur">
        <?= htmlspecialchars($erreur) ?>
    </div>
<?php endif; ?>

<!-- Bloc dédié au scanner caméra -->
<div class="scanner-container">
    <h3>Scanner le code-barres</h3>

    <div id="scanner-viewport"></div>

    <div class="scanner-controls">
        <button id="btn-demarrer-scanner" class="btn-primaire"><i class="fa-solid fa-camera"></i> Démarrer le scanner</button>
        <button id="btn-arreter-scanner" class="btn-secondaire" style="display: none;"><i class="fa-solid fa-stop"></i> Arrêter le scanner</button>
    </div>

    <div id="scanner-resultat" class="scanner-resultat" style="display: none;">
        <p><strong>Code-barres détecté :</strong> <span id="code-barre-detecte"></span></p>
    </div>
</div>

<!-- Formulaire d'ajout manuel/assisté d'un article -->
<div class="carte">
    <h3>Ajouter un article</h3>

    <form method="post">
        <?= champ_csrf() ?>
        <!-- Ce champ hidden transporte la vraie valeur du code-barres pour le backend -->
        <input type="hidden" name="code_barre" id="input-code-barre" value="<?= isset($code_barre_scanne) ? htmlspecialchars($code_barre_scanne) : '' ?>">

        <div class="champ-formulaire">
            <label>Code-barres</label>
            <!-- Ce champ visible est en lecture seule : il sert seulement d'affichage utilisateur -->
            <input type="text" id="affichage-code-barre" readonly
                   value="<?= isset($code_barre_scanne) ? htmlspecialchars($code_barre_scanne) : '' ?>"
                   placeholder="Scannez un code-barres">
        </div>

        <!-- Ce bloc ne s'affiche que si le produit a été reconnu -->
        <?php if ($produit_trouve): ?>
        <div class="message-succes">
            <strong><i class="fa-solid fa-circle-check"></i> Produit trouvé :</strong> <?= htmlspecialchars($produit_trouve['nom']) ?><br>
            <strong>Prix HT :</strong> <?= formater_prix($produit_trouve['prix_unitaire_ht']) ?><br>
            <strong>Stock disponible :</strong> <?= $produit_trouve['quantite_stock'] ?> unités
        </div>

        <div class="champ-formulaire">
            <label for="quantite">Quantité *</label>
            <input type="number" id="quantite" name="quantite" min="1" max="<?= $produit_trouve['quantite_stock'] ?>"
                   value="1" required autofocus>
        </div>

        <button type="submit" name="ajouter_article" class="btn-primaire"><i class="fa-solid fa-plus"></i> Ajouter à la facture</button>
        <?php endif; ?>
    </form>
</div>

<!-- Le panier de facture ne s'affiche que s'il contient au moins un article -->
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
            <!-- Chaque ligne correspond à un article stocké en session -->
            <?php foreach ($_SESSION['facture_en_cours'] as $index => $article): ?>
            <tr>
                <td><?= htmlspecialchars($article['nom']) ?></td>
                <td><?= formater_prix($article['prix_unitaire_ht']) ?></td>
                <td><?= $article['quantite'] ?></td>
                <td><?= formater_prix($article['sous_total_ht']) ?></td>
                <td>
                    <!-- Action destructive : suppression d'une ligne du panier -->
                    <form method="post" style="display:inline;">
                        <?= champ_csrf() ?>
                        <input type="hidden" name="index_article" value="<?= $index ?>">
                        <button type="submit" name="supprimer_article" class="btn-danger"
                            onclick="return confirm('Supprimer cet article ?')"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php
    // Calculs d'agrégation de la facture.
    $total_ht = calculer_total_ht($_SESSION['facture_en_cours']);
    $tva = calculer_tva($total_ht);
    $total_ttc = calculer_total_ttc($total_ht);
    ?>

    <!-- Bloc de synthèse comptable -->
    <div class="facture-totaux">
        <div class="ligne-total">
            <span>Total HT</span>
            <span><?= formater_prix($total_ht) ?></span>
        </div>
        <div class="ligne-total">
            <span>TVA (<?= (float) parametre_systeme('taux_tva') * 100 ?>%)</span>
            <span><?= formater_prix($tva) ?></span>
        </div>
        <div class="ligne-total total-final">
            <span>Net à payer</span>
            <span><?= formater_prix($total_ttc) ?></span>
        </div>
    </div>

    <!-- Actions finales sur la facture -->
    <form method="post" style="display: flex; gap: 1rem; margin-top: 1.5rem;">
        <?= champ_csrf() ?>
        <button type="submit" name="valider_facture" class="btn-primaire">
            <i class="fa-solid fa-check"></i> Valider la facture
        </button>
        <button type="submit" name="annuler_facture" class="btn-danger"
                onclick="return confirm('Annuler cette facture ?')">
            <i class="fa-solid fa-xmark"></i> Annuler
        </button>
    </form>
</div>
<?php endif; ?>

<?php
// On injecte ici du JavaScript spécifique à cette page via une variable du footer.
$script_supplementaire = '
<script src="https://cdn.jsdelivr.net/npm/@ericblade/quagga2/dist/quagga.min.js"></script>
<script>
// Module front-end de scan code-barres pour la page de facturation.
let scannerActif = false;

const configQuagga = {
    // inputStream décrit la source vidéo que Quagga doit analyser.
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
    // decoder liste les formats de codes-barres que l\'on accepte.
    decoder: {
        readers: ["ean_reader", "ean_8_reader", "code_128_reader", "code_39_reader", "upc_reader", "upc_e_reader"]
    },
    locate: true
};

function demarrerScanner() {
    // Guard clause : si le scanner tourne déjà, on évite une double initialisation.
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
        // Pipeline de détection : on récupère le code lu puis on l\'injecte dans l\'interface.
        const code = result.codeResult.code;
        document.getElementById("code-barre-detecte").textContent = code;
        document.getElementById("scanner-resultat").style.display = "block";
        document.getElementById("input-code-barre").value = code;
        document.getElementById("affichage-code-barre").value = code;

        // On arrête la caméra puis on recharge la page avec le code dans l\'URL.
        arreterScanner();
        window.location.href = "?code_barre=" + encodeURIComponent(code);
    });
}

function arreterScanner() {
    // On stoppe proprement le flux vidéo et on remet l\'interface dans son état initial.
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

// Footer partagé + injection du script supplémentaire défini juste au-dessus.
include __DIR__ . '/../../includes/footer.php';
?>
