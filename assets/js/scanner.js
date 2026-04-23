// Ce fichier JavaScript pilote le scanner de codes-barres côté navigateur.
// Il dépend de la librairie QuaggaJS chargée avant lui dans la page.

let scannerActif = false;

// Objet de configuration principal du scanner.
// Il décrit la source vidéo, les formats de codes acceptés et le comportement de détection.
const configQuagga = {
    inputStream: {
        // LiveStream = flux vidéo temps réel depuis la caméra du terminal.
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
        // Liste des symbologies de codes-barres que l'on autorise à la lecture.
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
    // locator aide Quagga à localiser la zone probable du code-barres dans l'image.
    locator: {
        patchSize: "medium",
        halfSample: true
    },
    // Nombre de workers en parallèle pour répartir le traitement.
    numOfWorkers: 4,
    frequency: 10,
    locate: true
};

// Fonction d'ouverture/initialisation du scanner.
function demarrerScanner() {
    // Guard clause : évite d'initialiser deux fois le scanner.
    if (scannerActif) return;

    Quagga.init(configQuagga, function(err) {
        if (err) {
            // console.error aide au debug côté développeur.
            console.error("Erreur d'initialisation du scanner:", err);
            alert("Impossible d'accéder à la caméra. Vérifiez les permissions.");
            return;
        }

        Quagga.start();
        scannerActif = true;

        document.getElementById('btn-demarrer-scanner').style.display = 'none';
        document.getElementById('btn-arreter-scanner').style.display = 'inline-block';
    });

    // Callback déclenché quand Quagga détecte un code-barres valide.
    Quagga.onDetected(function(result) {
        const code = result.codeResult.code;

        // Mise à jour immédiate de l'interface.
        document.getElementById('code-barre-detecte').textContent = code;
        document.getElementById('scanner-resultat').style.display = 'block';

        // On hydrate le formulaire pour que le backend puisse exploiter le code détecté.
        document.getElementById('input-code-barre').value = code;
        document.getElementById('affichage-code-barre').value = code;
        document.getElementById('btn-enregistrer').disabled = false;

        // On coupe le flux vidéo après détection pour éviter des scans multiples.
        arreterScanner();

        // Redirection vers la même page avec le code dans l'URL pour lancer le lookup PHP.
        window.location.href = '?code_barre=' + encodeURIComponent(code);
    });
}

    // Fonction d'arrêt propre du scanner.
function arreterScanner() {
    if (!scannerActif) return;

    Quagga.stop();
    scannerActif = false;

    document.getElementById('btn-demarrer-scanner').style.display = 'inline-block';
    document.getElementById('btn-arreter-scanner').style.display = 'none';
}

// Binding des événements UI sur les boutons de contrôle.
document.getElementById('btn-demarrer-scanner').addEventListener('click', demarrerScanner);
document.getElementById('btn-arreter-scanner').addEventListener('click', arreterScanner);

// Si la page a déjà été rechargée avec un code-barres, on réactive le bouton d'enregistrement.
if (document.getElementById('input-code-barre').value) {
    document.getElementById('btn-enregistrer').disabled = false;
}
