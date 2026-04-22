<?php
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../includes/fonctions-auth.php';

requiert_role(ROLE_SUPER_ADMIN);

$titre_page = 'Gestion des comptes';

$utilisateurs = charger_utilisateurs();

// Tri par rôle puis nom
usort($utilisateurs, function($a, $b) {
    if ($a['role'] !== $b['role']) {
        $ordre = [ROLE_SUPER_ADMIN => 3, ROLE_MANAGER => 2, ROLE_CAISSIER => 1];
        return $ordre[$b['role']] - $ordre[$a['role']];
    }
    return strcmp($a['nom_complet'], $b['nom_complet']);
});

include __DIR__ . '/../../includes/header.php';
?>

<h2>👥 Gestion des comptes utilisateurs</h2>

<div class="mb-2">
    <a href="/facturation/modules/admin/ajouter-compte.php" class="btn-primaire">+ Ajouter un compte</a>
</div>

<div class="tableau-conteneur">
    <table>
        <thead>
            <tr>
                <th>Identifiant</th>
                <th>Nom complet</th>
                <th>Rôle</th>
                <th>Date de création</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($utilisateurs as $utilisateur): ?>
            <tr>
                <td><code><?= htmlspecialchars($utilisateur['identifiant']) ?></code></td>
                <td><strong><?= htmlspecialchars($utilisateur['nom_complet']) ?></strong></td>
                <td>
                    <span class="badge
                        <?= $utilisateur['role'] === ROLE_SUPER_ADMIN ? 'statut-paye' :
                            ($utilisateur['role'] === ROLE_MANAGER ? 'statut-attente' : 'statut-retard') ?>">
                        <?= libelle_role($utilisateur['role']) ?>
                    </span>
                </td>
                <td><?= date('d/m/Y', strtotime($utilisateur['date_creation'])) ?></td>
                <td>
                    <?php if ($utilisateur['actif']): ?>
                        <span style="color: #10b981;">● Actif</span>
                    <?php else: ?>
                        <span style="color: #ef4444;">● Inactif</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($utilisateur['identifiant'] !== utilisateur_connecte() && $utilisateur['role'] !== ROLE_SUPER_ADMIN): ?>
                        <a href="/facturation/modules/admin/supprimer-compte.php?id=<?= urlencode($utilisateur['identifiant']) ?>"
                           class="btn-danger"
                           onclick="return confirm('Supprimer le compte de <?= htmlspecialchars($utilisateur['nom_complet']) ?> ?')">
                            🗑️ Supprimer
                        </a>
                    <?php else: ?>
                        <span style="color: #9ca3af;">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="message-info">
    <strong>ℹ️ Informations :</strong>
    <ul style="margin: 0.5rem 0 0 1.5rem;">
        <li>Vous ne pouvez pas supprimer votre propre compte</li>
        <li>Vous ne pouvez pas supprimer les comptes Super Administrateur</li>
        <li>Seuls les comptes Caissier et Manager peuvent être supprimés</li>
    </ul>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
