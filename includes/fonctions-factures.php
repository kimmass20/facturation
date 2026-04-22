<?php
/**
 * Fonctions de gestion des factures
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Charge toutes les factures depuis le fichier JSON
 */
function charger_factures() {
    if (!file_exists(FICHIER_FACTURES)) {
        return [];
    }

    $contenu = file_get_contents(FICHIER_FACTURES);
    $factures = json_decode($contenu, true);

    return $factures ?: [];
}

/**
 * Sauvegarde les factures dans le fichier JSON
 */
function sauvegarder_factures($factures) {
    $json = json_encode($factures, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents(FICHIER_FACTURES, $json) !== false;
}

/**
 * Génère un ID unique pour une facture
 */
function generer_id_facture() {
    $date = date('Ymd');
    $factures = charger_factures();

    // Compte les factures du jour
    $compteur = 0;
    foreach ($factures as $facture) {
        if (strpos($facture['id_facture'], 'FAC-' . $date) === 0) {
            $compteur++;
        }
    }

    $compteur++;
    return sprintf('FAC-%s-%03d', $date, $compteur);
}

/**
 * Calcule le total HT d'une liste d'articles
 */
function calculer_total_ht($articles) {
    $total = 0;
    foreach ($articles as $article) {
        $total += $article['sous_total_ht'];
    }
    return $total;
}

/**
 * Calcule la TVA
 */
function calculer_tva($total_ht) {
    return $total_ht * TAUX_TVA;
}

/**
 * Calcule le total TTC
 */
function calculer_total_ttc($total_ht) {
    return $total_ht + calculer_tva($total_ht);
}

/**
 * Enregistre une nouvelle facture
 */
function enregistrer_facture($caissier, $articles) {
    $factures = charger_factures();

    $total_ht = calculer_total_ht($articles);
    $tva = calculer_tva($total_ht);
    $total_ttc = calculer_total_ttc($total_ht);

    $nouvelle_facture = [
        'id_facture' => generer_id_facture(),
        'date' => date('Y-m-d'),
        'heure' => date('H:i:s'),
        'caissier' => $caissier,
        'articles' => $articles,
        'total_ht' => round($total_ht, 2),
        'tva' => round($tva, 2),
        'total_ttc' => round($total_ttc, 2)
    ];

    $factures[] = $nouvelle_facture;

    if (sauvegarder_factures($factures)) {
        return $nouvelle_facture;
    }

    return false;
}

/**
 * Trouve une facture par son ID
 */
function trouver_facture($id_facture) {
    $factures = charger_factures();

    foreach ($factures as $facture) {
        if ($facture['id_facture'] === $id_facture) {
            return $facture;
        }
    }

    return null;
}

/**
 * Obtient les factures d'une période
 */
function obtenir_factures_periode($date_debut, $date_fin) {
    $factures = charger_factures();

    return array_filter($factures, function($facture) use ($date_debut, $date_fin) {
        return $facture['date'] >= $date_debut && $facture['date'] <= $date_fin;
    });
}

/**
 * Obtient les factures du jour
 */
function obtenir_factures_jour($date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }

    return obtenir_factures_periode($date, $date);
}

/**
 * Calcule les statistiques d'une liste de factures
 */
function calculer_statistiques_factures($factures) {
    $stats = [
        'nombre_factures' => count($factures),
        'total_ht' => 0,
        'total_tva' => 0,
        'total_ttc' => 0
    ];

    foreach ($factures as $facture) {
        $stats['total_ht'] += $facture['total_ht'];
        $stats['total_tva'] += $facture['tva'];
        $stats['total_ttc'] += $facture['total_ttc'];
    }

    return $stats;
}
?>
