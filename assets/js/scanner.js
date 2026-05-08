// Ce fichier JavaScript pilote le scanner de codes-barres côté navigateur.
// Il est partagé par l'écran d'enregistrement produit et celui de nouvelle facture.
// ZXing est utilisé ici car il donne de meilleurs résultats avec les webcams de PC.

let scannerActif = false;
let detectionEnCours = false;
let lecteurCodeBarres = null;
let dernierCodeDetecte = '';
let compteurDetectionStable = 0;
let derniereDetectionTs = 0;

const DETECTIONS_STABLES_REQUISES = 2;
const FENETRE_DETECTION_MS = 1200;
const LONGUEUR_MIN_CODE = 6;

const viewportScanner = document.getElementById('scanner-viewport');
const boutonDemarrerScanner = document.getElementById('btn-demarrer-scanner');
const boutonArreterScanner = document.getElementById('btn-arreter-scanner');
const champCodeBarre = document.getElementById('affichage-code-barre');
const champCodeBarreTechnique = document.getElementById('input-code-barre');
const boutonActionPrincipal = document.getElementById('btn-enregistrer');
const resultatScanner = document.getElementById('scanner-resultat');
const texteCodeDetecte = document.getElementById('code-barre-detecte');
const messageScanner = document.getElementById('scanner-message');

function afficherMessageScanner(message, type) {
    if (!messageScanner) {
        return;
    }

    messageScanner.textContent = message;
    messageScanner.className = type === 'erreur' ? 'message-erreur' : 'message-info';
    messageScanner.style.display = 'block';
}

function effacerMessageScanner() {
    if (!messageScanner) {
        return;
    }

    messageScanner.textContent = '';
    messageScanner.style.display = 'none';
}

function obtenirMessageErreurCamera(err) {
    const nomErreur = err && err.name ? err.name : '';

    if (!window.isSecureContext || !(navigator.mediaDevices && navigator.mediaDevices.getUserMedia)) {
        return "La caméra n'est disponible que sur HTTPS ou sur http://localhost. Ouvrez l'application depuis ce contexte pour scanner.";
    }

    if (nomErreur === 'NotAllowedError' || nomErreur === 'PermissionDeniedError') {
        return "L'accès à la caméra est refusé pour ce site. Autorisez la caméra dans le navigateur puis réessayez.";
    }

    if (nomErreur === 'NotFoundError' || nomErreur === 'DevicesNotFoundError') {
        return "Aucune caméra n'a été détectée sur cet appareil.";
    }

    if (nomErreur === 'NotReadableError' || nomErreur === 'TrackStartError') {
        return "La caméra est déjà utilisée par une autre application. Fermez-la puis relancez le scanner.";
    }

    if (nomErreur === 'OverconstrainedError') {
        return "Les réglages demandés ne correspondent à aucune caméra disponible. Relancez le scanner avec la webcam du PC.";
    }

    return "Impossible d'accéder à la caméra pour le moment. Vérifiez les permissions puis réessayez.";
}

function obtenirContraintesVideo(prefererCameraArriere) {
    return {
        video: {
            width: { ideal: 1280 },
            height: { ideal: 720 },
            facingMode: prefererCameraArriere ? { ideal: 'environment' } : undefined
        },
        audio: false
    };
}

function obtenirFormatsPrisEnCharge() {
    return [
        ZXing.BarcodeFormat.EAN_13,
        ZXing.BarcodeFormat.EAN_8,
        ZXing.BarcodeFormat.UPC_A,
        ZXing.BarcodeFormat.UPC_E,
        ZXing.BarcodeFormat.CODE_128,
        ZXing.BarcodeFormat.CODE_39
    ];
}

function creerLecteurCodeBarres() {
    const hints = new Map();
    hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, obtenirFormatsPrisEnCharge());
    hints.set(ZXing.DecodeHintType.TRY_HARDER, true);

    return new ZXing.BrowserMultiFormatReader(hints, 250);
}

