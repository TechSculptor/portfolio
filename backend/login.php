<?php
require_once 'helpers/TranslationHelper.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header("Location: /dashboard");
    exit;
}

// Vérifier si le formulaire de connexion a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les valeurs du formulaire
    $inputUsername = trim($_POST['username']);
    $inputPassword = $_POST['password'];

    // Connexion à la base de donnée
    require_once 'config/db.php';

    // 1. Check PATIENT
    $stmt = $conn->prepare("SELECT patient_id, email, username, password_hash, first_name, last_name, email_verified FROM PATIENT WHERE username = :u OR email = :u");
    $stmt->execute(['u' => $inputUsername]);
    $row = $stmt->fetch();

    if ($row) {
        if (password_verify($inputPassword, $row['password_hash'])) {
            // ⭐ CHECK EMAIL VERIFICATION ⭐
            if (!$row['email_verified']) {
                $error = "Veuillez vérifier votre adresse email avant de vous connecter.";
            } else {
                $_SESSION['user_id'] = $row['patient_id'];
                $_SESSION['user_type'] = 'patient';
                $_SESSION['username'] = $row['first_name'] . ' ' . $row['last_name'];
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $error = "Mot de passe incorrect";
        }
    } else {
        // 2. Check DOCTOR
        $stmt = $conn->prepare("SELECT doctor_id, email, username, password_hash, first_name, last_name FROM DOCTOR WHERE username = :u OR email = :u");
        $stmt->execute(['u' => $inputUsername]);
        $row = $stmt->fetch();

        if ($row) {
            if (password_verify($inputPassword, $row['password_hash'])) {
                $_SESSION['user_id'] = $row['doctor_id'];
                $_SESSION['user_type'] = 'doctor';
                $_SESSION['username'] = 'Dr. ' . $row['first_name'] . ' ' . $row['last_name'];
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Mot de passe incorrect";
            }
        } else {
            // 3. Check ADMIN
            $stmt = $conn->prepare("SELECT admin_id, email, username, password_hash FROM ADMIN WHERE username = :u OR email = :u");
            $stmt->execute(['u' => $inputUsername]);
            $row = $stmt->fetch();

            if ($row) {
                if (password_verify($inputPassword, $row['password_hash'])) {
                    $_SESSION['user_id'] = $row['admin_id'];
                    $_SESSION['user_type'] = 'admin';
                    $_SESSION['username'] = $row['username'];
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = "Mot de passe incorrect";
                }
            } else {
                // User not found
                $error = "Utilisateur non trouvé";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title><?php echo __('login_title'); ?></title>
    <link rel="icon" type="image/png" href="/frontend/img/favicon.png">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="/frontend/css/styles.css">
</head>

<body>
    <?php include '../frontend/partials/navbar.php'; ?>

    <div class="main-content-wrapper">
        <div class="centered-box w3-card-4 w3-round w3-white">
            <header class="w3-container w3-red w3-round-large">
                <h3><?php echo __('login_title'); ?></h3>
            </header>
            <div class="w3-container w3-padding-large">
                <p><?php echo __('login_no_account'); ?> <a href="register.php"
                        class="w3-text-blue"><?php echo __('login_create_account'); ?></a></p>

                <?php if (isset($error)) { ?>
                    <div class="w3-panel w3-pale-red w3-border w3-round">
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php } ?>

                <form method="POST" action="login.php">
                    <label for="username"><?php echo __('login_sf_username'); ?>:</label>
                    <input class="w3-input w3-border w3-round" type="text" name="username" required><br>

                    <label for="password"><?php echo __('login_sf_password'); ?>:</label>
                    <input class="w3-input w3-border w3-round" type="password" name="password" required><br><br>

                    <button type="submit"
                        class="w3-button w3-red w3-block w3-round"><?php echo __('login_btn'); ?></button>
                </form>
            </div>
        </div>
    </div>

    <?php include '../frontend/partials/footer.php'; ?>
</body>

</html>