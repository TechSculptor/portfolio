<?php
require_once 'helpers/TranslationHelper.php';
require_once 'config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . _route('dashboard'));
    exit;
}

$token = $_GET['token'] ?? '';
$validToken = false;
$patient = null;

// Verify token
if (!empty($token)) {
    $stmt = $conn->prepare("SELECT patient_id, first_name, last_name FROM PATIENT WHERE reset_token = :token AND reset_token_expiry > NOW()");
    $stmt->execute(['token' => $token]);
    $patient = $stmt->fetch();

    if ($patient) {
        $validToken = true;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate password strength
    $passwordErrors = [];
    if (strlen($password) < 12) {
        $passwordErrors[] = "au moins 12 caractères";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $passwordErrors[] = "une majuscule";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $passwordErrors[] = "une minuscule";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $passwordErrors[] = "un chiffre";
    }

    if (!empty($passwordErrors)) {
        $error = "Le mot de passe doit contenir : " . implode(", ", $passwordErrors) . ".";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Update password and clear reset token
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE PATIENT SET password_hash = :password, reset_token = NULL, reset_token_expiry = NULL WHERE patient_id = :id");
        $updateStmt->execute([
            'password' => $hashedPassword,
            'id' => $patient['patient_id']
        ]);

        $success = "Votre mot de passe a été réinitialisé avec succès !";
        $validToken = false; // Hide form after success
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo getLang(); ?>">

<head>
    <title>Réinitialiser le mot de passe - Cabinet Médical</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="/frontend/img/favicon.png">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: "Lato", sans-serif;
        }

        .w3-bar,
        h1,
        button {
            font-family: "Montserrat", sans-serif;
        }

        .full-height-flex {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
            padding-top: 80px;
            padding-bottom: 60px;
        }
    </style>
</head>

<body>

    <?php include '../frontend/partials/navbar.php'; ?>

    <div class="full-height-flex w3-light-grey">
        <div class="w3-card-4 w3-round-large w3-white" style="max-width:450px; width:90%;">
            <header class="w3-container w3-red w3-center" style="border-radius:4px 4px 0 0;">
                <h2 class="w3-margin"><i class="fa fa-key"></i> Nouveau mot de passe</h2>
            </header>
            <div class="w3-container w3-padding-large">

                <?php if (isset($success)): ?>
                    <div class="w3-panel w3-pale-green w3-border w3-round">
                        <p><i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></p>
                    </div>
                    <p class="w3-center">
                        <a href="<?php echo _route('login'); ?>" class="w3-button w3-blue w3-round">Se connecter</a>
                    </p>
                <?php elseif (!$validToken): ?>
                    <div class="w3-panel w3-pale-red w3-border w3-round">
                        <p><i class="fa fa-exclamation-triangle"></i> Ce lien est invalide ou a expiré.</p>
                    </div>
                    <p class="w3-center">
                        <a href="<?php echo _route('forgot_password'); ?>" class="w3-button w3-blue w3-round">Demander un
                            nouveau lien</a>
                    </p>
                <?php else: ?>
                    <p class="w3-center w3-text-grey">Bonjour <?php echo htmlspecialchars($patient['first_name']); ?>,
                        entrez votre nouveau mot de passe.</p>

                    <?php if (isset($error)): ?>
                        <div class="w3-panel w3-pale-red w3-border w3-round">
                            <p><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo _route('reset_password'); ?>">
                        <label class="w3-text-grey"><b>Nouveau mot de passe</b></label>
                        <input class="w3-input w3-border w3-round w3-margin-bottom" type="password" name="password" required
                            placeholder="Min. 12 caractères, 1 majuscule, 1 chiffre">

                        <label class="w3-text-grey"><b>Confirmer le mot de passe</b></label>
                        <input class="w3-input w3-border w3-round w3-margin-bottom" type="password" name="confirm_password"
                            required placeholder="Confirmez votre mot de passe">

                        <button type="submit" class="w3-button w3-red w3-block w3-round w3-padding-large w3-margin-top">
                            <i class="fa fa-save"></i> Réinitialiser
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../frontend/partials/footer.php'; ?>

    <script>
        function myFunction() {
            var x = document.getElementById("navDemo");
            if (x.className.indexOf("w3-show") == -1) {
                x.className += " w3-show";
            } else {
                x.className = x.className.replace(" w3-show", "");
            }
        }
    </script>

</body>

</html>