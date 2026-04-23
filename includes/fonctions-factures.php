<?php
/**
 * Fonctions de gestion des factures
 */

// Dépendances globales : constantes et accès aux paramètres système.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/fonctions-auth.php';

/**
 * Charge toutes les factures depuis le fichier JSON
 */
function charger_factures() {
    // Le projet persiste les factures en JSON, sans base de données relationnelle.
    if (!file_exists(FICHIER_FACTURES)) {
        return [];
    }

    $contenu = file_get_contents(FICHIER_FACTURES);
    $factures = json_decode($contenu, true);

    // Fallback vers un tableau vide si le JSON est vide ou corrompu.
    return $factures ?: [];
}

/**
 * Sauvegarde les factures dans le fichier JSON
 */
function sauvegarder_factures($factures) {
    // On sérialise au format lisible pour faciliter le debug manuel du fichier.
    $json = json_encode($factures, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents(FICHIER_FACTURES, $json) !== false;
}

/**
 * Génère un ID unique pour une facture
 */
function generer_id_facture() {
    // Préfixe fonctionnel + date du jour pour construire un identifiant lisible.
    $date = date('Ymd');
    $factures = charger_factures();

    // On compte les factures déjà émises aujourd'hui pour incrémenter le suffixe.
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
    // Agrégation simple de tous les sous-totaux hors taxe.
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
    // La TVA est pilotée par le paramètre système et non par une constante codée en dur.
    return $total_ht * (float) parametre_systeme('taux_tva');
}

/**
 * Calcule le total TTC
 */
function calculer_total_ttc($total_ht) {
    // TTC = HT + TVA.
    return $total_ht + calculer_tva($total_ht);
}

/**
 * Enregistre une nouvelle facture
 */
function enregistrer_facture($caissier, $articles) {
    // On charge d'abord l'existant pour append la nouvelle facture.
    $factures = charger_factures();

    // Pré-calculs comptables de la facture.
    $total_ht = calculer_total_ht($articles);
    $tva = calculer_tva($total_ht);
    $total_ttc = calculer_total_ttc($total_ht);

    // Snapshot complet de la facture au moment de la vente.
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

    // On renvoie la facture créée seulement si la persistance a réussi.
    if (sauvegarder_factures($factures)) {
        return $nouvelle_facture;
    }

    return false;
}

/**
 * Trouve une facture par son ID
 */
function trouver_facture($id_facture) {
    // Lookup linéaire dans le tableau JSON des factures.
    $factures = charger_factures();

    foreach ($factures as $facture) {
        if ($facture['id_facture'] === $id_facture) {
            return $facture;
        }
    }

    return null;
}

/**
 * Obtient les factures d'un utilisateur donné.
 */
function obtenir_factures_utilisateur($identifiant) {
    // On filtre par propriétaire fonctionnel de la facture : le caissier.
    $factures = charger_factures();

    return array_values(array_filter($factures, function($facture) use ($identifiant) {
        return isset($facture['caissier']) && $facture['caissier'] === $identifiant;
    }));
}

/**
 * Obtient les factures d'une période
 */
function obtenir_factures_periode($date_debut, $date_fin) {
    // Filtre temporel inclusif sur la date de facture.
    $factures = charger_factures();

    return array_filter($factures, function($facture) use ($date_debut, $date_fin) {
        return $facture['date'] >= $date_debut && $facture['date'] <= $date_fin;
    });
}

/**
 * Obtient les factures du jour
 */
function obtenir_factures_jour($date = null) {
    // Si aucune date n'est fournie, on prend la date courante.
    if ($date === null) {
        $date = date('Y-m-d');
    }

    return obtenir_factures_periode($date, $date);
}

/**
 * Obtient les factures d'un utilisateur sur une période.
 */
function obtenir_factures_utilisateur_periode($identifiant, $date_debut, $date_fin) {
    // On combine deux filtres : ownership + période.
    $factures = obtenir_factures_utilisateur($identifiant);

    return array_values(array_filter($factures, function($facture) use ($date_debut, $date_fin) {
        return $facture['date'] >= $date_debut && $facture['date'] <= $date_fin;
    }));
}

/**
 * Retourne les dernières factures, de la plus récente à la plus ancienne.
 */
function obtenir_dernieres_factures($factures, $limite = 5) {
    // Tri décroissant par date + heure, puis pagination simplifiée par slice.
    usort($factures, function($a, $b) {
        $cleA = ($a['date'] ?? '') . ' ' . ($a['heure'] ?? '00:00:00');
        $cleB = ($b['date'] ?? '') . ' ' . ($b['heure'] ?? '00:00:00');
        return strcmp($cleB, $cleA);
    });

    return array_slice($factures, 0, $limite);
}

/**
 * Calcule les statistiques d'une liste de factures
 */
function calculer_statistiques_factures($factures) {
    // On construit ici un agrégat métier pour le dashboard.
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

    // Le tableau retourné sert de DTO simple pour la vue.
    return $stats;
}
?>
