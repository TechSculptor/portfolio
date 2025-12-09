<?php
// fetch_availability.php - AJAX endpoint for sidebar content
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "config/db.php";
require_once "helpers/TranslationHelper.php";

// 1. Fetch Doctors for dropdown
$stmtDoctors = $conn->prepare("SELECT doctor_id, first_name, last_name, specialty FROM DOCTOR ORDER BY last_name, first_name");
$stmtDoctors->execute();
$doctors = $stmtDoctors->fetchAll();

// 2. Get Parameters
$selectedDoctorId = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : '';
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d', strtotime('+1 day'));

// 3. Logic to get slots (if doctor selected)
$selectedDoctor = null;
$availableSlots = [];
$bookedSlots = [];

if ($selectedDoctorId && $selectedDate) {
    // Get Doctor Info
    $stmt = $conn->prepare("SELECT * FROM DOCTOR WHERE doctor_id = :doctor_id");
    $stmt->execute(['doctor_id' => $selectedDoctorId]);
    $selectedDoctor = $stmt->fetch();

    if ($selectedDoctor) {
        // Get Booked Slots
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
            $bookedSlots[] = substr($row['appointment_time'], 0, 5);
        }

        // Generate All Slots
        $allSlots = [];
        // Morning: 9h00 - 11h30
        for ($hour = 9; $hour < 12; $hour++) {
            $allSlots[] = sprintf("%02d:00", $hour);
            $allSlots[] = sprintf("%02d:30", $hour);
        }
        // Afternoon: 13h00 - 16h00
        for ($hour = 13; $hour <= 15; $hour++) {
            $allSlots[] = sprintf("%02d:00", $hour);
            if ($hour < 16) {
                $allSlots[] = sprintf("%02d:30", $hour);
            }
        }
        $allSlots[] = "16:00";

        // Determine Availability
        foreach ($allSlots as $slot) {
            $availableSlots[$slot] = !in_array($slot, $bookedSlots);
        }
    }
}
?>

<!-- Output HTML Content (No Head/Body) -->
<div class="availability-form-container">
    <form id="availabilityForm" onsubmit="submitAvailabilityForm(); return false;">
        <label for="sidebar_doctor_id"><?php echo __('book_select_doctor'); ?>:</label>
        <select id="sidebar_doctor_id" name="doctor_id" class="w3-select w3-border" onchange="submitAvailabilityForm()"
            required>
            <option value=""><?php echo __('book_select_default'); ?></option>
            <?php foreach ($doctors as $doctor) { ?>
                <option value="<?php echo $doctor['doctor_id']; ?>" <?php echo ($selectedDoctorId == $doctor['doctor_id']) ? 'selected' : ''; ?>>
                    Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                    (<?php echo htmlspecialchars($doctor['specialty']); ?>)
                </option>
            <?php } ?>
        </select>

        <br><br>

        <label for="sidebar_date"><?php echo __('book_date'); ?>:</label>
        <input type="date" id="sidebar_date" name="date" class="w3-input w3-border"
            value="<?php echo htmlspecialchars($selectedDate); ?>" min="<?php echo date('Y-m-d'); ?>"
            onchange="submitAvailabilityForm()" required>
    </form>
</div>

<hr>

<div id="slots-results">
    <?php if ($selectedDoctor && $selectedDate) { ?>
        <div class="doctor-summary w3-pale-blue w3-padding w3-round">
            <strong>Dr.
                <?php echo htmlspecialchars($selectedDoctor['first_name'] . ' ' . $selectedDoctor['last_name']); ?></strong><br>
            <small><?php echo htmlspecialchars($selectedDoctor['specialty']); ?></small><br>
            <small><?php echo __('book_date'); ?>: <?php echo date('d/m/Y', strtotime($selectedDate)); ?></small>
        </div>

        <div class="slots-container w3-margin-top">

            <div class="slot-group">
                <h5><?php echo __('avail_morning'); ?></h5>
                <div class="slots-flex">
                    <?php
                    $hasMorning = false;
                    foreach ($availableSlots as $time => $available) {
                        $hour = intval(substr($time, 0, 2));
                        if ($hour < 12) {
                            $hasMorning = true;
                            $class = $available ? 'w3-green w3-hover-green' : 'w3-red w3-opacity';
                            $disabled = $available ? '' : 'disabled style="cursor:not-allowed"';
                            $msg = "Vous avez sélectionné le créneau de $time.\\n\\nVeuillez confirmer pour accéder au formulaire et saisir vos informations.";
                            $route = _route('book_appointment');
                            $onclick = $available ? "onclick=\"if(confirm('$msg')) window.location.href='$route?doctor_id=$selectedDoctorId&date=$selectedDate&time=$time'\"" : "";

                            echo "<button type='button' class='w3-button w3-round w3-small w3-margin-small $class' $disabled $onclick>$time</button> ";
                        }
                    }
                    if (!$hasMorning)
                        echo "<p>" . __('dashboard_no_appts') . "</p>";
                    ?>
                </div>
            </div>

            <div class="slot-group w3-margin-top">
                <h5><?php echo __('avail_afternoon'); ?></h5>
                <div class="slots-flex">
                    <?php
                    $hasAfternoon = false;
                    foreach ($availableSlots as $time => $available) {
                        $hour = intval(substr($time, 0, 2));
                        if ($hour >= 12) {
                            $hasAfternoon = true;
                            $class = $available ? 'w3-green w3-hover-green' : 'w3-red w3-opacity';
                            $disabled = $available ? '' : 'disabled style="cursor:not-allowed"';
                            $msg = "Vous avez sélectionné le créneau de $time.\\n\\nVeuillez confirmer pour accéder au formulaire et saisir vos informations.";
                            $route = _route('book_appointment');
                            $onclick = $available ? "onclick=\"if(confirm('$msg')) window.location.href='$route?doctor_id=$selectedDoctorId&date=$selectedDate&time=$time'\"" : "";

                            echo "<button type='button' class='w3-button w3-round w3-small w3-margin-small $class' $disabled $onclick>$time</button> ";
                        }
                    }
                    if (!$hasAfternoon)
                        echo "<p>" . __('dashboard_no_appts') . "</p>";
                    ?>
                </div>
            </div>
        </div>

    <?php } else { ?>
        <div class="w3-panel w3-yellow w3-padding">
            <p><?php echo __('avail_desc'); ?></p>
        </div>
    <?php } ?>
</div>