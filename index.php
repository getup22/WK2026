<?php
require_once __DIR__ . '/includes/auth.php';
echo $_SESSION['user_id'] ?? 'Niet ingelogd';
require_once __DIR__ . '/includes/db.php';

$user = currentUser();

// Haal statistieken op voor ingelogde gebruikers
$stats = [
    'pools'       => 0,
    'predictions' => 0,
    'matches'     => 0,
];

if ($user) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pool_members WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $stats['pools'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM predictions WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $stats['predictions'] = (int)$stmt->fetchColumn();

        $stats['matches'] = (int)$pdo->query("SELECT COUNT(*) FROM matches")->fetchColumn();
    } catch (PDOException $e) {
        // Stil: stats blijven 0 als tabel nog leeg is
    }
}

$pageTitle = $user ? 'Dashboard' : 'Welkom';
include __DIR__ . '/includes/header.php';
?>

<div class="container">

<?php if (!$user): ?>

    <!-- Landing / Hero voor niet ingelogde bezoekers -->
    <section class="hero">
        <span class="hero-badge">⚽ WK 2026 · USA · Canada · Mexico</span>
        <h1 class="hero-title">
            Voorspel.<br>
            Strijd met <span class="field-word">vrienden</span>.<br>
            Word kampioen.
        </h1>
        <p class="hero-sub">
            Maak je eigen poule, nodig vrienden uit met een toegangscode en voorspel
            de uitslagen van alle WK-wedstrijden. Wie scoort de meeste punten?
        </p>
        <div class="hero-actions">
            <a href="register.php" class="btn btn-primary btn-lg">Start je poule</a>
            <a href="login.php"    class="btn btn-ghost btn-lg">Al een account? Inloggen</a>
        </div>
    </section>

    <section class="feature-grid">
        <article class="feature">
            <div class="feature-number">01 · REGISTREREN</div>
            <h3 class="feature-title">Maak een account</h3>
            <p class="feature-text">Gratis en snel. Alleen je naam, e-mail en een wachtwoord en je bent klaar om te starten.</p>
        </article>
        <article class="feature">
            <div class="feature-number">02 · POULE STARTEN</div>
            <h3 class="feature-title">Eigen competitie</h3>
            <p class="feature-text">Start een poule en deel de unieke code met vrienden, klasgenoten of collega's om samen te spelen.</p>
        </article>
        <article class="feature">
            <div class="feature-number">03 · VOORSPELLEN</div>
            <h3 class="feature-title">Scoor punten</h3>
            <p class="feature-text">Voorspel alle uitslagen, van groepsfase tot finale. Hoe dichter bij de score, hoe meer punten.</p>
        </article>
    </section>

<?php else: ?>

    <!-- Dashboard voor ingelogde gebruikers -->
    <div class="page-header">
        <div>
            <div class="page-eyebrow">Dashboard</div>
            <h1 class="page-title">Hey, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?> 👋</h1>
            <p class="page-desc">Klaar om het veld te betreden? Check je poules of update je voorspellingen.</p>
        </div>
        <a href="create_pool.php" class="btn btn-primary">+ Nieuwe Poule</a>
    </div>

    <div class="stat-row">
        <div class="stat stat-accent">
            <div class="stat-label">Mijn Poules</div>
            <div class="stat-value"><?= $stats['pools'] ?></div>
        </div>
        <div class="stat">
            <div class="stat-label">Voorspellingen</div>
            <div class="stat-value"><?= $stats['predictions'] ?></div>
        </div>
        <div class="stat">
            <div class="stat-label">Wedstrijden Totaal</div>
            <div class="stat-value"><?= $stats['matches'] ?></div>
        </div>
    </div>

    <div class="split">
        <a href="pools.php" class="pool-card">
            <div class="pool-card-header">
                <div>
                    <div class="feature-number">POULES</div>
                    <h3 class="pool-name">Bekijk poules</h3>
                </div>
            </div>
            <p class="pool-desc">Zie al je actieve poules en de deelnemers. Maak een nieuwe poule aan of sluit je aan met een code.</p>
            <div class="pool-meta">
                <span>Ga naar poules →</span>
            </div>
        </a>

        <a href="predictions.php" class="pool-card">
            <div class="pool-card-header">
                <div>
                    <div class="feature-number">VOORSPELLINGEN</div>
                    <h3 class="pool-name">Doe mee</h3>
                </div>
            </div>
            <p class="pool-desc">Geef jouw uitslag voor elke WK-wedstrijd. Wijzig je voorspelling tot vlak voor de aftrap.</p>
            <div class="pool-meta">
                <span>Voorspel wedstrijden →</span>
            </div>
        </a>
    </div>

<?php endif; ?>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
