<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Connexion à la base de données
require_once "config/db.php";
require_once "helpers/TranslationHelper.php";

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
    <link rel="stylesheet" href="/frontend/css/styles.css">
    <style>
        .doctors-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 30px;
            justify-content: center;
        }

        .doctor-column {
            flex: 0 0 300px;
            /* Fixed width base, can grow */
            max-width: 100%;
            display: flex;
            /* Makes the card fill height */
        }

        .doctor-card {
            display: flex;
            flex-direction: column;
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .doctor-card h3 {
            color: #d32f2f;
            margin-top: 0;
            min-height: 2em;
            /* Ensure title height consistency */
        }

        .doctor-specialty {
            color: #666;
            font-style: italic;
            margin-bottom: 10px;
        }

        .doctor-description {
            flex-grow: 1;
            /* Pushes button to bottom */
            margin-bottom: 20px;
        }

        .doctor-icon {
            font-size: 60px;
            color: #d32f2f;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

    <?php include '../frontend/partials/navbar.php'; ?>

    <br><br><br><br><br><br>

    <div class="w3-container">
        <h1 class="w3-center"><?php echo __('doctors_title'); ?></h1>
        <p class="w3-center"><?php echo __('home_subtitle'); ?></p>

        <div class="doctors-grid">
            <?php foreach ($doctors as $doctor) { ?>
                <div class="doctor-column">
                    <div class="doctor-card w3-center">
                        <i class="fa fa-user-md doctor-icon"></i>
                        <h3>Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h3>
                        <p class="doctor-specialty"><?php echo htmlspecialchars($doctor['specialty']); ?></p>
                        <p class="doctor-description"><?php echo htmlspecialchars($doctor['description']); ?></p>

                        <a href="/doctor_availability?doctor_id=<?php echo $doctor['doctor_id']; ?>"
                            class="w3-button w3-blue w3-round" style="margin-top: auto;">
                            <?php echo __('doctors_view_avail'); ?>
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

    <?php include '../frontend/partials/footer.php'; ?>

</body>

</html>