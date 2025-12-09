<?php
require_once 'helpers/TranslationHelper.php';
require_once 'config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch all doctors
$stmt = $conn->query("SELECT doctor_id, first_name, last_name, specialty FROM DOCTOR ORDER BY last_name");
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?php echo getLang(); ?>">

<head>
    <title><?php echo __('doctors_title'); ?> - Cabinet Médical</title>
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
    </style>
</head>

<body>

    <?php include '../frontend/partials/navbar.php'; ?>

    <!-- Header -->
    <header class="w3-container w3-red w3-center" style="padding:128px 16px">
        <h1 class="w3-margin w3-jumbo"><?php echo __('doctors_title'); ?></h1>
        <p class="w3-xlarge"><?php echo __('doctors_subtitle'); ?></p>
    </header>

    <!-- Doctors Grid -->
    <div class="w3-row-padding w3-padding-64 w3-container">
        <div class="w3-content">
            <div class="w3-row-padding">
                <?php if (count($doctors) > 0): ?>
                    <?php foreach ($doctors as $doc): ?>
                        <div class="w3-third w3-margin-bottom">
                            <div class="w3-card-4">
                                <div class="w3-container w3-center w3-padding-large">
                                    <i class="fa fa-user-md w3-text-red" style="font-size:80px;"></i>
                                    <h3>Dr. <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></h3>
                                    <p class="w3-text-grey"><?php echo htmlspecialchars($doc['specialty']); ?></p>
                                    <a href="<?php echo _route('doctor_availability', ['doctor_id' => $doc['doctor_id']]); ?>"
                                        class="w3-button w3-black w3-margin-top"><?php echo __('doctors_view_availability'); ?></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="w3-center w3-text-grey"><?php echo __('doctors_none'); ?></p>
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