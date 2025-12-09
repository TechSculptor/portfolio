<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté, rediriger vers la page de connexion si non connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

// Inclure le fichier de connexion à la base de données (db.php)
require_once "config/db.php";

// Récupérer l'ID et le type de l'utilisateur connecté
$userId = $_SESSION['user_id'];
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;

// Récupérer les informations de l'utilisateur
if ($userType === 'admin') {
    $stmt = $conn->prepare("SELECT * FROM ADMIN WHERE admin_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch();
    $displayName = $user['username'];
} elseif ($userType === 'doctor') {
    $stmt = $conn->prepare("SELECT * FROM DOCTOR WHERE doctor_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch();
    $displayName = 'Dr. ' . $user['first_name'] . ' ' . $user['last_name'];
} elseif ($userType === 'patient') {
    $stmt = $conn->prepare("SELECT * FROM PATIENT WHERE patient_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch();
    $displayName = $user['first_name'] . ' ' . $user['last_name'];
} else {
    // Type inconnu ou non défini
    $displayName = "Utilisateur";
}

// Traitement de la soumission du formulaire d'annulation de rendez-vous
if (isset($_POST['cancel_appointment_id'])) {
    $appointmentId = $_POST['cancel_appointment_id'];

    if ($userType === 'patient') {
        // Vérifier si le rendez-vous appartient à l'utilisateur connecté
        $sqlCheck = $conn->prepare("SELECT * FROM APPOINTMENT WHERE appointment_id = :appt_id AND patient_id = :user_id");
        $sqlCheck->execute(['appt_id' => $appointmentId, 'user_id' => $userId]);

        if ($sqlCheck->rowCount() === 1) {
            // Supprimer le rendez-vous de la base de données
            $sqlDelete = $conn->prepare("DELETE FROM APPOINTMENT WHERE appointment_id = :appt_id");
            $sqlDelete->execute(['appt_id' => $appointmentId]);
        }
    } else {
        // Admin can cancel any appointment
        $sqlDelete = $conn->prepare("DELETE FROM APPOINTMENT WHERE appointment_id = :appt_id");
        $sqlDelete->execute(['appt_id' => $appointmentId]);
    }

    // Rediriger vers la page de tableau de bord
    header("Location: dashboard.php");
    exit;
}

// ADMIN ACTION HANDLERS
if ($userType === 'admin') {
    // 1. Add Doctor
    if (isset($_POST['add_doctor'])) {
        $stmt = $conn->prepare("INSERT INTO DOCTOR (first_name, last_name, specialty, description, username, password_hash) VALUES (:first_name, :last_name, :specialty, :description, :username, :password_hash)");
        try {
            // Default password for new docs: doctor123
            $defaultHash = password_hash('doctor123', PASSWORD_BCRYPT);
            $username = 'dr.' . strtolower($_POST['last_name']);

            $stmt->execute([
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'specialty' => $_POST['specialty'],
                'description' => $_POST['description'],
                'username' => $username,
                'password_hash' => $defaultHash
            ]);
            header("Location: dashboard.php?msg=doctor_added");
            exit;
        } catch (PDOException $e) {
            // Handle error (ideally show message)
        }
    }

    // 2. Delete Doctor
    if (isset($_POST['delete_doctor'])) {
        $stmt = $conn->prepare("DELETE FROM DOCTOR WHERE doctor_id = :id");
        try {
            $stmt->execute(['id' => $_POST['doctor_id']]);
            header("Location: dashboard.php?msg=doctor_deleted");
            exit;
        } catch (PDOException $e) {
            // Likely has appointments
            header("Location: dashboard.php?err=doctor_has_appts");
            exit;
        }
    }

    // 3. Update Doctor
    if (isset($_POST['update_doctor'])) {
        $stmt = $conn->prepare("UPDATE DOCTOR SET first_name = :first_name, last_name = :last_name, specialty = :specialty, description = :description WHERE doctor_id = :id");
        try {
            $stmt->execute([
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'specialty' => $_POST['specialty'],
                'description' => $_POST['description'],
                'id' => $_POST['doctor_id']
            ]);
            header("Location: dashboard.php?msg=doctor_updated");
            exit;
        } catch (PDOException $e) {
            // Handle error
        }
    }
}


// LOGIC: Filter and Data Fetching
$appointments = []; // For Patient List
$appointmentsGrid = []; // For Weekly View (Admin/Doctor)
$selectedDate = date('Y-m-d');
$mondayTs = 0;
$sundayTs = 0;

if ($userType === 'admin' || $userType === 'doctor') {
    // 1. Determine Week Range
    $selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $ts = strtotime($selectedDate);
    // Find Monday of this week (N: 1=Mon, 7=Sun)
    $dayOfWeek = date('N', $ts);
    $mondayTs = strtotime('-' . ($dayOfWeek - 1) . ' days', $ts);
    $sundayTs = strtotime('+6 days', $mondayTs);

    $startDate = date('Y-m-d', $mondayTs);
    $endDate = date('Y-m-d', $sundayTs);

    // 2. Base Query
    $sql = "
        SELECT 
            a.appointment_id, 
            a.appointment_date, 
            a.appointment_time, 
            a.reason, 
            a.status,
            p.first_name as patient_first_name,
            p.last_name as patient_last_name,
            d.first_name as doctor_first_name,
            d.last_name as doctor_last_name,
            d.specialty,
            a.doctor_id
        FROM APPOINTMENT a
        JOIN PATIENT p ON a.patient_id = p.patient_id
        JOIN DOCTOR d ON a.doctor_id = d.doctor_id
        WHERE a.appointment_date BETWEEN :start AND :end
    ";

    $params = ['start' => $startDate, 'end' => $endDate];

    // Admin Filter
    if ($userType === 'admin') {
        if (isset($_GET['view_doctor']) && is_numeric($_GET['view_doctor'])) {
            $sql .= " AND a.doctor_id = :doc_id";
            $params['doc_id'] = $_GET['view_doctor'];
        }
    } elseif ($userType === 'doctor') {
        $sql .= " AND a.doctor_id = :user_id";
        $params['user_id'] = $userId;
    }

    $sql .= " ORDER BY a.appointment_date, a.appointment_time";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rawAppointments = $stmt->fetchAll();

    // 3. Restructure for Grid: [Date][Time] = AppointmentData
    foreach ($rawAppointments as $appt) {
        $d = $appt['appointment_date'];
        // Format time to H:i (09:00:00 -> 09:00)
        $t = substr($appt['appointment_time'], 0, 5);
        $appointmentsGrid[$d][$t] = $appt;
    }

} elseif ($userType === 'patient') {
    // Patient: Logic remains simple list
    $stmt = $conn->prepare("
        SELECT 
            a.appointment_id, 
            a.appointment_date, 
            a.appointment_time, 
            a.reason, 
            a.status,
            d.first_name as doctor_first_name,
            d.last_name as doctor_last_name,
            a.doctor_id
        FROM APPOINTMENT a
        JOIN DOCTOR d ON a.doctor_id = d.doctor_id
        JOIN PATIENT p ON a.patient_id = p.patient_id
        WHERE a.patient_id = :user_id
        ORDER BY a.appointment_date, a.appointment_time
    ");
    $stmt->execute(['user_id' => $userId]);
    $appointments = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Tableau de Bord - Cabinet Médical</title>
    <link rel="icon" type="image/png" href="/frontend/img/favicon.png">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="/frontend/css/styles.css">
    <script src="/frontend/js/availability.js"></script>
    <style>
        body,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: "Lato", sans-serif
        }

        .dashboard-container {
            max-width: 1200px;
            margin: auto;
            padding-top: 50px;
            padding-bottom: 50px;
        }

        .appointment-card {
            margin-bottom: 20px;
            text-align: left;
        }

        /* Week Grid Styles */
        .week-grid {
            display: flex;
            flex-wrap: wrap;
            margin-top: 20px;
            border: 1px solid #ccc;
            background-color: white;
        }

        .day-column {
            flex: 1;
            min-width: 140px;
            border-right: 1px solid #eee;
        }

        .day-column:last-child {
            border-right: none;
        }

        .day-header {
            padding: 10px;
            text-align: center;
            background-color: #f44336;
            color: white;
            border-bottom: 1px solid #ccc;
            font-weight: bold;
        }

        .day-column.weekend .day-header {
            background-color: #e57373;
        }

        .day-column.weekend {
            background-color: #f9f9f9;
        }

        .time-slot {
            height: 70px;
            border-bottom: 1px solid #eee;
            padding: 2px;
            position: relative;
            font-size: 0.8em;
        }

        .time-label {
            color: #888;
            font-size: 0.7em;
            position: absolute;
            top: 2px;
            left: 4px;
        }

        .appt-block {
            background-color: #2196F3;
            color: white;
            border-radius: 4px;
            padding: 4px;
            height: 90%;
            width: 96%;
            margin: 2% auto;
            overflow: hidden;
            font-size: 0.9em;
            cursor: pointer;
            box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .appt-block.confirmed {
            background-color: #4CAF50;
        }

        .appt-block.cancelled {
            background-color: #f44336;
            opacity: 0.7;
        }

        .appt-block:hover {
            transform: scale(1.02);
            z-index: 10;
            position: relative;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
        }

        /* Sidebar Styles */
        .sidebar {
            height: 100%;
            width: 0;
            position: fixed;
            z-index: 1000;
            top: 0;
            right: 0;
            background-color: #fff;
            overflow-x: hidden;
            transition: 0.5s;
            box-shadow: -2px 0 5px rgba(0, 0, 0, 0.2);
        }

        .sidebar.open {
            width: 350px;
        }

        .sidebar-header {
            background-color: #f44336;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 2rem;
            cursor: pointer;
        }

        .sidebar-content {
            padding: 20px;
        }

        #sidebar-overlay {
            display: none;
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 900;
        }

        /* Admin specific */
        .admin-option {
            cursor: pointer;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
        }

        .admin-option:hover {
            background-color: #f1f1f1;
        }

        .admin-option i {
            margin-right: 15px;
            font-size: 1.2em;
            color: #d32f2f;
        }
    </style>
</head>

<body>

    <?php include '../frontend/partials/navbar.php'; ?>

    <div class="w3-content dashboard-container">

        <div class="w3-center">
            <h2><?php echo __('dashboard_title'); ?></h2>
            <p class="w3-opacity"><i><?php echo __('dashboard_welcome'); ?>
                    <?php echo htmlspecialchars($displayName); ?></i></p>
            <?php if ($userType === 'admin')
                echo '<p><span class="w3-tag w3-red">' . __('dashboard_admin_badge') . '</span></p>'; ?>
            <hr>
        </div>

        <!-- Actions -->
        <div class="w3-center w3-section">
            <?php if ($userType === 'patient') { ?>
                <a class="w3-button w3-blue w3-round w3-margin-small" href="/book_appointment">
                    <i class="fa fa-plus"></i> <?php echo __('dashboard_book_appt'); ?>
                </a>
                <button class="w3-button w3-blue w3-round w3-margin-small" onclick="openAvailabilitySidebar()">
                    <i class="fa fa-calendar"></i> <?php echo __('dashboard_view_avail'); ?>
                </button>
            <?php } elseif ($userType === 'admin') { ?>
                <!-- Admin Actions: Manage Doctors -->
                <button class="w3-button w3-green w3-round w3-margin-small" onclick="openAdminSidebar()">
                    <i class="fa fa-user-md"></i> Gérer les médecins
                </button>
            <?php } elseif ($userType === 'doctor') { ?>
                <!-- Doctor Actions: View Planning -->
                <a class="w3-button w3-blue w3-round w3-margin-small" href="#planning">
                    <i class="fa fa-calendar"></i> Mon Planning
                </a>
            <?php } ?>
        </div>

        <?php if ($userType === 'patient') { ?>
            <!-- PATIENT VIEW (Simple List) -->
            <div class="w3-container">
                <h3 class="w3-center w3-text-grey"><?php echo __('dashboard_your_appts'); ?></h3>
                <?php if (isset($_GET['success']) && $_GET['success'] == 'appointment_added') { ?>
                    <div class="w3-panel w3-green w3-display-container w3-round">
                        <span onclick="this.parentElement.style.display='none'"
                            class="w3-button w3-green w3-large w3-display-topright">&times;</span>
                        <p><?php echo __('dashboard_success_appt'); ?></p>
                    </div>
                <?php } ?>

                <?php if (empty($appointments)) { ?>
                    <div class="w3-panel w3-pale-yellow w3-leftbar w3-border-yellow w3-center">
                        <p><?php echo __('dashboard_no_appts'); ?></p>
                    </div>
                <?php } else { ?>
                    <?php foreach ($appointments as $row): ?>
                        <div class="w3-card w3-white w3-round appointment-card">
                            <header
                                class="w3-container <?php echo ($row['status'] === 'cancelled') ? 'w3-red' : (($row['status'] === 'confirmed') ? 'w3-green' : 'w3-blue'); ?>">
                                <h4><i class="fa fa-calendar-check-o"></i>
                                    <?php echo date('d/m/Y', strtotime($row['appointment_date'])); ?> à
                                    <?php echo substr($row['appointment_time'], 0, 5); ?>
                                </h4>
                            </header>
                            <div class="w3-container w3-padding-16">
                                <p><strong><?php echo __('dashboard_doctor'); ?></strong> Dr.
                                    <?php echo htmlspecialchars($row['doctor_first_name'] . ' ' . $row['doctor_last_name']); ?>
                                </p>
                                <p><strong><?php echo __('dashboard_reason'); ?></strong>
                                    <?php echo htmlspecialchars($row['reason']); ?></p>
                                <p><strong><?php echo __('dashboard_status'); ?></strong>
                                    <?php echo __("status_" . $row['status']) ?? $row['status']; ?></p>
                            </div>
                            <?php if ($row['status'] !== 'cancelled') { ?>
                                <footer class="w3-container w3-light-grey w3-padding">
                                    <form method="post" action="/dashboard"
                                        onsubmit="return confirm('<?php echo __('dashboard_confirm_cancel'); ?>');">
                                        <input type="hidden" name="cancel_appointment_id" value="<?php echo $row['appointment_id']; ?>">
                                        <button type="submit"
                                            class="w3-button w3-red w3-round w3-small w3-right"><?php echo __('dashboard_cancel'); ?></button>
                                    </form>
                                </footer>
                            <?php } ?>
                        </div>
                    <?php endforeach; ?>
                <?php } ?>
            </div>

        <?php } elseif ($userType === 'admin' || $userType === 'doctor') { ?>
            <!-- ADMIN & DOCTOR VIEW (Weekly Grid) -->

            <!-- Date Selector -->
            <div class="w3-container w3-card w3-white w3-padding w3-margin-bottom w3-center">
                <form method="get" action="/dashboard">
                    <?php if (isset($_GET['view_doctor']))
                        echo '<input type="hidden" name="view_doctor" value="' . htmlspecialchars($_GET['view_doctor']) . '">'; ?>
                    <label><strong><?php echo __('week_label'); ?></strong></label>
                    <input type="date" name="date" value="<?php echo $selectedDate; ?>" onchange="this.form.submit()">
                    <noscript><button type="submit">Aller</button></noscript>
                    <span class="w3-margin-left">
                        (<?php echo date('d/m', $mondayTs) . ' - ' . date('d/m', $sundayTs); ?>)
                    </span>
                    <a href="/dashboard"
                        class="w3-button w3-small w3-grey w3-round w3-margin-left"><?php echo __('today_btn'); ?></a>
                </form>
            </div>

            <div id="planning" class="week-grid w3-card" style="display: flex; flex-wrap: wrap;">
                <?php
                // Loop 7 days
                for ($i = 0; $i < 7; $i++) {
                    $currentDayTs = strtotime('+' . $i . ' days', $mondayTs);
                    $dateStr = date('Y-m-d', $currentDayTs);
                    $dayNameEnglish = date('l', $currentDayTs);

                    $dayNameEnglish = strtolower(date('l', $currentDayTs));
                    $dayNameTranslated = __($dayNameEnglish);

                    $isWeekend = ($i >= 5); // Sat (5) Sun (6)
                    ?>
                    <div class="day-column <?php echo $isWeekend ? 'weekend' : ''; ?> w3-border-right"
                        style="width: 14.28%; box-sizing: border-box;">
                        <div class="day-header">
                            <?php echo $dayNameTranslated; ?><br>
                            <?php echo date('d/m', $currentDayTs); ?>
                        </div>

                        <?php
                        // Time Slots: 09:00 to 16:30 (30 min intervals)
                        // 9 * 60 = 540 min
                        // 16 * 60 + 30 = 990 min
                        for ($minutes = 540; $minutes <= 990; $minutes += 30) {
                            $h = floor($minutes / 60);
                            $m = $minutes % 60;
                            $timeStr = sprintf('%02d:%02d', $h, $m);

                            // Check if appointment matches this time slot (approximate)
                            // The DB stores seconds, so we match HH:MM
                            $appt = null;
                            if (isset($appointmentsGrid[$dateStr])) {
                                // We need to check exact match or within range if we wanted duration, but prompt assumes slot match
                                // Database stores '09:00:00'. We match '09:00'.
                                // Using array key check from before might need adjustment if logic was rough
                                $appt = isset($appointmentsGrid[$dateStr][$timeStr]) ? $appointmentsGrid[$dateStr][$timeStr] : null;
                            }
                            ?>
                            <div class="time-slot" title="<?php echo $timeStr; ?>">
                                <span class="time-label"><?php echo $timeStr; ?></span>
                                <?php if ($appt): ?>
                                    <div class="appt-block <?php echo $appt['status']; ?>"
                                        onclick="alert('Patient: <?php echo htmlspecialchars($appt['patient_first_name'] . ' ' . $appt['patient_last_name']) . '\nMotif: ' . htmlspecialchars($appt['reason']); ?>')">
                                        <strong><?php echo htmlspecialchars(substr($appt['patient_first_name'], 0, 1) . '. ' . $appt['patient_last_name']); ?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>

            <div class="legend w3-margin-top w3-center">
                <span class="w3-tag w3-blue">En attente</span>
                <span class="w3-tag w3-green">Confirmé</span>
                <span class="w3-tag w3-red">Annulé</span>
                <p class="w3-small w3-text-grey w3-margin-top">Cliquez sur un rendez-vous pour voir les détails.</p>
            </div>
        <?php } ?>
    </div>

    <!-- Availability Sidebar (Patient Only) -->
    <?php if ($userType === 'patient') { ?>
        <div id="availability-sidebar" class="sidebar">
            <div class="sidebar-header">
                <h3><?php echo __('avail_title'); ?></h3>
                <button class="close-btn" onclick="closeAvailabilitySidebar()">&times;</button>
            </div>
            <div id="sidebar-content" class="sidebar-content"></div>
        </div>
        <div id="sidebar-overlay" onclick="closeAvailabilitySidebar()"></div>
    <?php } ?>

    <!-- Admin Sidebar -->
    <?php if ($userType === 'admin') { ?>
        <div id="adminSidebar" class="sidebar">
            <div class="sidebar-header">
                <h3><?php echo __('admin_doc_title'); ?></h3>
                <button class="close-btn"
                    onclick="document.getElementById('adminSidebar').classList.remove('open'); document.getElementById('sidebar-overlay').style.display='none';">&times;</button>
            </div>
            <div class="sidebar-content w3-container">
                <!-- Main Options -->
                <div id="adminMenu">
                    <!-- Toggle Add Form -->
                    <button class="w3-button w3-blue w3-block w3-margin-bottom" onclick="toggleAddForm()">
                        <i class="fa fa-plus"></i> Ajouter un médecin
                    </button>

                    <!-- Add/Edit Form -->
                    <div id="doctorForm" class="w3-card w3-padding w3-light-grey w3-margin-bottom" style="display:none;">
                        <h4 id="formTitle">Nouveau Médecin</h4>
                        <form method="POST" action="/dashboard">
                            <input type="hidden" name="doctor_id" id="form_doctor_id">

                            <label>Prénom</label>
                            <input type="text" name="first_name" id="form_first_name" class="w3-input w3-border" required>

                            <label>Nom</label>
                            <input type="text" name="last_name" id="form_last_name" class="w3-input w3-border" required>

                            <label>Spécialité</label>
                            <input type="text" name="specialty" id="form_specialty" class="w3-input w3-border" required>

                            <label>Description</label>
                            <input type="text" name="description" id="form_description" class="w3-input w3-border">

                            <div class="w3-section">
                                <button type="submit" name="add_doctor" id="btnRunAction"
                                    class="w3-button w3-green w3-block">Ajouter</button>
                                <button type="submit" name="update_doctor" id="btnUpdateAction"
                                    class="w3-button w3-blue w3-block" style="display:none;">Modifier</button>
                            </div>
                        </form>
                    </div>

                    <div class="w3-padding-small w3-text-grey w3-small w3-margin-top">
                        Liste des médecins
                    </div>

                    <?php
                    // Fetch doctors list for sidebar
                    $stmtDoc = $conn->prepare("SELECT doctor_id, first_name, last_name, specialty, description FROM DOCTOR ORDER BY last_name");
                    $stmtDoc->execute();
                    $sidebarDoctors = $stmtDoc->fetchAll();

                    foreach ($sidebarDoctors as $doc) {
                        ?>
                        <div class="w3-card w3-white w3-margin-bottom w3-padding-small w3-display-container">
                            <div>
                                <strong>Dr. <?php echo htmlspecialchars($doc['last_name']); ?></strong>
                                <div class="w3-small w3-text-grey"><?php echo htmlspecialchars($doc['specialty']); ?></div>
                            </div>

                            <div class="w3-display-topright">
                                <!-- Edit -->
                                <button class="w3-button w3-small w3-text-blue"
                                    onclick='editDoctor(<?php echo json_encode($doc); ?>)'>
                                    <i class="fa fa-pencil"></i>
                                </button>
                                <!-- Delete -->
                                <form method="POST" action="/dashboard" style="display:inline;"
                                    onsubmit="return confirm('Supprimer ce médecin ?');">
                                    <input type="hidden" name="delete_doctor" value="1">
                                    <input type="hidden" name="doctor_id" value="<?php echo $doc['doctor_id']; ?>">
                                    <button class="w3-button w3-small w3-text-red"><i class="fa fa-trash"></i></button>
                                </form>
                            </div>

                            <div class="w3-small w3-text-grey"
                                style="margin-top:5px; border-top:1px solid #eee; padding-top:4px; cursor:pointer;"
                                onclick="filterAppointments(<?php echo $doc['doctor_id']; ?>)">
                                <i class="fa fa-eye"></i> Voir planning
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div id="sidebar-overlay"
            onclick="document.getElementById('adminSidebar').classList.remove('open'); document.getElementById('sidebar-overlay').style.display='none';"
            style="display:none;"></div>

        <script>
            // Admin Sidebar Logic
            function openAdminSidebar() {
                document.getElementById('adminSidebar').classList.add('open');
                document.getElementById('sidebar-overlay').style.display = 'block';
            }

            // Filter logic
            function filterAppointments(doctorId) {
                window.location.href = '/dashboard?view_doctor=' + doctorId;
            }

            function toggleAddForm() {
                var form = document.getElementById('doctorForm');
                // Reset form for "Add"
                document.getElementById('formTitle').innerText = 'Nouveau Médecin';
                document.getElementById('btnRunAction').style.display = 'block';
                document.getElementById('btnUpdateAction').style.display = 'none';
                document.getElementById('form_doctor_id').value = '';
                document.getElementById('form_first_name').value = '';
                document.getElementById('form_last_name').value = '';
                document.getElementById('form_specialty').value = '';
                document.getElementById('form_description').value = '';

                if (form.style.display === 'none') {
                    form.style.display = 'block';
                } else {
                    form.style.display = 'none';
                }
            }

            function editDoctor(doc) {
                var form = document.getElementById('doctorForm');
                form.style.display = 'block';
                document.getElementById('formTitle').innerText = 'Modifier Médecin';
                document.getElementById('btnRunAction').style.display = 'none';
                document.getElementById('btnUpdateAction').style.display = 'block';

                document.getElementById('form_doctor_id').value = doc.doctor_id;
                document.getElementById('form_first_name').value = doc.first_name;
                document.getElementById('form_last_name').value = doc.last_name;
                document.getElementById('form_specialty').value = doc.specialty;
                document.getElementById('form_description').value = doc.description || '';
            }
        </script>
    <?php } ?>

    <?php include '../frontend/partials/footer.php'; ?>

</body>

</html>