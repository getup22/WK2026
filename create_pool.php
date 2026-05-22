<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();
$user = currentUser();

// =================================================================
// WAT MOET DIT BESTAND DOEN?
// =================================================================
// Een ingelogde gebruiker vult het formulier in met een poule-naam
// en (optioneel) een beschrijving. Als het formulier wordt verzonden:
//   1. Controleer of de ingevulde gegevens kloppen (validatie).
//   2. Maak een unieke 8-tekens toegangscode aan.
//   3. Sla de poule op in de database.
//   4. Voeg de aanmaker meteen toe als eerste lid van de poule.
//   5. Stuur de gebruiker door naar de detailpagina van de poule.
//
// WAAROM EEN TOEGANGSCODE?
// Andere gebruikers kunnen alleen meedoen als ze de code kennen.
// Zo blijven poules privé en kun je de code delen met bv. je klas.
//
// WAAROM MOET DE CODE UNIEK ZIJN?
// Op de kolom `access_code` staat een UNIQUE constraint in MySQL.
// Als je per ongeluk een bestaande code invoert, geeft MySQL een
// foutmelding en mislukt de INSERT. Met een random code van 8 tekens
// is de kans op botsing heel klein, maar bij duizenden poules niet nul.
// =================================================================


$errors      = [];
$name        = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');

    // =============================================================
    // TODO 1: VALIDATIE
    // =============================================================
    // Controleer of de ingevoerde gegevens kloppen:
    //  - $name mag niet leeg zijn en moet minimaal 3 tekens lang zijn.
    //  - $name mag maximaal 100 tekens lang zijn.
    //  - $description mag leeg zijn, maar maximaal 500 tekens.
    //
    // TIP: gebruik `mb_strlen()` in plaats van `strlen()` om goed te werken
    // met speciale tekens zoals é, ñ, emoji's, etc.
    //
    // Voeg bij elke fout een bericht toe aan de $errors array:
    //   $errors[] = 'Naam is verplicht.';
    //   $errors[] = 'Naam moet minimaal 3 tekens lang zijn.';
    //   $errors[] = 'Naam mag maximaal 100 tekens lang zijn.';
    //   $errors[] = 'Beschrijving mag maximaal 500 tekens lang zijn.';
    //
    // LET OP: als $name leeg is, hoef je niet nog een keer te controleren
    // of de naam te lang is. Gebruik hiervoor een `if / elseif` structuur.
    // =============================================================
    if (empty($name)) {
        $errors[] = 'Naam is verplicht.';
    } elseif (mb_strlen($name) >= 3) {
        $errors[] = 'Naam moet minimaal 3 tekens lang zijn.';
    } elseif (mb_strlen($name) <= 100) {
        $errors[] = 'Naam mag maximaal 100 tekens lang zijn.';
    } elseif ($description <= 500) {
        $errors[] = 'Beschrijving mag maximaal 500 tekens lang zijn.';
    }


    // =============================================================
    // TODO 2: UNIEKE TOEGANGSCODE GENEREREN
    // =============================================================
    // Alleen uitvoeren als $errors leeg is (validatie geslaagd).
    //
    // We willen een code van 8 tekens met alleen hoofdletters en cijfers.
    // Bijvoorbeeld: "A7F3B9C2", "4D8E1F60", "0123ABCD".
    //
    // UITLEG STAP VOOR STAP:
    //
    //  a) random_bytes(4) maakt 4 willekeurige bytes aan.
    //
    //  b) bin2hex(...) zet die 4 bytes om naar hexadecimale tekst
    //     (hex gebruikt tekens 0-9 en a-f). 4 bytes = 8 hex-tekens.
    //
    //  c) strtoupper(...) maakt er hoofdletters van: "a7f3b9c2" → "A7F3B9C2".
    //
    //  d) substr(..., 0, 8) pakt zekerheidshalve de eerste 8 tekens.
    //
    // Samen:
    //   $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    //
    // PROBLEEM: wat als die code toevallig al bestaat in de database?
    // Dan geeft de INSERT later een error. Oplossing: een do-while lus
    // die blijft nieuwe codes maken totdat er één vrij is.
    //
    // VOLLEDIGE OPLOSSING:
    //   do {
    //       $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    //       $stmt = $pdo->prepare("SELECT id FROM pools WHERE access_code = ?");
    //       $stmt->execute([$code]);
    //   } while ($stmt->fetch());
    //
    // De lus stopt zodra $stmt->fetch() "false" teruggeeft (= code niet
    // gevonden = deze code is nog vrij).
    // =============================================================
    do {
        $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $stmt = $pdo->prepare("SELECT id FROM pools WHERE access_code = ?");
        $stmt->execute([$code]);
    } while ($stmt->fetch());


    // =============================================================
    // TODO 3: POULE OPSLAAN & AANMAKER TOEVOEGEN
    // =============================================================
    // Alleen uitvoeren als $errors leeg is.
    //
    // Dit is een gevoelige stap: er moeten TWEE dingen in de database
    // gebeuren, en ze horen BIJ ELKAAR:
    //   1. Een nieuwe rij in de `pools` tabel.
    //   2. Een nieuwe rij in `pool_members` die de aanmaker koppelt.
    //
    // Als stap 1 lukt maar stap 2 faalt, heb je een "wees-poule" zonder
    // leden. Dat willen we niet! Oplossing: een database-transaction.
    //
    // WAT IS EEN TRANSACTION?
    // Een transaction zegt tegen de database: "doe deze groep queries
    // samen, of helemaal niet". Je opent met beginTransaction(), voert
    // je queries uit, en sluit af met commit() (= definitief maken).
    // Als er onderweg iets misgaat, roep je rollBack() aan en wordt
    // ALLES teruggedraaid alsof er niks gebeurd is.
    //
    // VOLLEDIGE OPZET MET TRANSACTION EN ERROR HANDLING:
    //
    //   try {
    //       $pdo->beginTransaction();
    //
    //       // Stap 1: poule aanmaken
    //       $stmt = $pdo->prepare(
    //           "INSERT INTO pools (name, description, access_code, created_by)
    //            VALUES (?, ?, ?, ?)"
    //       );
    //       $stmt->execute([$name, $description, $code, $user['id']]);
    //
    //       // Het ID van de zojuist aangemaakte poule ophalen
    //       $pool_id = (int)$pdo->lastInsertId();
    //
    //       // Stap 2: aanmaker als eerste lid toevoegen
    //       $stmt = $pdo->prepare(
    //           "INSERT INTO pool_members (pool_id, user_id) VALUES (?, ?)"
    //       );
    //       $stmt->execute([$pool_id, $user['id']]);
    //
    //       // Alles gelukt → definitief maken
    //       $pdo->commit();
    //
    //       // Doorsturen naar detailpagina
    //       header('Location: pool_detail.php?id=' . $pool_id);
    //       exit;
    //
    //   } catch (PDOException $e) {
    //       // Er ging iets mis → alles terugdraaien
    //       $pdo->rollBack();
    //       $errors[] = 'Er ging iets mis bij het aanmaken van de poule.';
    //   }
    //
    // BELANGRIJK: `exit;` na `header(...)` is essentieel! Anders blijft
    // de code na de redirect gewoon doorlopen en kan er gekke dingen gebeuren.
    // =============================================================
    try {
        $pdo->beginTransaction();

        // Stap 1: poule aanmaken
        $stmt = $pdo->prepare(
            "INSERT INTO pools (name, description, access_code, created_by)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$name, $description, $code, $user['id']]);

        // Het ID van de zojuist aangemaakte poule ophalen
        $pool_id = (int)$pdo->lastInsertId();

        // Stap 2: aanmaker als eerste lid toevoegen
        $stmt = $pdo->prepare(
            "INSERT INTO pool_members (pool_id, user_id) VALUES (?, ?)"
        );
        $stmt->execute([$pool_id, $user['id']]);

        // Alles gelukt → definitief maken
        $pdo->commit();

        // Doorsturen naar detailpagina
        header('Location: pool_detail.php?id=' . $pool_id);
        exit;
    } catch (PDOException $e) {
        // Er ging iets mis → alles terugdraaien
        $pdo->rollBack();
        $errors[] = 'Er ging iets mis bij het aanmaken van de poule.';
    };
}

$pageTitle = 'Nieuwe poule';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="form-wrapper">
        <div class="form-card">
            <h1 class="form-title">Nieuwe poule</h1>
            <p class="form-subtitle">Start je eigen competitie en nodig vrienden uit met een toegangscode.</p>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>

            <form method="POST" action="create_pool.php" novalidate>
                <div class="form-group">
                    <label class="form-label" for="name">Naam van de poule</label>
                    <input type="text" id="name" name="name" class="form-input"
                        value="<?= htmlspecialchars($name) ?>"
                        placeholder="Bijv. Klas 4A - WK 2026" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Beschrijving (optioneel)</label>
                    <textarea id="description" name="description" class="form-textarea"
                        placeholder="Waar gaat deze poule over?"><?= htmlspecialchars($description) ?></textarea>
                </div>

                <div class="flex gap-2">
                    <a href="pools.php" class="btn btn-ghost">Annuleren</a>
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Poule aanmaken</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>