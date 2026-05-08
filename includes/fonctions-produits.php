<?php
/**
 * Fonctions de gestion des produits
 */

// Constantes partagées du projet : chemins, formats, monnaie, etc.
require_once __DIR__ . '/../config/config.php';

/**
 * Normalise un code-barres avant stockage ou recherche
 */
function normaliser_code_barre($code_barre) {
    return preg_replace('/\s+/', '', trim((string)$code_barre));
}

/**
 * Charge tous les produits depuis le fichier JSON
 */
function charger_produits() {
    // Même stratégie de persistance que pour les factures : stockage JSON sur disque.
    if (!file_exists(FICHIER_PRODUITS)) {
        return [];
    }

    $contenu = file_get_contents(FICHIER_PRODUITS);
    $produits = json_decode($contenu, true);

    // Fallback utile si le fichier est vide ou mal formé.
    return $produits ?: [];
}

/**
 * Sauvegarde les produits dans le fichier JSON
 */
function sauvegarder_produits($produits) {
    // Encodage pretty print pour conserver un fichier lisible par un humain.
    $json = json_encode($produits, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents(FICHIER_PRODUITS, $json) !== false;
}

/**
 * Trouve un produit par son code-barres
 */
function trouver_produit($code_barre) {
    // Le code-barres joue ici le rôle d'identifiant fonctionnel du produit.
    $code_barre = normaliser_code_barre($code_barre);
    $produits = charger_produits();

    foreach ($produits as $produit) {
        if (normaliser_code_barre($produit['code_barre']) === $code_barre) {
            return $produit;
        }
    }

    return null;
}

/**
 * Enregistre un nouveau produit
 */
function enregistrer_produit($code_barre, $nom, $prix_unitaire_ht, $date_expiration, $quantite_stock) {
    // On charge le catalogue courant pour savoir si on crée ou si on remplace.
    $code_barre = normaliser_code_barre($code_barre);
    $produits = charger_produits();

    // Recherche d'un éventuel produit existant avec le même code-barres.
    $index_existant = -1;
    foreach ($produits as $index => $produit) {
        if (normaliser_code_barre($produit['code_barre']) === $code_barre) {
            $index_existant = $index;
            break;
        }
    }

    // Snapshot complet du produit à enregistrer.
    $nouveau_produit = [
        'code_barre' => $code_barre,
        'nom' => $nom,
        'prix_unitaire_ht' => (float)$prix_unitaire_ht,
        'date_expiration' => $date_expiration,
        'quantite_stock' => (int)$quantite_stock,
        'date_enregistrement' => date(FORMAT_DATE)
    ];

    if ($index_existant >= 0) {
        // Upsert simplifié : si le produit existe, on l'écrase avec la nouvelle version.
        $produits[$index_existant] = $nouveau_produit;
    } else {
        // Sinon on ajoute une nouvelle entrée au catalogue.
        $produits[] = $nouveau_produit;
    }

    return sauvegarder_produits($produits);
}

/**
 * Met à jour le stock d'un produit
 */
function mettre_a_jour_stock($code_barre, $nouvelle_quantite) {
    // Mutation ciblée de la quantité stockée pour un produit donné.
    $code_barre = normaliser_code_barre($code_barre);
    $produits = charger_produits();

    foreach ($produits as &$produit) {
        if (normaliser_code_barre($produit['code_barre']) === $code_barre) {
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
    // Utilisé juste après la validation d'une facture pour refléter la sortie de stock.
    $code_barre = normaliser_code_barre($code_barre);
    $produits = charger_produits();

    foreach ($produits as &$produit) {
        if (normaliser_code_barre($produit['code_barre']) === $code_barre) {
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
    // Validation serveur des champs saisis dans le formulaire produit.
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

    // Validation de date au format attendu par cette application : MM-JJ-AAAA.
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
    // number_format gère l'affichage français du montant, puis on ajoute la devise.
    return number_format($montant, 2, ',', ' ') . ' ' . MONNAIE;
}

/**
 * Convertit une date MM-JJ-AAAA en AAAA-MM-JJ
 */
function convertir_date_us_vers_iso($date_us) {
    // Cette conversion permet d'avoir un format cohérent pour le stockage.
    if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $date_us)) {
        return $date_us;
    }
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
    // Conversion inverse pour l'affichage ou la réédition dans les formulaires.
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_iso)) {
        return $date_iso;
    }
    $parts = explode('-', $date_iso);
    if (count($parts) === 3) {
        return $parts[1] . '-' . $parts[2] . '-' . $parts[0];
    }
    return $date_iso;
}
?>
