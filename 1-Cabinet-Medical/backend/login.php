<?php
require_once 'helpers/TranslationHelper.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header("Location: " . _route('dashboard'));
    exit;
}

// Vérifier si le formulaire de connexion a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les valeurs du formulaire
    $inputUsername = trim($_POST['username']);
    $inputPassword = $_POST['password'];

    // Connexion à la base de donnée
    require_once 'config/db.php';

    $loginSuccessful = false;
    $error = "Nom d'utilisateur ou mot de passe incorrect";

    // 1. Check PATIENT
    $stmt = $conn->prepare("SELECT patient_id, email, username, password_hash, first_name, last_name, email_verified FROM PATIENT WHERE username = :u OR email = :u");
    $stmt->execute(['u' => $inputUsername]);
    $users = $stmt->fetchAll();

    foreach ($users as $row) {
        if (password_verify($inputPassword, $row['password_hash'])) {
            if (!$row['email_verified']) {
                $error = "Veuillez vérifier votre adresse email avant de vous connecter.";
                // Continue searching? No, if we found a match but it's unverified, that's likely the intended account. 
                // But to be fully robust against duplicates, maybe we continue? 
                // Let's stop if we find a password match but unverified. It's unsafe to let them login to a *different* account if they found their target but it's locked.
                // However, user wants to avoid "Incorrect password". This is "Unverified".
                break;
            } else {
                $_SESSION['user_id'] = $row['patient_id'];
                $_SESSION['user_type'] = 'patient';
                $_SESSION['username'] = $row['first_name'] . ' ' . $row['last_name'];
                $loginSuccessful = true;
                break; // Stop checking patients
            }
        }
    }

    // 2. Check DOCTOR (Only if not already logged in)
    if (!$loginSuccessful && $error === "Nom d'utilisateur ou mot de passe incorrect") {
        $stmt = $conn->prepare("SELECT doctor_id, email, username, password_hash, first_name, last_name FROM DOCTOR WHERE username = :u OR email = :u");
        $stmt->execute(['u' => $inputUsername]);
        $users = $stmt->fetchAll();

        foreach ($users as $row) {
            if (password_verify($inputPassword, $row['password_hash'])) {
                $_SESSION['user_id'] = $row['doctor_id'];
                $_SESSION['user_type'] = 'doctor';
                $_SESSION['username'] = 'Dr. ' . $row['first_name'] . ' ' . $row['last_name'];
                $loginSuccessful = true;
                break;
            }
        }
    }

    // 3. Check ADMIN (Only if not already logged in)
    if (!$loginSuccessful && $error === "Nom d'utilisateur ou mot de passe incorrect") {
        $stmt = $conn->prepare("SELECT admin_id, email, username, password_hash FROM ADMIN WHERE username = :u OR email = :u");
        $stmt->execute(['u' => $inputUsername]);
        $users = $stmt->fetchAll();

        foreach ($users as $row) {
            if (password_verify($inputPassword, $row['password_hash'])) {
                $_SESSION['user_id'] = $row['admin_id'];
                $_SESSION['user_type'] = 'admin';
                $_SESSION['username'] = $row['username'];
                $loginSuccessful = true;
                break;
            }
        }
    }

    if ($loginSuccessful) {
        header("Location: " . _route('dashboard'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo getLang(); ?>">

<head>
    <title><?php echo __('login_title'); ?> - Cabinet Médical</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="/frontend/img/favicon.png">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css">
    <link rel="stylesheet" href="/frontend/css/styles.css">
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
    <div style="display: flex; flex-direction: column; min-height: 100vh;">

        <!-- Navbar -->
        <div style="flex: 0 0 auto;">
            <?php include '../frontend/partials/navbar.php'; ?>
        </div>

        <!-- Main Content (Centered) -->
        <div class="w3-light-grey"
            style="flex: 1 0 auto; display: flex; align-items: center; justify-content: center; padding-top: 60px; padding-bottom: 20px;">
            <div class="w3-card-4 w3-round-large w3-white" style="max-width:450px; width:90%;">
                <header class="w3-container w3-red w3-center" style="border-radius:4px 4px 0 0;">
                    <h2 class="w3-margin"><?php echo __('login_title'); ?></h2>
                </header>
                <div class="w3-container w3-padding-large">
                    <p class="w3-center"><?php echo __('login_no_account'); ?> <a
                            href="<?php echo _route('register'); ?>"
                            class="w3-text-blue"><b><?php echo __('login_create_account'); ?></b></a></p>

                    <?php if (isset($error)): ?>
                        <div class="w3-panel w3-pale-red w3-border w3-round">
                            <p><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo _route('login'); ?>" autocomplete="off">
                        <label class="w3-text-grey"><b><?php echo __('login_sf_username'); ?></b></label>
                        <input class="w3-input w3-border w3-round w3-margin-bottom" type="text" name="username" required
                            placeholder="Nom d'utilisateur ou Email" autocomplete="off">

                        <label class="w3-text-grey"><b><?php echo __('login_sf_password'); ?></b></label>
                        <input class="w3-input w3-border w3-round w3-margin-bottom" type="password" name="password"
                            required placeholder="Votre mot de passe" autocomplete="new-password">

                        <button type="submit"
                            class="w3-button w3-red w3-block w3-round w3-padding-large w3-margin-top"><?php echo __('login_btn'); ?></button>
                    </form>
                    <p class="w3-center w3-margin-top">
                        <a href="<?php echo _route('forgot_password'); ?>" class="w3-text-grey w3-hover-text-red"><i
                                class="fa fa-unlock-alt"></i> Mot de passe oublié ?</a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="flex: 0 0 auto;">
            <?php include '../frontend/partials/footer.php'; ?>
        </div>

    </div>

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