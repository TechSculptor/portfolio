<?php
require_once 'helpers/TranslationHelper.php';
require_once 'config/db.php';
require_once 'helpers/EmailHelper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . _route('dashboard'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Check if email exists in PATIENT table
    $stmt = $conn->prepare("SELECT patient_id, first_name, last_name FROM PATIENT WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $patient = $stmt->fetch();

    if ($patient) {
        // Generate reset token
        $resetToken = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store token in database
        $updateStmt = $conn->prepare("UPDATE PATIENT SET reset_token = :token, reset_token_expiry = :expiry WHERE patient_id = :id");
        $updateStmt->execute([
            'token' => $resetToken,
            'expiry' => $expiry,
            'id' => $patient['patient_id']
        ]);

        // Send reset email
        $emailHelper = new EmailHelper();
        $fullName = $patient['first_name'] . ' ' . $patient['last_name'];
        $emailHelper->sendPasswordResetEmail($email, $fullName, $resetToken);

        $success = "Si cette adresse email existe dans notre système, un email de réinitialisation a été envoyé.";
    } else {
        // Same message for security (don't reveal if email exists)
        $success = "Si cette adresse email existe dans notre système, un email de réinitialisation a été envoyé.";
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo getLang(); ?>">

<head>
    <title>Mot de passe oublié - Cabinet Médical</title>
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
                <h2 class="w3-margin"><i class="fa fa-unlock-alt"></i> Mot de passe oublié</h2>
            </header>
            <div class="w3-container w3-padding-large">
                <p class="w3-center w3-text-grey">Entrez votre adresse email pour recevoir un lien de réinitialisation.
                </p>

                <?php if (isset($success)): ?>
                    <div class="w3-panel w3-pale-green w3-border w3-round">
                        <p><?php echo htmlspecialchars($success); ?></p>
                        <p class="w3-small w3-text-grey">Consultez votre boîte mail (MailHog: <a
                                href="http://localhost:8025" target="_blank">localhost:8025</a>)</p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo _route('forgot_password'); ?>">
                    <label class="w3-text-grey"><b>Adresse Email</b></label>
                    <input class="w3-input w3-border w3-round w3-margin-bottom" type="email" name="email" required
                        placeholder="votre.email@exemple.com" autocomplete="off">

                    <button type="submit" class="w3-button w3-red w3-block w3-round w3-padding-large w3-margin-top">
                        <i class="fa fa-paper-plane"></i> Envoyer le lien
                    </button>
                </form>

                <p class="w3-center w3-margin-top">
                    <a href="<?php echo _route('login'); ?>" class="w3-text-blue"><i class="fa fa-arrow-left"></i>
                        Retour à la connexion</a>
                </p>
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