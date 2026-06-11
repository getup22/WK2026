<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

requireLogin();
$user = currentUser();

// =================================================================
// WAT MOET DIT BESTAND DOEN?
// =================================================================
// Een ingelogde gebruiker heeft een toegangscode gekregen van iemand
// anders en wil meedoen aan die poule. Het proces:
//   1. Controleer of de code niet leeg is (validatie).
//   2. Zoek de bijbehorende poule in de database.
//   3. Check of de gebruiker al lid is (zo ja: niet dubbel toevoegen).
//   4. Voeg de gebruiker toe aan de poule.
//   5. Stuur door naar de detailpagina van de poule.
//
// WAAROM EERST CHECKEN OF IEMAND AL LID IS?
// Op de `pool_members` tabel staat een UNIQUE KEY op (pool_id, user_id).
// Als je iemand probeert toe te voegen die al lid is, crasht de INSERT.
// We willen netjes afhandelen door simpelweg door te sturen naar de
// poule-pagina — de gebruiker ziet gewoon de poule die hij wilde
// bekijken, zonder foutmelding.
// =================================================================


$errors  = [];
$code    = '';

$pool = null;         //  ← declareer $pool alvast hier (later kan gebruiken in TODO 3 en 4) [opdracht van: TODO 2]


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // De code uit het formulier: trim (spaties weg) + uppercase voor
    // hoofdletter-ongevoelige matching. Dus "abc12345" wordt "ABC12345".
    $code = strtoupper(trim($_POST['access_code'] ?? ''));

    // =============================================================
    // TODO 1: VALIDATIE
    // =============================================================
    // Controleer dat de toegangscode niet leeg is.
    //
    //   if ($code === '') {
    //       $errors[] = 'Vul een toegangscode in.';
    //   }
    //
    // EXTRA (optioneel, goed voor gevorderden):
    // Je kunt ook checken dat de code precies 8 tekens lang is, want
    // zo wordt hij gegenereerd in create_pool.php. Maar omdat we de
    // code toch gaan opzoeken in de database, is dit niet strict nodig.
    // =============================================================
    if ($code === '') {
        $errors[] = 'Vul een toegangscode in.';
    } elseif (mb_strlen($code) !== 8) {       //  ← deze code mag hoeft niet  
        $errors[] = 'Toegangscode moet 8 tekens lang zijn.';
    }


    // =============================================================
    // TODO 2: POULE ZOEKEN OP BASIS VAN CODE
    // =============================================================
    // Voer deze stap alleen uit als $errors leeg is.
    //
    // Zoek in de `pools` tabel naar een rij met deze access_code.
    // Gebruik een prepared statement:
    //
    //   $stmt = $pdo->prepare("SELECT * FROM pools WHERE access_code = ?");
    //   $stmt->execute([$code]);
    //   $pool = $stmt->fetch();
    //
    // Als $pool "false" is (niet gevonden), voeg dan een foutmelding toe:
    //
    //   if (!$pool) {
    //       $errors[] = 'Deze toegangscode is onbekend.';
    //   }
    //
    // BELANGRIJK: declareer $pool = null; bovenaan het if-blok, zodat
    // je hem in TODO 3 en 4 kunt gebruiken. Dus vóór TODO 1:
    //
    //   $pool = null;  // zie bovenkant van dit blok
    //
    // Of zet deze zelf alvast neer.
    // =============================================================    
    $stmt = $pdo->prepare("SELECT * FROM pools WHERE access_code = ?");
    $stmt->execute([$code]);
    $pool = $stmt->fetch();

    if (!$pool) {   // zoek poule via toegangscode
        $errors[] = 'Deze toegangscode is onbekend.';
    }


    // =============================================================
    // TODO 3: CONTROLEER OF GEBRUIKER AL LID IS
    // =============================================================
    // Alleen uitvoeren als $errors leeg is EN $pool gevonden is.
    //
    // Zoek in de `pool_members` tabel of er al een rij is met deze
    // pool_id en user_id:
    //
    //   $stmt = $pdo->prepare(
    //       "SELECT id FROM pool_members WHERE pool_id = ? AND user_id = ?"
    //   );
    //   $stmt->execute([$pool['id'], $user['id']]);
    //
    //   if ($stmt->fetch()) {
    //       // Gebruiker is al lid → stuur direct door naar de detailpagina.
    //       // We geven GEEN foutmelding; we willen dat het "gewoon werkt".
    //       header('Location: pool_detail.php?id=' . $pool['id']);
    //       exit;
    //   }
    //
    // WAAROM GEEN FOUTMELDING?
    // Stel je voor: een student krijgt de code, klikt, wordt toegevoegd
    // en ziet de poule. Een week later klikt hij per ongeluk nog een
    // keer op dezelfde link. In plaats van "je bent al lid!" te zeggen,
    // is het vriendelijker om hem gewoon naar de poule te sturen.
    //
    // Dit patroon heet "idempotent": dezelfde actie meerdere keren
    // uitvoeren heeft hetzelfde eindresultaat.
    // =============================================================
    if (empty($errors) && $pool) {    
    // Als er fouten zijn, stop dan hier
        $stmt = $pdo->prepare(
            "SELECT id FROM pool_members WHERE pool_id = ? AND user_id = ?"
        );
        $stmt->execute([$pool['id'], $user['id']]);

        if ($stmt->fetch()) {
            // Gebruiker is al lid → stuur direct door naar de detailpagina.
            // We geven GEEN foutmelding; we willen dat het "gewoon werkt".
            header('Location: pool_detail.php?id=' . $pool['id']);
            exit;
        }
    }


    // =============================================================
    // TODO 4: GEBRUIKER TOEVOEGEN AAN POULE
    // =============================================================
    // Alleen uitvoeren als $errors leeg is, $pool gevonden is, en
    // de gebruiker nog geen lid is (TODO 3 heeft dan niet geredirect).
    //
    // Voeg een rij toe aan `pool_members`:
    //
    //   $stmt = $pdo->prepare(
    //       "INSERT INTO pool_members (pool_id, user_id) VALUES (?, ?)"
    //   );
    //   $stmt->execute([$pool['id'], $user['id']]);
    //
    //   header('Location: pool_detail.php?id=' . $pool['id']);
    //   exit;
    //
    // TIP: je kunt de 4 TODO's het beste aan elkaar koppelen met
    // "als er geen fouten zijn" controles, bijvoorbeeld:
    //
    //   if (empty($errors)) {
    //       // ... zoek poule ...
    //   }
    //   if (empty($errors) && $pool) {
    //       // ... check of al lid ...
    //   }
    //   if (empty($errors) && $pool) {
    //       // ... toevoegen + redirect ...
    //   }
    // =============================================================
      if (empty($errors) && $pool) {
          // ... toevoegen + redirect ...
          $stmt = $pdo->prepare(
              "INSERT INTO pool_members (pool_id, user_id) VALUES (?, ?)"
          );
          $stmt->execute([$pool['id'], $user['id']]);
      
          header('Location: pool_detail.php?id=' . $pool['id']);
          exit;
      }
}

$pageTitle = 'Poule joinen';
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="form-wrapper">
        <div class="form-card">
            <h1 class="form-title">Join een poule</h1>
            <p class="form-subtitle">Heb je een toegangscode gekregen? Vul hem hier in om deel te nemen.</p>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>

            <form method="POST" action="join_pool.php" novalidate>
                <div class="form-group">
                    <label class="form-label" for="access_code">Toegangscode</label>
                    <input type="text" id="access_code" name="access_code" class="form-input"
                        value="<?= htmlspecialchars($code) ?>"
                        placeholder="Bijv. A1B2C3D4"
                        style="font-family: var(--font-mono); letter-spacing: 0.15em; text-transform: uppercase;"
                        maxlength="20" required>
                    <p class="form-help">De 8-cijferige code die je van de poule-beheerder hebt gekregen.</p>
                </div>

                <div class="flex gap-2">
                    <a href="pools.php" class="btn btn-ghost">Annuleren</a>
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Join poule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>