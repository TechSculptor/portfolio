<?php
require_once 'helpers/TranslationHelper.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifie si un utilisateur est déja connecté, redirige vers le tableau de bord si c'est le cas.
if (isset($_SESSION['user_id'])) {
    header("Location: /dashboard");
    exit;
}



// Vérifie si tous les champs du formulaire ont été saisis.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the form values
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];

    // Connexion à la base de donnée
    require_once 'config/db.php';
    require_once 'helpers/EmailHelper.php';

    // Sécurisation du mot de passe avec la fonction hash.
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Générer un token de vérification unique
    $verificationToken = bin2hex(random_bytes(32));

    // Insertion des données dans la table PATIENT avec token de vérification
    // ⭐ MODIFICATION 1 : RETRAIT de "email_verified" de la liste des colonnes ⭐
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
            // ⭐ MODIFICATION 2 : RETRAIT de "false" (qui était la valeur de email_verified) ⭐
            'verification_token' => $verificationToken
        ]);

        // Enregistrement réussi - Envoyer l'email de vérification
        $emailHelper = new EmailHelper();
        $emailSent = $emailHelper->sendVerificationEmail($email, $username, $verificationToken);

        if ($emailSent) {
            $success = "Inscription réussie! Un email de vérification a été envoyé à $email. Veuillez vérifier votre boîte mail pour activer votre compte.";
        } else {
            $success = "Inscription réussie! Cependant, l'email de vérification n'a pas pu être envoyé. Contactez l'administrateur.";
        }
    } catch (PDOException $e) {
        // Enregistrement échoué
        if ($e->getCode() == 23505) { // PostgreSQL unique violation (duplicate key)
            $error = "Cet email ou nom d'utilisateur existe déjà.";
        } else {
            // Affiche l'erreur technique précise
            $error = "Erreur fatale lors de l'insertion : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title><?php echo __('register_title'); ?></title>
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
                <h3><?php echo __('register_title'); ?></h3>
            </header>
            <div class="w3-container w3-padding-large">
                <p><?php echo __('register_already_account'); ?> <a href="login.php"
                        class="w3-text-blue"><?php echo __('register_login_link'); ?></a></p>

                <?php if (isset($success)) { ?>
                    <div class="w3-panel w3-pale-green w3-border w3-round">
                        <p><?php echo htmlspecialchars($success); ?></p>
                        <p><a href="login.php" class="w3-button w3-blue w3-round">Aller à la page de connexion</a></p>
                    </div>
                <?php } ?>
                <?php if (isset($error)) { ?>
                    <div class="w3-panel w3-pale-red w3-border w3-round">
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php } ?>

                <form method="POST" action="register.php">
                    <label for="first_name"><?php echo __('register_sf_firstname'); ?>:</label>
                    <input class="w3-input w3-border w3-round" type="text" name="first_name" required>

                    <label for="last_name"><?php echo __('register_sf_lastname'); ?>:</label>
                    <input class="w3-input w3-border w3-round" type="text" name="last_name" required>

                    <label for="email"><?php echo __('register_sf_email'); ?>:</label>
                    <input class="w3-input w3-border w3-round" type="email" name="email" required>

                    <label for="username"><?php echo __('register_sf_username'); ?>:</label>
                    <input class="w3-input w3-border w3-round" type="text" name="username" required>

                    <label for="password"><?php echo __('register_sf_password'); ?>:</label>
                    <input class="w3-input w3-border w3-round" type="password" name="password" required>

                    <label for="phone"><?php echo __('register_sf_phone'); ?>:</label>
                    <input class="w3-input w3-border w3-round" type="text" name="phone"><br>

                    <button type="submit"
                        class="w3-button w3-red w3-block w3-round"><?php echo __('register_btn'); ?></button>
                </form>
            </div>
        </div>
    </div>

    <?php include '../frontend/partials/footer.php'; ?>
</body>

</html>