<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connexion à la base de données
require_once 'config/db.php';

$message = '';
$messageType = '';

// Vérifier si un token est fourni
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];

    // Rechercher le patient avec ce token
    $stmt = $conn->prepare("SELECT patient_id, email, username FROM PATIENT WHERE verification_token = :token AND email_verified = false");
    $stmt->execute(['token' => $token]);

    if ($stmt->rowCount() === 1) {
        $patient = $stmt->fetch();

        // Mise à jour: Marquer l'email comme vérifié et supprimer le token
        $updateStmt = $conn->prepare("UPDATE PATIENT SET email_verified = true, verification_token = NULL WHERE patient_id = :patient_id");
        $updateStmt->execute(['patient_id' => $patient['patient_id']]);

        $message = "Votre adresse email a été vérifiée avec succès! Vous pouvez maintenant vous connecter.";
        $messageType = "success";
    } else {
        $message = "Le lien de vérification est invalide ou a déjà été utilisé.";
        $messageType = "error";
    }
} else {
    $message = "Aucun token de vérification fourni.";
    $messageType = "error";
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Vérification Email - Cabinet Médical</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="/frontend/css/styles.css">
</head>

<body>

    <?php include '../frontend/partials/navbar.php'; ?>

    <br><br><br><br><br><br>

    <div class="w3-container w3-center" style="max-width: 600px; margin: 0 auto;">
        <h2>Vérification de l'adresse email</h2>

        <?php if ($messageType === 'success') { ?>
            <div class="w3-panel w3-pale-green w3-border w3-border-green">
                <p><i class="fa fa-check-circle" style="font-size: 48px; color: green;"></i></p>
                <h3>Email Vérifié!</h3>
                <p><?php echo htmlspecialchars($message); ?></p>
                <br>
                <p><a href="<?php echo _route('login'); ?>" class="w3-button w3-green w3-large">Se connecter</a></p>
            </div>
        <?php } else { ?>
            <div class="w3-panel w3-pale-red w3-border w3-border-red">
                <p><i class="fa fa-times-circle" style="font-size: 48px; color: red;"></i></p>
                <h3>Erreur de Vérification</h3>
                <p><?php echo htmlspecialchars($message); ?></p>
                <br>
                <p>
                    <a href="<?php echo _route('register'); ?>" class="w3-button w3-blue">S'inscrire</a>
                    <a href="<?php echo _route('login'); ?>" class="w3-button w3-gray">Se connecter</a>
                </p>
            </div>
        <?php } ?>

        <br><br>
        <p><a href="<?php echo _route('home'); ?>">Retour à l'accueil</a></p>
    </div>

    <?php include '../frontend/partials/footer.html'; ?>

</body>

</html>