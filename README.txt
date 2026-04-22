================================================================================
    SYSTÈME DE FACTURATION AVEC LECTURE DE CODES-BARRES
    Travaux Pratiques - Programmation Web PHP
    Université Protestante au Congo - L2 FASI
    Année académique 2025-2026
================================================================================

## DESCRIPTION

Application web de gestion de facturation développée en PHP procédural.
Le système permet :
- L'enregistrement de produits via scanner de codes-barres
- La création de factures avec calcul automatique des totaux (TVA 18%)
- La gestion des comptes utilisateurs avec contrôle d'accès (RBAC)
- La persistance des données via fichiers JSON

## PRÉREQUIS

- PHP 7.4 ou supérieur
- Serveur web (Apache, Nginx) ou PHP built-in server
- Navigateur moderne avec support de la caméra (pour le scanner)
- Connexion HTTPS recommandée (requise pour accès caméra sur certains navigateurs)

## INSTALLATION ET DÉPLOIEMENT LOCAL

### Méthode 1 : Serveur PHP intégré (recommandé pour les tests)

1. Ouvrez un terminal dans le dossier du projet
2. Lancez la commande :

   php -S localhost:8000 -t facturation

3. Ouvrez votre navigateur sur : http://localhost:8000

Note : Pour tester le scanner de codes-barres avec le serveur PHP intégré,
vous devrez peut-être utiliser un tunnel HTTPS (ex: ngrok) car certains
navigateurs n'autorisent l'accès à la caméra qu'en HTTPS.

### Méthode 2 : Apache/Nginx

1. Copiez le dossier 'facturation' dans le répertoire web de votre serveur
   - Apache (XAMPP/WAMP) : htdocs/facturation
   - Apache (Linux) : /var/www/html/facturation
   - Nginx : selon votre configuration

2. Assurez-vous que PHP est activé

3. Configurez les permissions (Linux) :
   chmod -R 755 facturation/
   chmod -R 777 facturation/data/

4. Accédez à l'URL : http://localhost/facturation

## COMPTES DE TEST

L'application est préchargée avec 3 comptes utilisateurs :

┌─────────────────┬──────────────┬──────────────┬─────────────────────────────┐
│ Rôle            │ Identifiant  │ Mot de passe │ Permissions                 │
├─────────────────┼──────────────┼──────────────┼─────────────────────────────┤
│ Super Admin     │ admin        │ admin123     │ Toutes les permissions      │
│ Manager         │ manager      │ manager123   │ Produits + Factures + Stats │
│ Caissier        │ caissier     │ caissier123  │ Factures uniquement         │
└─────────────────┴──────────────┴──────────────┴─────────────────────────────┘

⚠️ IMPORTANT : Changez ces mots de passe en production !

## PRODUITS DE TEST

3 produits sont préenregistrés pour tester le système :

1. Huile de palme 1L (code-barres : 3017620422003)
2. Savon de Marseille (code-barres : 3256220172882)
3. Riz parfumé 2kg (code-barres : 5410063021084)

## STRUCTURE DU PROJET

facturation/
├── index.php                       # Page d'accueil / Tableau de bord
├── config/
│   └── config.php                  # Configuration globale (TVA, monnaie, etc.)
├── auth/
│   ├── login.php                   # Page de connexion
│   ├── logout.php                  # Déconnexion
│   └── session.php                 # Gestion des sessions et contrôle d'accès
├── modules/
│   ├── produits/
│   │   ├── enregistrer.php         # Enregistrement produits avec scanner
│   │   └── liste.php               # Liste des produits
│   ├── facturation/
│   │   ├── nouvelle-facture.php    # Création de factures
│   │   └── afficher-facture.php    # Affichage/Impression factures
│   └── admin/
│       ├── gestion-comptes.php     # Liste des utilisateurs
│       ├── ajouter-compte.php      # Création de comptes
│       └── supprimer-compte.php    # Suppression de comptes
├── data/
│   ├── produits.json               # Base de données produits
│   ├── factures.json               # Base de données factures
│   └── utilisateurs.json           # Base de données utilisateurs
├── includes/
│   ├── header.php                  # En-tête HTML commun
│   ├── footer.php                  # Pied de page HTML commun
│   ├── fonctions-produits.php      # Fonctions gestion produits
│   ├── fonctions-factures.php      # Fonctions gestion factures
│   └── fonctions-auth.php          # Fonctions authentification
├── assets/
│   ├── css/
│   │   └── style.css               # Styles CSS
│   └── js/
│       └── scanner.js              # Scanner codes-barres (QuaggaJS)
└── README.txt                      # Ce fichier

