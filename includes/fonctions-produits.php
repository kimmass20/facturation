<?php
/**
 * Fonctions de gestion des produits
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Charge tous les produits depuis le fichier JSON
 */
function charger_produits() {
    if (!file_exists(FICHIER_PRODUITS)) {
        return [];
    }

    $contenu = file_get_contents(FICHIER_PRODUITS);
    $produits = json_decode($contenu, true);

    return $produits ?: [];
}

/**
 * Sauvegarde les produits dans le fichier JSON
 */
function sauvegarder_produits($produits) {
    $json = json_encode($produits, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents(FICHIER_PRODUITS, $json) !== false;
}

/**
 * Trouve un produit par son code-barres
 */
function trouver_produit($code_barre) {
    $produits = charger_produits();

    foreach ($produits as $produit) {
        if ($produit['code_barre'] === $code_barre) {
            return $produit;
        }
    }

    return null;
}

/**
 * Enregistre un nouveau produit
 */
function enregistrer_produit($code_barre, $nom, $prix_unitaire_ht, $date_expiration, $quantite_stock) {
    $produits = charger_produits();

    // Vérifie si le produit existe déjà
    $index_existant = -1;
    foreach ($produits as $index => $produit) {
        if ($produit['code_barre'] === $code_barre) {
            $index_existant = $index;
            break;
        }
    }

    $nouveau_produit = [
        'code_barre' => $code_barre,
        'nom' => $nom,
        'prix_unitaire_ht' => (float)$prix_unitaire_ht,
        'date_expiration' => $date_expiration,
        'quantite_stock' => (int)$quantite_stock,
        'date_enregistrement' => date(FORMAT_DATE)
    ];

    if ($index_existant >= 0) {
        // Met à jour le produit existant
        $produits[$index_existant] = $nouveau_produit;
    } else {
        // Ajoute un nouveau produit
        $produits[] = $nouveau_produit;
    }

    return sauvegarder_produits($produits);
}

/**
 * Met à jour le stock d'un produit
 */
function mettre_a_jour_stock($code_barre, $nouvelle_quantite) {
    $produits = charger_produits();

    foreach ($produits as &$produit) {
        if ($produit['code_barre'] === $code_barre) {
            $produit['quantite_stock'] = (int)$nouvelle_quantite;
            return sauvegarder_produits($produits);
        }
    }

    return false;
}

/**
 * Décrémente le stock d'un produit
 */
function decrementer_stock($code_barre, $quantite_vendue) {
    $produits = charger_produits();

    foreach ($produits as &$produit) {
        if ($produit['code_barre'] === $code_barre) {
            $produit['quantite_stock'] -= (int)$quantite_vendue;
            return sauvegarder_produits($produits);
        }
    }

    return false;
}

/**
 * Valide les données d'un produit
 */
function valider_produit($nom, $prix_unitaire_ht, $date_expiration, $quantite_stock) {
    $erreurs = [];

    if (empty(trim($nom))) {
        $erreurs[] = "Le nom du produit est obligatoire.";
    }

    if (!is_numeric($prix_unitaire_ht) || $prix_unitaire_ht < 0) {
        $erreurs[] = "Le prix unitaire doit être un nombre positif.";
    }

    if (!is_numeric($quantite_stock) || $quantite_stock < 0) {
        $erreurs[] = "La quantité en stock doit être un nombre positif.";
    }

    // Valide le format de date MM-JJ-AAAA
    $date_parts = explode('-', $date_expiration);
    if (count($date_parts) !== 3) {
        $erreurs[] = "Le format de date doit être MM-JJ-AAAA.";
    } else {
        list($mois, $jour, $annee) = $date_parts;
        if (!checkdate($mois, $jour, $annee)) {
            $erreurs[] = "La date d'expiration n'est pas valide.";
        }
    }

    return $erreurs;
}

/**
 * Formate le prix avec la monnaie
 */
function formater_prix($montant) {
    return number_format($montant, 2, ',', ' ') . ' ' . MONNAIE;
}

/**
 * Convertit une date MM-JJ-AAAA en AAAA-MM-JJ
 */
function convertir_date_us_vers_iso($date_us) {
    $parts = explode('-', $date_us);
    if (count($parts) === 3) {
        return $parts[2] . '-' . $parts[0] . '-' . $parts[1];
    }
    return $date_us;
}

/**
 * Convertit une date AAAA-MM-JJ en MM-JJ-AAAA
 */
function convertir_date_iso_vers_us($date_iso) {
    $parts = explode('-', $date_iso);
    if (count($parts) === 3) {
        return $parts[1] . '-' . $parts[2] . '-' . $parts[0];
    }
    return $date_iso;
}
?>
