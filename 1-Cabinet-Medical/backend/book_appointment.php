<?php
require_once 'helpers/TranslationHelper.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté, rediriger vers la page de connexion si ce n'est pas le cas
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header("Location: " . _route('login'));
    exit;
}

// Inclure le fichier de connexion à la base de données (db.php)
require_once "config/db.php";

// Récupérer la liste des médecins
$stmtDoctors = $conn->prepare("SELECT doctor_id, first_name, last_name, specialty FROM DOCTOR ORDER BY last_name, first_name");
$stmtDoctors->execute();
$doctors = $stmtDoctors->fetchAll();

// Récupérer les paramètres pré-remplis depuis l'URL (venant de doctor_availability.php)
$prefillDoctorId = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : '';
$prefillDate = isset($_GET['date']) ? $_GET['date'] : '';
$prefillTime = isset($_GET['time']) ? $_GET['time'] : '';

// Vérifier si le formulaire de prise de rendez-vous est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $date = isset($_POST['date']) ? $_POST['date'] : $prefillDate;
    $time = isset($_POST['time']) ? $_POST['time'] : $prefillTime;
    $doctor_id = isset($_POST['doctor_id']) ? $_POST['doctor_id'] : $prefillDoctorId;
    $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
    $isFirstAppointment = isset($_POST['is_first_appointment']) ? true : false;

    // Update prefills for the form view
    $prefillDate = $date;
    $prefillTime = $time;
    $prefillDoctorId = $doctor_id;

    // Only process booking if all required fields are present
    if (empty($date) || empty($time) || empty($doctor_id) || empty($reason)) {
        // Just a refresh/selection update, do not insert yet
    } else {
        // ... proceed with insertion logic ...

        // Vérifier si la date sélectionnée est un samedi ou dimanche
        $selectedDay = date('N', strtotime($date));
        if ($selectedDay === '6' || $selectedDay === '7') {
            // Rediriger avec un message d'erreur si la date est un samedi ou dimanche
            header("Location: " . _route('book_appointment', ['error' => 'invalid_date']));
            exit;
        }

        // Récupérer l'ID du patient à partir de la session
        $patientId = $_SESSION['user_id'];

        // Vérifier si le patient a déjà un rendez-vous en attente avec ce médecin
        $stmtPending = $conn->prepare("SELECT COUNT(*) FROM APPOINTMENT WHERE patient_id = :patient_id AND doctor_id = :doctor_id AND status = 'pending'");
        $stmtPending->execute(['patient_id' => $patientId, 'doctor_id' => $doctor_id]);
        $pendingCount = $stmtPending->fetchColumn();

        $canInsert = false;

        if ($pendingCount > 0) {
            $errorMessage = "Vous avez déjà un rendez-vous en attente avec ce médecin. Veuillez attendre la validation ou annuler votre rendez-vous actuel.";
        } elseif ($isFirstAppointment) {
            // Vérifier si le patient a déjà eu un premier rendez-vous avec ce médecin
            $stmtFirst = $conn->prepare("SELECT COUNT(*) FROM APPOINTMENT WHERE patient_id = :patient_id AND doctor_id = :doctor_id AND is_first_appointment = true");
            $stmtFirst->execute(['patient_id' => $patientId, 'doctor_id' => $doctor_id]);
            $firstCount = $stmtFirst->fetchColumn();

            if ($firstCount > 0) {
                $errorMessage = "Vous avez déjà eu un premier rendez-vous avec ce médecin. Veuillez décocher l'option 'Première visite'.";
            } else {
                $canInsert = true;
            }
        } else {
            $canInsert = true;
        }

        if ($canInsert) {
            // Insérer le rendez-vous dans la base de données
            $stmt = $conn->prepare("
            INSERT INTO APPOINTMENT 
            (patient_id, doctor_id, appointment_date, appointment_time, reason, is_first_appointment, status) 
            VALUES 
            (:patient_id, :doctor_id, :appointment_date, :appointment_time, :reason, :is_first_appointment, 'pending')
        ");

            try {
                $stmt->execute([
                    'patient_id' => $patientId,
                    'doctor_id' => $doctor_id,
                    'appointment_date' => $date,
                    'appointment_time' => $time,
                    'reason' => $reason,
                    'is_first_appointment' => $isFirstAppointment ? 't' : 'f'
                ]);

                // Récupérer les informations complètes pour l'email
                require_once 'helpers/EmailHelper.php';

                // Infos patient
                $stmtPatient = $conn->prepare("SELECT * FROM PATIENT WHERE patient_id = :id");
                $stmtPatient->execute(['id' => $patientId]);
                $patient = $stmtPatient->fetch();

                // Infos médecin
                $stmtDoc = $conn->prepare("SELECT * FROM DOCTOR WHERE doctor_id = :id");
                $stmtDoc->execute(['id' => $doctor_id]);
                $doctorInfo = $stmtDoc->fetch();

                if ($patient && $doctorInfo) {
                    $emailHelper = new EmailHelper();

                    // Préparer les données pour l'email
                    $appointmentData = [
                        'appointment_date' => $date,
                        'appointment_time' => $time,
                        'reason' => $reason,
                        'doctor_first_name' => $doctorInfo['first_name'],
                        'doctor_last_name' => $doctorInfo['last_name'],
                        'specialty' => $doctorInfo['specialty'],
                        'is_first_appointment' => $isFirstAppointment
                    ];

                    // Envoyer l'email
                    try {
                        $emailHelper->sendAppointmentConfirmation(
                            $patient['email'],
                            $patient['first_name'] . ' ' . $patient['last_name'],
                            $appointmentData
                        );
                    } catch (Exception $e) {
                        // Log l'erreur mais ne pas bloquer la redirection
                        error_log("Erreur envoi email: " . $e->getMessage());
                    }
                }

                // Rediriger vers le tableau de bord avec un message de succès
                header("Location: " . _route('dashboard', ['success' => 'appointment_added']));
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23505) { // Unique violation
                    $errorMessage = "Créneau déjà pris. Veuillez choisir une autre heure.";
                } else {
                    $errorMessage = "Erreur lors de la création du rendez-vous: " . $e->getMessage();
                }
            }
        } // End of if ($canInsert)
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title><?php echo __('book_title'); ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
        <div class="centered-box">
            <h2><?php echo __('book_title'); ?></h2>

            <?php if (isset($errorMessage)) { ?>
                <p style="color: red;"><?php echo htmlspecialchars($errorMessage); ?></p>
            <?php } elseif (isset($successMessage)) { ?>
                <p style="color: green;"><?php echo htmlspecialchars($successMessage); ?></p>
            <?php } ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_date') { ?>
                <p style="color: red;"><?php echo __('book_error_weekend') ?? 'Weekends are not available.'; ?></p>
            <?php } ?>

            <p><button class="w3-button w3-blue"
                    onclick="openAvailabilitySidebar()"><?php echo __('avail_title'); ?></button></p>
            <br>

            <form method="POST" action="<?php echo _route('book_appointment'); ?>">
                <label for="doctor_id"><?php echo __('book_select_doctor'); ?></label>
                <select id="doctor_id" name="doctor_id" required onchange="this.form.submit()">
                    <option value=""><?php echo __('book_select_default'); ?></option>
                    <?php foreach ($doctors as $doctor) { ?>
                        <option value="<?php echo $doctor['doctor_id']; ?>" <?php echo ($prefillDoctorId == $doctor['doctor_id']) ? 'selected' : ''; ?>>
                            Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                            - <?php echo htmlspecialchars($doctor['specialty']); ?>
                        </option>
                    <?php } ?>
                </select><br><br>

                <label for="date"><?php echo __('book_date'); ?></label>
                <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($prefillDate); ?>"
                    min="<?php echo date('Y-m-d'); ?>" required onchange="this.form.submit()"><br><br>

                <?php
                // Fetch booked slots if doctor and date are selected
                $bookedSlots = [];
                if (!empty($prefillDoctorId) && !empty($prefillDate)) {
                    $stmtBooked = $conn->prepare("SELECT appointment_time FROM APPOINTMENT WHERE doctor_id = :doctor_id AND appointment_date = :date AND status != 'cancelled'");
                    $stmtBooked->execute(['doctor_id' => $prefillDoctorId, 'date' => $prefillDate]);
                    $bookedSlots = $stmtBooked->fetchAll(PDO::FETCH_COLUMN);
                }

                $slots = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00'];
                ?>

                <label for="time"><?php echo __('book_time'); ?></label>
                <select id="time" name="time" required>
                    <option value="">Sélectionner un créneau
                    </option>
                    <?php foreach ($slots as $slot) {
                        $isBooked = in_array($slot . ':00', $bookedSlots);
                        $style = $isBooked ? 'color: red; background-color: #ffe6e6;' : '';
                        $disabled = $isBooked ? 'disabled' : '';
                        $selected = ($prefillTime == $slot) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $slot; ?>" <?php echo $selected; ?> style="<?php echo $style; ?>" <?php echo $disabled; ?>>
                            <?php echo $slot; ?>     <?php echo $isBooked ? '(' . __('avail_taken') . ')' : ''; ?>
                        </option>
                    <?php } ?>
                </select><br><br>

                <label for="reason"><?php echo __('book_reason'); ?></label>
                <textarea id="reason" name="reason" rows="4" cols="50"
                    required><?php echo isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : ''; ?></textarea><br><br>

                <label for="is_first_appointment">
                    <input type="checkbox" id="is_first_appointment" name="is_first_appointment" <?php echo isset($_POST['is_first_appointment']) ? 'checked' : ''; ?>>
                    <?php echo __('book_first_visit'); ?>
                </label><br><br>

                <input type="submit" value="<?php echo __('book_btn'); ?>"
                    style="width: 75%; display: block; margin: 0 auto;">
            </form>
        </div>
    </div>

    <!-- Availability Sidebar Structure -->
    <div id="availability-sidebar" class="sidebar">
        <div class="sidebar-header">
            <h3><?php echo __('avail_title'); ?></h3>
            <button class="close-btn" onclick="closeAvailabilitySidebar()">&times;</button>
        </div>
        <div id="sidebar-content" class="sidebar-content">
        </div>
    </div>
    <div id="sidebar-overlay" onclick="closeAvailabilitySidebar()"></div>

    <script src="../frontend/js/availability.js"></script>

    <!-- Mobile Menu Toggle -->
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

    <?php include '../frontend/partials/footer.php'; ?>
</body>

</html>