<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'helpers/TranslationHelper.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: " . _route('login'));
    exit;
}

// Connexion à la base de données
require_once "config/db.php";

// Traitement des actions CRUD
$message = '';
$messageType = '';

// Ajouter un médecin
if (isset($_POST['add_doctor'])) {
    $stmt = $conn->prepare("
        INSERT INTO DOCTOR (first_name, last_name, specialty, description) 
        VALUES (:first_name, :last_name, :specialty, :description)
    ");
    try {
        $stmt->execute([
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'specialty' => $_POST['specialty'],
            'description' => $_POST['description']
        ]);
        $message = "Médecin ajouté avec succès!";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Erreur lors de l'ajout du médecin.";
        $messageType = "error";
    }
}

// Supprimer un médecin
if (isset($_POST['delete_doctor'])) {
    $stmt = $conn->prepare("DELETE FROM DOCTOR WHERE doctor_id = :doctor_id");
    try {
        $stmt->execute(['doctor_id' => $_POST['doctor_id']]);
        $message = "Médecin supprimé avec succès!";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Erreur: Ce médecin a des rendez-vous programmés.";
        $messageType = "error";
    }
}

// Modifier un médecin
if (isset($_POST['update_doctor'])) {
    $stmt = $conn->prepare("
        UPDATE DOCTOR 
        SET first_name = :first_name, 
            last_name = :last_name, 
            specialty = :specialty, 
            description = :description 
        WHERE doctor_id = :doctor_id
    ");
    try {
        $stmt->execute([
            'doctor_id' => $_POST['doctor_id'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'specialty' => $_POST['specialty'],
            'description' => $_POST['description']
        ]);
        $message = "Médecin modifié avec succès!";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Erreur lors de la modification.";
        $messageType = "error";
    }
}

// Récupérer tous les médecins
$stmt = $conn->prepare("SELECT * FROM DOCTOR ORDER BY last_name, first_name");
$stmt->execute();
$doctors = $stmt->fetchAll();

// Mode édition
$editDoctor = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM DOCTOR WHERE doctor_id = :doctor_id");
    $stmt->execute(['doctor_id' => $_GET['edit']]);
    $editDoctor = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Gestion des Médecins - Admin</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="/frontend/css/styles.css">
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

        .doctor-table {
            width: 100%;
            margin-top: 20px;
        }

        .doctor-table th,
        .doctor-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .doctor-table th {
            background-color: #f2f2f2;
        }

        .form-section {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
    </style>
</head>

<body>

    <?php include '../frontend/partials/navbar.php'; ?>

    <br><br><br><br><br><br>

    <div class="w3-container">
        <h2><?php echo __('admin_doc_title'); ?></h2>

        <?php if ($message) { ?>
            <div class="w3-panel w3-<?php echo ($messageType === 'success') ? 'pale-green' : 'pale-red'; ?> w3-border">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php } ?>

        <!-- Formulaire d'ajout/édition -->
        <div class="form-section">
            <h3><?php echo $editDoctor ? __('admin_doc_edit') : __('admin_doc_add'); ?></h3>
            <form method="POST" action="<?php echo _route('admin_doctors'); ?>">
                <?php if ($editDoctor) { ?>
                    <input type="hidden" name="doctor_id" value="<?php echo $editDoctor['doctor_id']; ?>">
                <?php } ?>

                <label for="first_name"><?php echo __('admin_sf_firstname'); ?></label>
                <input type="text" name="first_name" required
                    value="<?php echo $editDoctor ? htmlspecialchars($editDoctor['first_name']) : ''; ?>"><br><br>

                <label for="last_name"><?php echo __('admin_sf_lastname'); ?></label>
                <input type="text" name="last_name" required
                    value="<?php echo $editDoctor ? htmlspecialchars($editDoctor['last_name']) : ''; ?>"><br><br>

                <label for="specialty"><?php echo __('admin_sf_specialty'); ?></label>
                <input type="text" name="specialty" required
                    value="<?php echo $editDoctor ? htmlspecialchars($editDoctor['specialty']) : ''; ?>"><br><br>

                <label for="description"><?php echo __('admin_sf_desc'); ?></label>
                <textarea name="description" rows="3"
                    cols="50"><?php echo $editDoctor ? htmlspecialchars($editDoctor['description']) : ''; ?></textarea><br><br>

                <?php if ($editDoctor) { ?>
                    <input type="submit" name="update_doctor" value="<?php echo __('admin_btn_update'); ?>"
                        class="w3-button w3-blue">
                    <a href="admin_doctors.php" class="w3-button w3-gray"><?php echo __('admin_btn_cancel'); ?></a>
                <?php } else { ?>
                    <input type="submit" name="add_doctor" value="<?php echo __('admin_btn_add'); ?>"
                        class="w3-button w3-green">
                <?php } ?>
            </form>
        </div>

        <!-- Liste des médecins -->
        <h3><?php echo __('admin_list'); ?></h3>
        <table class="doctor-table">
            <thead>
                <tr>
                    <th><?php echo __('admin_th_id'); ?></th>
                    <th><?php echo __('admin_th_name'); ?></th>
                    <th><?php echo __('admin_th_specialty'); ?></th>
                    <th><?php echo __('admin_th_desc'); ?></th>
                    <th><?php echo __('admin_th_actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doctors as $doctor) { ?>
                    <tr>
                        <td><?php echo $doctor['doctor_id']; ?></td>
                        <td>Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($doctor['specialty']); ?></td>
                        <td><?php echo htmlspecialchars(substr($doctor['description'], 0, 50)) . '...'; ?></td>
                        <td>
                            <a href="<?php echo _route('admin_doctors', ['edit' => $doctor['doctor_id']]); ?>"
                                class="w3-button w3-small w3-blue"><?php echo __('admin_btn_modify'); ?></a>
                            <form method="POST" action="<?php echo _route('admin_doctors'); ?>" style="display: inline;"
                                onsubmit="return confirm('<?php echo __('admin_confirm_delete'); ?>');">
                                <input type="hidden" name="doctor_id" value="<?php echo $doctor['doctor_id']; ?>">
                                <input type="submit" name="delete_doctor" value="<?php echo __('admin_btn_delete'); ?>"
                                    class="w3-button w3-small w3-red">
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <?php if (empty($doctors)) { ?>
            <p class="w3-center">Aucun médecin dans la base de données.</p>
        <?php } ?>

        <br><br>
        <p><a href="dashboard.php" class="w3-button w3-gray"><?php echo __('book_back'); ?></a></p>
    </div>

    <?php include '../frontend/partials/footer.php'; ?>

</body>

</html>