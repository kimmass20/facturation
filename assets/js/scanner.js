// Gestion du scanner de codes-barres avec QuaggaJS

let scannerActif = false;

// Configuration de Quagga
const configQuagga = {
    inputStream: {
        name: "Live",
        type: "LiveStream",
        target: document.querySelector('#scanner-viewport'),
        constraints: {
            width: 640,
            height: 480,
            facingMode: "environment"
        }
    },
    decoder: {
        readers: [
            "ean_reader",
            "ean_8_reader",
            "code_128_reader",
            "code_39_reader",
            "upc_reader",
            "upc_e_reader"
        ],
        debug: {
            showCanvas: true,
            showPatches: false,
            showFoundPatches: false,
            showSkeleton: false,
            showLabels: false,
            showPatchLabels: false,
            showRemainingPatchLabels: false,
            boxFromPatches: {
                showTransformed: true,
                showTransformedBox: true,
                showBB: true
            }
        }
    },
    locator: {
        patchSize: "medium",
        halfSample: true
    },
    numOfWorkers: 4,
    frequency: 10,
    locate: true
};

// Démarre le scanner
function demarrerScanner() {
    if (scannerActif) return;

    Quagga.init(configQuagga, function(err) {
        if (err) {
            console.error("Erreur d'initialisation du scanner:", err);
            alert("Impossible d'accéder à la caméra. Vérifiez les permissions.");
            return;
        }

        Quagga.start();
        scannerActif = true;

        document.getElementById('btn-demarrer-scanner').style.display = 'none';
        document.getElementById('btn-arreter-scanner').style.display = 'inline-block';
    });

    // Écoute les détections
    Quagga.onDetected(function(result) {
        const code = result.codeResult.code;

        // Affiche le résultat
        document.getElementById('code-barre-detecte').textContent = code;
        document.getElementById('scanner-resultat').style.display = 'block';

        // Remplit le formulaire
        document.getElementById('input-code-barre').value = code;
        document.getElementById('affichage-code-barre').value = code;
        document.getElementById('btn-enregistrer').disabled = false;

        // Arrête le scanner
        arreterScanner();

        // Vérifie si le produit existe déjà
        window.location.href = '?code_barre=' + encodeURIComponent(code);
    });
}

// Arrête le scanner
function arreterScanner() {
    if (!scannerActif) return;

    Quagga.stop();
    scannerActif = false;

    document.getElementById('btn-demarrer-scanner').style.display = 'inline-block';
    document.getElementById('btn-arreter-scanner').style.display = 'none';
}

// Écouteurs d'événements
document.getElementById('btn-demarrer-scanner').addEventListener('click', demarrerScanner);
document.getElementById('btn-arreter-scanner').addEventListener('click', arreterScanner);

// Active le bouton d'enregistrement si un code-barres est déjà présent
if (document.getElementById('input-code-barre').value) {
    document.getElementById('btn-enregistrer').disabled = false;
}
