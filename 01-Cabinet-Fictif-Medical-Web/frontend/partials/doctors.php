<?php
// Connexion à la base de données
require_once "../../backend/config/db.php";

// Récupérer tous les médecins
$stmt = $conn->prepare("SELECT * FROM DOCTOR ORDER BY last_name, first_name");
$stmt->execute();
$doctors = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Nos Médecins - Cabinet Médical</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../frontend/css/styles.css">
    <style>
        .doctor-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            background-color: #f9f9f9;
        }

        .doctor-card h3 {
            color: #d32f2f;
            margin-top: 0;
        }

        .doctor-specialty {
            color: #666;
            font-style: italic;
            margin-bottom: 10px;
        }

        .doctor-icon {
            font-size: 60px;
            color: #d32f2f;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

    <!-- Navigation bar would be included here, but it's in backend/partials -->
    <div class="w3-top">
        <div class="w3-bar w3-red w3-card w3-left-align w3-large">
            <a href="../backend/index.php" class="w3-bar-item w3-button w3-padding-large w3-white">Accueil</a>
            <a href="../backend/doctor_availability.php"
                class="w3-bar-item w3-button w3-padding-large w3-hover-white">Disponibilités</a>
            <a href="../backend/login.php" class="w3-bar-item w3-button w3-padding-large w3-hover-white">Connexion</a>
            <a href="../backend/register.php"
                class="w3-bar-item w3-button w3-padding-large w3-hover-white">Inscription</a>
        </div>
    </div>

    <br><br><br><br><br><br>

    <div class="w3-container">
        <h1 class="w3-center">Notre Équipe Médicale</h1>
        <p class="w3-center">Découvrez nos médecins spécialisés</p>

        <div class="w3-row-padding" style="margin-top: 30px;">
            <?php foreach ($doctors as $doctor) { ?>
                <div class="w3-third">
                    <div class="doctor-card w3-center">
                        <i class="fa fa-user-md doctor-icon"></i>
                        <h3>Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h3>
                        <p class="doctor-specialty"><?php echo htmlspecialchars($doctor['specialty']); ?></p>
                        <p><?php echo htmlspecialchars($doctor['description']); ?></p>
                        <br>
                        <a href="../backend/doctor_availability.php?doctor_id=<?php echo $doctor['doctor_id']; ?>"
                            class="w3-button w3-blue w3-round">
                            Voir les disponibilités
                        </a>
                    </div>
                </div>
            <?php } ?>
        </div>

        <?php if (empty($doctors)) { ?>
            <div class="w3-panel w3-pale-yellow w3-center">
                <p>Aucun médecin disponible pour le moment.</p>
            </div>
        <?php } ?>
    </div>

    <br><br>

    <!-- Footer -->
    <footer class="w3-container w3-padding-64 w3-center w3-opacity">
        <p>Cabinet Médical © 2025</p>
    </footer>

</body>

</html>