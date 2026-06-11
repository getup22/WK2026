<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
                                          // toegevogd met ai
requireLogin();
$user = currentUser();

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'update_name')) {
    $newName = trim((string)($_POST['name'] ?? ''));

    if (strlen($newName) < 2) {
        $errors[] = 'De naam moet minimaal 2 tekens lang zijn.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE users SET name = ? WHERE id = ?');
            $stmt->execute([$newName, $user['id']]);

            $_SESSION['user_name'] = $newName;
            $user['name'] = $newName;
            $success = 'Je naam is succesvol bijgewerkt.';
        } catch (PDOException $e) {
            $errors[] = 'Kon naam niet opslaan. Probeer het later opnieuw.';
        }
    }
}

$stats = [
    'pools'       => 0,
    'predictions' => 0,
];

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM pool_members WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    $stats['pools'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM predictions WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    $stats['predictions'] = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // Fouten negeren en 0 tonen als de query mislukt
}

$pageTitle = 'Profiel';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <?php if ($success): ?>
             <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        
        <div>
            <div class="page-eyebrow">Profiel pagina
            </div>
            <h1 class="page-title">Jouw profiel</h1>
            <p class="page-desc">Bekijk je accountgegevens en jouw persoonlijke statistieken.</p>
        </div>
    </div>


    <section class="profile-card">
        <div class="profile-card-inner">
            <h2>Persoonlijke gegevens</h2>
            <dl class="profile-details">
                <div class="profile-row">
                    <form method="POST" action="profile.php" class="profile-form">
                        <input type="hidden" name="action" value="update_name">
                        
                        <label class="form-row">
                            <span>Naam</span>
                            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" minlength="2" required>
                        </label>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Opslaan</button>
                        </div>
                    </form>
                </div>
                <div class="profile-row">
                    <dt>E-mailadres:</dt>
                    <dd><?= htmlspecialchars($user['email']) ?></dd>
                </div>
            </dl>
        </div>
    </section>


    <div class="stat-row">
        <div class="stat stat-accent">
            <div class="stat-label">Aantal poules</div>
            <div class="stat-value"><?= $stats['pools'] ?></div>
        </div>
        <div class="stat">
            <div class="stat-label">Voorspellingen</div>
            <div class="stat-value"><?= $stats['predictions'] ?></div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>