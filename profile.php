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
        
        <?php if ($success): ?>
            <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
    </div>
    
    
    <section class="form-card">
    <div class="form-title">
        <div class="page-eyebrow">Persoonlijke gegevens</div>
        <h2 class="form-title">Naam wijzigen</h2>
        <p class="form-subtitle">Je vrienden zien deze naam in de poule.</p>
    </div>

    <form method="POST" action="profile.php" class="form-wrapper">
        <input type="hidden" name="action" value="update_name">

        <div class="form-group">
            <label class="form-label" for="name">Naam</label>
            <input
                id="name"
                class="form-input"
                type="text"
                name="name"
                value="<?= htmlspecialchars($user['name']) ?>"
                minlength="2"
                required>
            <p class="form-help">Minimaal 2 tekens.</p>
        </div>

        <div class="form-footer">
            <button type="submit" class="btn btn-primary">Opslaan</button>
        </div>
    </form>
</section>

<section class="form-card" style="margin-top: 24px;">
    <div class="form-title">
        <h2 class="form-title">Accountgegevens</h2>
    </div>

    <div class="form-wrapper">
        <div class="form-group">
            <label class="form-label">E-mailadres</label>
            <div class="form-input" style="background: none; border: none; box-shadow: none; padding: 0;">
                <?= htmlspecialchars($user['email']) ?>
            </div>
        </div>
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