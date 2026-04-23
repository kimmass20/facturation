<?php
// On recharge les helpers d'authentification pour accéder aux paramètres système.
require_once __DIR__ . '/fonctions-auth.php';
$nom_entreprise = parametre_systeme('nom_entreprise');
?>
</div><!-- /.container -->

<!-- Footer global affiché sur toutes les pages authentifiées -->
<footer class="footer">
    <p><i class="fa-regular fa-copyright"></i> <?= date('Y') ?> <?= htmlspecialchars($nom_entreprise) ?> &mdash; Système de Facturation</p>
    <p>Développé dans le cadre du TP de Programmation Web PHP &mdash; L2 FASI</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Sélection des nœuds DOM nécessaires au burger menu.
    const hamburger = document.querySelector('.hamburger');
    const panel = document.querySelector('[data-navbar-panel]');
    const menu = panel ? panel.querySelector('.navbar-menu') : null;
    const backdrop = document.querySelector('[data-navbar-backdrop]');
    const userArea = panel ? panel.querySelector('.navbar-user') : null;

    // closeDuration synchronise le JavaScript avec la durée de la transition CSS.
    const closeDuration = 260;
    let closeTimer = null;

    // Si la structure n'existe pas sur la page, on ne fait rien.
    if (!hamburger || !menu || !panel) {
        return;
    }

    function nettoyerAnimationFermeture() {
        // Cette routine remet l'UI dans un état "neutre" après la fin d'une fermeture.
        panel.classList.remove('closing');
        menu.classList.remove('closing');
        if (userArea) {
            userArea.classList.remove('closing');
        }
        if (backdrop) {
            backdrop.classList.remove('closing');
        }
    }

    function ouvrirMenu() {
        // Si une fermeture était en cours, on l'annule proprement.
        if (closeTimer) {
            clearTimeout(closeTimer);
            closeTimer = null;
        }

        // On active les classes d'ouverture utilisées par le CSS pour animer le drawer.
        nettoyerAnimationFermeture();
        panel.classList.add('active');
        menu.classList.add('active');
        hamburger.classList.add('active');

        // Le body reçoit une classe pour empêcher le scroll de fond pendant l'ouverture.
        document.body.classList.add('menu-open');
        if (backdrop) {
            backdrop.classList.add('active');
        }
        hamburger.setAttribute('aria-expanded', 'true');
    }

    function fermerMenu() {
        // Guard clause : si le panneau n'est déjà plus ouvert, on sort immédiatement.
        if (!panel.classList.contains('active') && !panel.classList.contains('closing')) {
            return;
        }

        // Si un timer était déjà présent, on l'annule pour éviter une fermeture multiple.
        if (closeTimer) {
            clearTimeout(closeTimer);
        }

        // Ici on passe en mode "closing" pour laisser jouer l'animation de sortie.
        panel.classList.add('closing');
        menu.classList.add('closing');
        if (userArea) {
            userArea.classList.add('closing');
        }
        menu.classList.remove('active');
        hamburger.classList.remove('active');
        document.body.classList.remove('menu-open');
        if (backdrop) {
            backdrop.classList.add('closing');
            backdrop.classList.remove('active');
        }
        hamburger.setAttribute('aria-expanded', 'false');

        // Après la durée d'animation, on nettoie les classes résiduelles.
        closeTimer = window.setTimeout(function () {
            panel.classList.remove('active');
            nettoyerAnimationFermeture();
            closeTimer = null;
        }, closeDuration);
    }

    hamburger.addEventListener('click', function () {
        // Toggle comportemental : si le menu est ouvert, on ferme ; sinon on ouvre.
        if (panel.classList.contains('active') && !panel.classList.contains('closing')) {
            fermerMenu();
        } else {
            ouvrirMenu();
        }
    });

    if (backdrop) {
        // Cliquer sur le backdrop revient à fermer le drawer.
        backdrop.addEventListener('click', fermerMenu);
    }

    // Cliquer sur un lien ferme aussi le menu, ce qui améliore l'UX mobile.
    menu.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', fermerMenu);
    });

    window.addEventListener('resize', function () {
        // Si on repasse sur un viewport large, on referme le drawer mobile.
        if (window.innerWidth > 1280) {
            fermerMenu();
        }
    });

    document.addEventListener('keydown', function (event) {
        // UX standard : la touche Escape ferme le menu.
        if (event.key === 'Escape') {
            fermerMenu();
        }
    });
});
</script>

<!-- Permet à une page spécifique d'injecter son propre JavaScript -->
<?php if (isset($script_supplementaire)) echo $script_supplementaire; ?>
</body>
</html>