function nettoyerLecteurCodeBarres() {
    if (!lecteurCodeBarres) {
        return;
    }

    try {
        lecteurCodeBarres.reset();
    } catch (err) {
        console.warn('Impossible de réinitialiser le lecteur ZXing :', err);
    }

    lecteurCodeBarres = null;
}

function estErreurLectureContinue(err) {
    return err instanceof ZXing.NotFoundException
        || err instanceof ZXing.ChecksumException
        || err instanceof ZXing.FormatException;
}

function normaliserCodeBarre(code) {
    return String(code || '').replace(/\s+/g, '').trim();
}

function reinitialiserStabilisationDetection() {
    dernierCodeDetecte = '';
    compteurDetectionStable = 0;
    derniereDetectionTs = 0;
}

function mettreAJourInterfaceAvecCode(code) {
    if (texteCodeDetecte) {
        texteCodeDetecte.textContent = code;
    }

    if (resultatScanner) {
        resultatScanner.style.display = 'block';
    }

    if (champCodeBarreTechnique) {
        champCodeBarreTechnique.value = code;
    }

    if (champCodeBarre) {
        champCodeBarre.value = code;
    }

    if (boutonActionPrincipal) {
        boutonActionPrincipal.disabled = false;
    }
}

function redirigerAvecCodeBarre(code) {
    const url = new URL(window.location.href);
    url.searchParams.set('code_barre', code);
    window.location.href = url.toString();
}

function extraireTexteResultat(resultat) {
    if (!resultat) {
        return '';
    }

    if (typeof resultat.getText === 'function') {
        return resultat.getText();
    }

    return resultat.text || '';
}

function extraireFormatResultat(resultat) {
    if (!resultat) {
        return '';
    }

    if (typeof resultat.getBarcodeFormat === 'function') {
        return resultat.getBarcodeFormat();
    }

    return resultat.barcodeFormat || '';
}

function obtenirSeuilValidation(formatCodeBarres) {
    const formatsLectureImmediatte = [
        ZXing.BarcodeFormat.EAN_13,
        ZXing.BarcodeFormat.EAN_8,
        ZXing.BarcodeFormat.UPC_A,
        ZXing.BarcodeFormat.UPC_E
    ];

    if (formatsLectureImmediatte.includes(formatCodeBarres)) {
        return 1;
    }

    return DETECTIONS_STABLES_REQUISES;
}

function traiterCodeBarre(code) {
    const codeNettoye = normaliserCodeBarre(code);

    if (!codeNettoye || codeNettoye.length < LONGUEUR_MIN_CODE || detectionEnCours) {
        return;
    }

    detectionEnCours = true;
    mettreAJourInterfaceAvecCode(codeNettoye);

    arreterScanner();
    redirigerAvecCodeBarre(codeNettoye);
}

function enregistrerDetectionStable(code, formatCodeBarres) {
    const maintenant = Date.now();
    const seuilValidation = obtenirSeuilValidation(formatCodeBarres);

    if (code === dernierCodeDetecte && maintenant - derniereDetectionTs <= FENETRE_DETECTION_MS) {
        compteurDetectionStable += 1;
    } else {
        dernierCodeDetecte = code;
        compteurDetectionStable = 1;
    }

    derniereDetectionTs = maintenant;

    if (compteurDetectionStable >= seuilValidation) {
        traiterCodeBarre(code);
    }
}

function gererDetection(resultat) {
    if (detectionEnCours || !resultat) {
        return;
    }

    const texte = normaliserCodeBarre(extraireTexteResultat(resultat));
    const formatCodeBarres = extraireFormatResultat(resultat);

    if (!texte) {
        return;
    }

    enregistrerDetectionStable(texte, formatCodeBarres);
}

async function initialiserScannerAvecZXing(prefererCameraArriere) {
    nettoyerLecteurCodeBarres();
    lecteurCodeBarres = creerLecteurCodeBarres();

    await lecteurCodeBarres.decodeFromConstraints(
        obtenirContraintesVideo(prefererCameraArriere),
        viewportScanner,
        function(resultat, err) {
            if (resultat) {
                gererDetection(resultat);
                return;
            }

            if (err && !estErreurLectureContinue(err)) {
                console.error('Erreur ZXing pendant le scan :', err);
                afficherMessageScanner(obtenirMessageErreurCamera(err), 'erreur');
                arreterScanner();
            }
        }
    );
}

