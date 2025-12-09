<?php
require_once 'helpers/TranslationHelper.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifie si un utilisateur est déja connecté
if (isset($_SESSION['user_id'])) {
    header("Location: " . _route('dashboard'));
    exit;
}

// Initialize variables
$email = '';
$username = '';
$first_name = '';
$last_name = '';
$phone = '';

// Vérifie si tous les champs du formulaire ont été saisis.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];

    // Validation du mot de passe - Force requise
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
        require_once 'config/db.php';
        require_once 'helpers/EmailHelper.php';

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $verificationToken = bin2hex(random_bytes(32));

        $stmt = $conn->prepare("INSERT INTO PATIENT (email, username, password_hash, first_name, last_name, phone_number, verification_token) 
                                VALUES (:email, :username, :password_hash, :first_name, :last_name, :phone_number, :verification_token)");

        try {
            $stmt->execute([
                'email' => $email,
                'username' => $username,
                'password_hash' => $hashedPassword,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'phone_number' => $phone,
                'verification_token' => $verificationToken
            ]);

            $emailHelper = new EmailHelper();
            $fullName = $first_name . ' ' . $last_name;
            $emailSent = $emailHelper->sendVerificationEmail($email, $fullName, $verificationToken);

            if ($emailSent) {
                $success = "Inscription réussie! Un email de vérification a été envoyé à $email. Veuillez vérifier votre boîte mail (MailHog: http://localhost:8025) pour activer votre compte.";
                $email = $username = $first_name = $last_name = $phone = '';
            } else {
                $success = "Inscription réussie! Cependant, l'email de vérification n'a pas pu être envoyé.";
                $email = $username = $first_name = $last_name = $phone = '';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23505) {
                $error = "Cet email ou nom d'utilisateur existe déjà.";
            } else {
                $error = "Erreur lors de l'inscription : " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo getLang(); ?>">

<head>
    <title><?php echo __('register_title'); ?> - Cabinet Médical</title>
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
        <div class="w3-card-4 w3-round-large w3-white" style="max-width:550px; width:90%;">
            <header class="w3-container w3-red w3-center" style="border-radius:4px 4px 0 0;">
                <h2 class="w3-margin"><?php echo __('register_title'); ?></h2>
            </header>
            <div class="w3-container w3-padding-large">
                <p class="w3-center"><?php echo __('register_already_account'); ?> <a
                        href="<?php echo _route('login'); ?>"
                        class="w3-text-blue"><b><?php echo __('register_login_link'); ?></b></a></p>

                <?php if (isset($success)): ?>
                    <div class="w3-panel w3-pale-green w3-border w3-round">
                        <p><?php echo htmlspecialchars($success); ?></p>
                        <p><a href="<?php echo _route('login'); ?>" class="w3-button w3-blue w3-round">Aller à la page de
                                connexion</a></p>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="w3-panel w3-pale-red w3-border w3-round">
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo _route('register'); ?>">
                    <div class="w3-row-padding" style="margin:0 -8px;">
                        <div class="w3-half">
                            <label class="w3-text-grey"><b><?php echo __('register_sf_firstname'); ?></b></label>
                            <input class="w3-input w3-border w3-round w3-margin-bottom" type="text" name="first_name"
                                required placeholder="Ex: Jean" value="<?php echo htmlspecialchars($first_name); ?>">
                        </div>
                        <div class="w3-half">
                            <label class="w3-text-grey"><b><?php echo __('register_sf_lastname'); ?></b></label>
                            <input class="w3-input w3-border w3-round w3-margin-bottom" type="text" name="last_name"
                                required placeholder="Ex: Dupont" value="<?php echo htmlspecialchars($last_name); ?>">
                        </div>
                    </div>

                    <label class="w3-text-grey"><b><?php echo __('register_sf_email'); ?></b></label>
                    <input class="w3-input w3-border w3-round w3-margin-bottom" type="email" name="email" required
                        placeholder="Ex: jean.dupont@email.com" value="<?php echo htmlspecialchars($email); ?>">

                    <label class="w3-text-grey"><b><?php echo __('register_sf_username'); ?></b></label>
                    <input class="w3-input w3-border w3-round w3-margin-bottom" type="text" name="username" required
                        placeholder="Ex: jdupont" value="<?php echo htmlspecialchars($username); ?>">

                    <div class="w3-row-padding" style="margin:0 -8px;">
                        <div class="w3-half">
                            <label class="w3-text-grey"><b><?php echo __('register_sf_password'); ?></b></label>
                            <input class="w3-input w3-border w3-round w3-margin-bottom" type="password" name="password"
                                required placeholder="Votre mot de passe">
                        </div>
                        <div class="w3-half">
                            <label class="w3-text-grey"><b>Confirmation</b></label>
                            <input class="w3-input w3-border w3-round w3-margin-bottom" type="password"
                                name="confirm_password" required placeholder="Confirmez">
                        </div>
                    </div>

                    <label class="w3-text-grey"><b><?php echo __('register_sf_phone'); ?></b></label>
                    <input class="w3-input w3-border w3-round w3-margin-bottom" type="text" name="phone"
                        placeholder="Ex: 0612345678" value="<?php echo htmlspecialchars($phone); ?>">

                    <button type="submit"
                        class="w3-button w3-red w3-block w3-round w3-padding-large w3-margin-top"><?php echo __('register_btn'); ?></button>
                </form>
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