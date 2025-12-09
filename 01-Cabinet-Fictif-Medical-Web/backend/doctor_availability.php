<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connexion à la base de données
require_once "config/db.php";
require_once "helpers/TranslationHelper.php";

// Récupérer tous les médecins
$stmtDoctors = $conn->prepare("SELECT doctor_id, first_name, last_name, specialty FROM DOCTOR ORDER BY last_name, first_name");
$stmtDoctors->execute();
$doctors = $stmtDoctors->fetchAll();

// Variables pour la sélection
$selectedDoctorId = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : null;
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d', strtotime('+1 day'));

// Si un médecin est sélectionné, récupérer ses informations
$selectedDoctor = null;
$availableSlots = [];
$bookedSlots = [];

if ($selectedDoctorId && $selectedDate) {
    // Récupérer les informations du médecin
    $stmt = $conn->prepare("SELECT * FROM DOCTOR WHERE doctor_id = :doctor_id");
    $stmt->execute(['doctor_id' => $selectedDoctorId]);
    $selectedDoctor = $stmt->fetch();

    // Récupérer les rendez-vous déjà pris pour ce médecin à cette date
    $stmt = $conn->prepare("
        SELECT appointment_time 
        FROM APPOINTMENT 
        WHERE doctor_id = :doctor_id 
        AND appointment_date = :date
        AND status != 'cancelled'
    ");
    $stmt->execute([
        'doctor_id' => $selectedDoctorId,
        'date' => $selectedDate
    ]);

    while ($row = $stmt->fetch()) {
        $bookedSlots[] = substr($row['appointment_time'], 0, 5); // Format HH:MM
    }

    // Générer tous les créneaux possibles (9h-12h et 13h-16h, par intervalle de 30min)
    $allSlots = [];

    // Matinée: 9h00 - 11h30
    for ($hour = 9; $hour < 12; $hour++) {
        $allSlots[] = sprintf("%02d:00", $hour);
        $allSlots[] = sprintf("%02d:30", $hour);
    }

    // Après-midi: 13h00 - 16h00
    for ($hour = 13; $hour <= 15; $hour++) {
        $allSlots[] = sprintf("%02d:00", $hour);
        if ($hour < 16) {
            $allSlots[] = sprintf("%02d:30", $hour);
        }
    }
    $allSlots[] = "16:00";

    // Déterminer les créneaux disponibles
    foreach ($allSlots as $slot) {
        $availableSlots[$slot] = !in_array($slot, $bookedSlots);
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title><?php echo __('avail_title'); ?></title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="/frontend/css/styles.css">
    <style>
        .time-slot {
            display: inline-block;
            margin: 5px;
            padding: 10px 20px;
            border: 2px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
            min-width: 80px;
            text-align: center;
        }

        .time-slot.available {
            background-color: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }

        .time-slot.available:hover {
            background-color: #45a049;
        }

        .time-slot.booked {
            background-color: #f44336;
            color: white;
            border-color: #f44336;
            cursor: not-allowed;
        }

        .slot-grid {
            margin: 20px 0;
        }

        .slot-section {
            margin: 15px 0;
        }

        .slot-section h4 {
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    <?php include '../frontend/partials/navbar.php'; ?>

    <br><br><br><br><br><br>

    <div class="w3-container">
        <h2><?php echo __('avail_title'); ?></h2>
        <p><?php echo __('avail_desc'); ?></p>

        <form method="GET" action="<?php echo _route('doctor_availability'); ?>">
            <label for="doctor_id"><?php echo __('book_select_doctor'); ?></label>
            <select id="doctor_id" name="doctor_id" required onchange="this.form.submit()">
                <option value=""><?php echo __('book_select_default'); ?></option>
                <?php foreach ($doctors as $doctor) { ?>
                    <option value="<?php echo $doctor['doctor_id']; ?>" <?php echo ($selectedDoctorId == $doctor['doctor_id']) ? 'selected' : ''; ?>>
                        Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                        - <?php echo htmlspecialchars($doctor['specialty']); ?>
                    </option>
                <?php } ?>
            </select><br><br>

            <label for="date"><?php echo __('book_date'); ?></label>
            <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>"
                min="<?php echo date('Y-m-d'); ?>" required onchange="this.form.submit()"><br><br>
        </form>

        <?php if ($selectedDoctor && $selectedDate) { ?>
            <div class="w3-panel w3-pale-blue w3-border">
                <h3>Dr. <?php echo htmlspecialchars($selectedDoctor['first_name'] . ' ' . $selectedDoctor['last_name']); ?>
                </h3>
                <p><strong><?php echo __('doctors_specialty'); ?></strong>
                    <?php echo htmlspecialchars($selectedDoctor['specialty']); ?></p>
                <p><strong><?php echo __('book_date'); ?></strong> <?php echo date('d/m/Y', strtotime($selectedDate)); ?>
                </p>
            </div>

            <div class="slot-grid">
                <div class="slot-section">
                    <h4><?php echo __('avail_morning'); ?></h4>
                    <?php
                    foreach ($availableSlots as $time => $available) {
                        $hour = intval(substr($time, 0, 2));
                        if ($hour >= 9 && $hour < 12) {
                            $class = $available ? 'available' : 'booked';
                            $text = $available ? $time . ' ✓' : $time . ' ✗';
                            if ($available && isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'patient') {
                                $msg = "Vous avez sélectionné le créneau de $time.\\n\\nVeuillez confirmer pour accéder au formulaire et saisir vos informations.";
                                echo '<a href="' . _route('book_appointment') . '?doctor_id=' . $selectedDoctorId .
                                    '&date=' . $selectedDate . '&time=' . $time . '" 
                                    onclick="return confirm(\'' . $msg . '\');" 
                                    class="time-slot ' . $class . '">' .
                                    $text . '</a>';
                            } else {
                                echo '<span class="time-slot ' . $class . '">' . $text . '</span>';
                            }
                        }
                    }
                    ?>
                </div>

                <div class="slot-section">
                    <h4><?php echo __('avail_afternoon'); ?></h4>
                    <?php
                    foreach ($availableSlots as $time => $available) {
                        $hour = intval(substr($time, 0, 2));
                        if ($hour >= 13 && $hour <= 16) {
                            $class = $available ? 'available' : 'booked';
                            $text = $available ? $time . ' ✓' : $time . ' ✗';
                            if ($available && isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'patient') {
                                $msg = "Vous avez sélectionné le créneau de $time.\\n\\nVeuillez confirmer pour accéder au formulaire et saisir vos informations.";
                                echo '<a href="' . _route('book_appointment') . '?doctor_id=' . $selectedDoctorId .
                                    '&date=' . $selectedDate . '&time=' . $time . '" 
                                    onclick="return confirm(\'' . $msg . '\');" 
                                    class="time-slot ' . $class . '">' .
                                    $text . '</a>';
                            } else {
                                echo '<span class="time-slot ' . $class . '">' . $text . '</span>';
                            }
                        }
                    }
                    ?>
                </div>
            </div>

            <div class="w3-panel w3-pale-yellow">
                <p><strong><?php echo __('avail_legend'); ?></strong></p>
                <p>
                    <span class="time-slot available" style="display: inline-block;">09:00 ✓</span>
                    <?php echo __('avail_free'); ?>
                </p>
                <p>
                    <span class="time-slot booked" style="display: inline-block;">10:00 ✗</span>
                    <?php echo __('avail_taken'); ?>
                </p>
            </div>

            <?php if (!isset($_SESSION['user_id'])) { ?>
                <div class="w3-panel w3-pale-red">
                    <p><strong>Note:</strong> <?php echo __('avail_login_note'); ?></p>
                    <p><a href="<?php echo _route('login'); ?>"
                            class="w3-button w3-blue"><?php echo __('avail_login_btn'); ?></a></p>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="w3-panel w3-pale-yellow">
                <p><?php echo __('avail_desc'); ?></p>
            </div>
        <?php } ?>
    </div>

    <?php include '../frontend/partials/footer.php'; ?>

</body>

</html>