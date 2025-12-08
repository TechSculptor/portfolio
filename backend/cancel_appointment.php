<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit;
}

// Inclure le fichier de connexion à la base de données (db.php)
require_once "config/db.php";

// Vérifier si l'ID du rendez-vous est présent dans la requête GET
if (!isset($_GET['appointment_id'])) {
    // Rediriger vers la page de dashboard si l'ID du rendez-vous est manquant
    header("Location: /dashboard");
    exit;
}

// Récupérer l'ID du rendez-vous à annuler
$appointmentId = $_GET['appointment_id'];

// Vérifier si l'ID du rendez-vous est valide
if (!is_numeric($appointmentId)) {
    // Rediriger vers la page de dashboard si l'ID du rendez-vous est invalide
    header("Location: /dashboard");
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$userId = $_SESSION['user_id'];
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'patient';

// Vérifier si le rendez-vous appartient à l'utilisateur connecté (ou si admin)
if ($userType === 'admin') {
    // Admin can cancel any appointment
    $stmt = $conn->prepare("SELECT * FROM APPOINTMENT WHERE appointment_id = :appt_id");
    $stmt->execute(['appt_id' => $appointmentId]);
} else {
    // Patient can only cancel their own appointments
    $stmt = $conn->prepare("SELECT * FROM APPOINTMENT WHERE appointment_id = :appt_id AND patient_id = :user_id");
    $stmt->execute(['appt_id' => $appointmentId, 'user_id' => $userId]);
}

if ($stmt->rowCount() === 1) {
    // Supprimer le rendez-vous de la base de données
    $sqlDelete = $conn->prepare("DELETE FROM APPOINTMENT WHERE appointment_id = :appt_id");
    $sqlDelete->execute(['appt_id' => $appointmentId]);
}

// Rediriger vers la page de dashboard
header("Location: /dashboard");
exit;
?>