<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
                                          // toegevogd met ai
requireLogin();
$user = currentUser();

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
        <div>
            <div class="page-eyebrow">Profiel pagina</div>
            <h1 class="page-title">Jouw profiel</h1>
            <p class="page-desc">Bekijk je accountgegevens en jouw persoonlijke statistieken.</p>
        </div>
    </div>


    <section class="profile-card">
        <div class="profile-card-inner">
            <h2>Persoonlijke gegevens</h2>
            <dl class="profile-details">
                <div class="profile-row">
                    <dt>Naam:</dt>
                    <dd><?= htmlspecialchars($user['name']) ?></dd>
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