async function tenterDemarrageAutomatique() {
    if (!window.isSecureContext || !(navigator.mediaDevices && navigator.mediaDevices.getUserMedia)) {
        afficherMessageScanner(obtenirMessageErreurCamera(), 'erreur');
        if (champCodeBarre) {
            champCodeBarre.focus();
        }
        return;
    }

    if (navigator.permissions && navigator.permissions.query) {
        try {
            const statut = await navigator.permissions.query({ name: 'camera' });

            if (statut.state === 'denied') {
                afficherMessageScanner(obtenirMessageErreurCamera({ name: 'NotAllowedError' }), 'erreur');
                if (champCodeBarre) {
                    champCodeBarre.focus();
                }
                return;
            }
        } catch (err) {
            console.warn('Impossible de lire la permission caméra :', err);
        }
    }

    demarrerScanner();
}

async function demarrerScanner() {
    if (scannerActif) {
        return;
    }

    detectionEnCours = false;
    reinitialiserStabilisationDetection();
    effacerMessageScanner();

    if (!window.isSecureContext || !(navigator.mediaDevices && navigator.mediaDevices.getUserMedia)) {
        afficherMessageScanner(obtenirMessageErreurCamera(), 'erreur');
        if (champCodeBarre) {
            champCodeBarre.focus();
        }
        return;
    }

    try {
        try {
            await initialiserScannerAvecZXing(true);
        } catch (err) {
            const nomErreur = err && err.name ? err.name : '';

            if (nomErreur === 'NotAllowedError' || nomErreur === 'PermissionDeniedError') {
                throw err;
            }

            await initialiserScannerAvecZXing(false);
        }

        scannerActif = true;
        afficherMessageScanner("Caméra active. Présentez le code-barres bien à plat devant l'objectif de la webcam.", 'info');
        boutonDemarrerScanner.style.display = 'none';
        boutonArreterScanner.style.display = 'inline-block';
    } catch (err) {
        nettoyerLecteurCodeBarres();
        console.error("Erreur d'initialisation du scanner:", err);
        afficherMessageScanner(obtenirMessageErreurCamera(err), 'erreur');
        if (champCodeBarre) {
            champCodeBarre.focus();
        }
    }
}

function arreterScanner() {
    if (!scannerActif && !lecteurCodeBarres) {
        return;
    }

    nettoyerLecteurCodeBarres();
    scannerActif = false;
    detectionEnCours = false;
    reinitialiserStabilisationDetection();

    boutonDemarrerScanner.style.display = 'inline-block';
    boutonArreterScanner.style.display = 'none';
}

if (viewportScanner && boutonDemarrerScanner && boutonArreterScanner && champCodeBarre && champCodeBarreTechnique) {
    boutonDemarrerScanner.addEventListener('click', demarrerScanner);
    boutonArreterScanner.addEventListener('click', arreterScanner);

    champCodeBarre.addEventListener('input', function() {
        const codeSaisi = normaliserCodeBarre(champCodeBarre.value);
        champCodeBarreTechnique.value = codeSaisi;
        if (boutonActionPrincipal) {
            boutonActionPrincipal.disabled = codeSaisi === '';
        }
    });

    champCodeBarre.addEventListener('keydown', function(event) {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        traiterCodeBarre(champCodeBarre.value);
    });

    window.addEventListener('pagehide', arreterScanner);

    if (champCodeBarreTechnique.value) {
        // Un code-barres est déjà chargé (retour de scan via ?code_barre=).
        // Ne pas relancer le scanner automatiquement : cela provoquerait une re-détection
        // et une redirection pendant que l'utilisateur remplit ou valide le formulaire.
        mettreAJourInterfaceAvecCode(champCodeBarreTechnique.value);
    } else {
        if (boutonActionPrincipal) {
            boutonActionPrincipal.disabled = true;
        }
        tenterDemarrageAutomatique();
    }
}