## FONCTIONNALITÉS

### Partie 1 : Enregistrement des Produits (Manager + Super Admin)
- Scanner de codes-barres via caméra (QuaggaJS)
- Enregistrement : nom, prix HT, date d'expiration, stock
- Vérification si le produit existe déjà
- Modification de produits existants
- Validation des données côté serveur

### Partie 2 : Facturation (Caissier + Manager + Super Admin)
- Scanner de codes-barres pour ajouter des articles
- Recherche automatique du produit
- Vérification du stock disponible
- Calcul automatique : Total HT, TVA (18%), Total TTC
- Décrémentation automatique du stock
- Génération d'ID de facture unique (format : FAC-AAAAMMJJ-XXX)
- Affichage et impression de la facture

### Partie 3 : Gestion des Comptes (Super Admin uniquement)
- Création de comptes Caissier et Manager
- Suppression de comptes (sauf Super Admin)
- Contrôle d'accès basé sur les rôles (RBAC)
- Hachage sécurisé des mots de passe (password_hash)

## UTILISATION

### Scanner un code-barres
1. Cliquez sur "Démarrer le scanner"
2. Autorisez l'accès à la caméra
3. Présentez le code-barres devant la caméra
4. Le code est automatiquement détecté et rempli dans le formulaire

Note : Si vous n'avez pas de codes-barres physiques, vous pouvez :
- Utiliser les codes de test (voir section "Produits de test")
- Générer des codes-barres en ligne : https://barcode.tec-it.com
- Afficher un code-barres à l'écran et le scanner avec un téléphone

### Créer une facture
1. Connectez-vous avec un compte Caissier ou Manager
2. Cliquez sur "Nouvelle Facture"
3. Scannez les codes-barres des produits
4. Saisissez la quantité
5. Cliquez sur "Ajouter à la facture"
6. Répétez pour chaque article
7. Vérifiez le total et cliquez sur "Valider la facture"
8. La facture s'affiche et peut être imprimée

### Enregistrer un produit
1. Connectez-vous avec un compte Manager ou Super Admin
2. Cliquez sur "Enregistrer Produit"
3. Scannez le code-barres du nouveau produit
4. Remplissez les informations (nom, prix, date d'expiration, stock)
5. Cliquez sur "Enregistrer le produit"

### Gérer les comptes utilisateurs
1. Connectez-vous avec le compte Super Admin
2. Cliquez sur "Gestion Comptes"
3. Créez ou supprimez des comptes Caissier/Manager

## SÉCURITÉ

✓ Hachage des mots de passe avec password_hash() / password_verify()
✓ Validation des données côté serveur
✓ Contrôle d'accès basé sur les rôles (RBAC)
✓ Protection contre les injections (htmlspecialchars)
✓ Sessions PHP sécurisées

⚠️ Pour un usage en production, ajoutez :
- Protection CSRF
- Limitation du nombre de tentatives de connexion
- Logs d'audit
- Sauvegarde automatique des fichiers JSON
- HTTPS obligatoire

## TECHNOLOGIES UTILISÉES

- PHP 7.4+ (procédural)
- HTML5 / CSS3
- JavaScript (ES6)
- QuaggaJS (scanner codes-barres)
- JSON (persistance des données)

## DÉPANNAGE

### Le scanner ne fonctionne pas
- Vérifiez que vous utilisez HTTPS (ou localhost)
- Autorisez l'accès à la caméra dans votre navigateur
- Testez avec un autre navigateur (Chrome recommandé)
- Vérifiez que votre caméra fonctionne

### Erreur "Permission denied" sur Linux
- Exécutez : chmod -R 777 facturation/data/

### Les données ne sont pas sauvegardées
- Vérifiez les permissions d'écriture sur le dossier data/
- Vérifiez que PHP peut écrire dans des fichiers

### Erreur 404 sur les pages
- Vérifiez que mod_rewrite est activé (Apache)
- Vérifiez les chemins absolus dans les liens

## AUTEURS

Projet développé dans le cadre du TP de Programmation Web PHP
Faculté de Sciences Informatiques - L2 FASI
Université Protestante au Congo
Année académique 2025-2026

## LICENCE

Projet académique - Usage éducatif uniquement

================================================================================
Pour toute question ou problème technique, consultez la documentation PHP
ou contactez votre enseignant.
================================================================================
