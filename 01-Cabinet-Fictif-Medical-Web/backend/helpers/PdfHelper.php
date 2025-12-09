<?php
/**
 * PDF Helper
 * Generates PDF documents for appointment summaries
 */

require_once __DIR__ . '/../../vendor/autoload.php';



class PdfHelper
{

    /**
     * Generate appointment confirmation PDF
     * 
     * @param array $data Appointment and patient data
     * @return string PDF content as string
     */
    public function generateAppointmentPDF($data)
    {
        // Create new PDF document
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Cabinet Médical');
        $pdf->SetAuthor('Cabinet Médical');
        $pdf->SetTitle('Confirmation de Rendez-vous');
        $pdf->SetSubject('Rendez-vous Médical');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 12);

        // Generate PDF content
        $html = $this->generatePDFContent($data);

        // Output HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

        // Return PDF as string
        return $pdf->Output('', 'S');
    }

    /**
     * Generate PDF HTML content
     */
    private function generatePDFContent($data)
    {
        $patientName = htmlspecialchars($data['patient_name']);
        $patientEmail = htmlspecialchars($data['patient_email']);
        $doctorName = htmlspecialchars($data['doctor_name']);
        $specialty = htmlspecialchars($data['specialty']);
        $date = date('d/m/Y', strtotime($data['appointment_date']));
        $time = substr($data['appointment_time'], 0, 5);
        $reason = htmlspecialchars($data['reason']);
        $isFirst = $data['is_first_appointment'] ? 'Oui' : 'Non';
        $status = $data['status'] ?? 'pending';

        $statusLabels = [
            'pending' => 'En attente de confirmation',
            'confirmed' => 'Confirmé',
            'cancelled' => 'Annulé'
        ];
        $statusText = $statusLabels[$status] ?? $status;

        $html = <<<HTML
<style>
    .header {
        background-color: #d32f2f;
        color: white;
        padding: 20px;
        text-align: center;
        margin-bottom: 30px;
    }
    .header h1 {
        margin: 0;
        font-size: 24px;
    }
    .header h2 {
        margin: 5px 0 0 0;
        font-size: 18px;
        font-weight: normal;
    }
    .section {
        border: 2px solid #d32f2f;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }
    .section h3 {
        color: #d32f2f;
        margin-top: 0;
        font-size: 16px;
        border-bottom: 2px solid #d32f2f;
        padding-bottom: 5px;
    }
    .info-row {
        margin: 10px 0;
        font-size: 12px;
    }
    .label {
        font-weight: bold;
        color: #333;
        display: inline-block;
        width: 150px;
    }
    .value {
        color: #666;
    }
    .footer {
        margin-top: 30px;
        padding-top: 15px;
        border-top: 1px solid #ccc;
        text-align: center;
        font-size: 10px;
        color: #666;
    }
    .important {
        background-color: #fff3cd;
        border: 1px solid #ffc107;
        padding: 10px;
        margin: 20px 0;
        border-radius: 3px;
    }
</style>

<div class="header">
    <h1>CABINET MÉDICAL</h1>
    <h2>Confirmation de Rendez-vous</h2>
</div>

<div class="section">
    <h3>Informations Patient</h3>
    <div class="info-row">
        <span class="label">Nom complet:</span>
        <span class="value">{$patientName}</span>
    </div>
    <div class="info-row">
        <span class="label">Email:</span>
        <span class="value">{$patientEmail}</span>
    </div>
</div>

<div class="section">
    <h3>Détails du Rendez-vous</h3>
    <div class="info-row">
        <span class="label">Date:</span>
        <span class="value"><strong>{$date}</strong></span>
    </div>
    <div class="info-row">
        <span class="label">Heure:</span>
        <span class="value"><strong>{$time}</strong></span>
    </div>
    <div class="info-row">
        <span class="label">Médecin:</span>
        <span class="value">{$doctorName}</span>
    </div>
    <div class="info-row">
        <span class="label">Spécialité:</span>
        <span class="value">{$specialty}</span>
    </div>
    <div class="info-row">
        <span class="label">Motif:</span>
        <span class="value">{$reason}</span>
    </div>
    <div class="info-row">
        <span class="label">Premier rendez-vous:</span>
        <span class="value">{$isFirst}</span>
    </div>
    <div class="info-row">
        <span class="label">Statut:</span>
        <span class="value"><strong>{$statusText}</strong></span>
    </div>
</div>

<div class="section">
    <h3>Adresse du Cabinet</h3>
    <div class="info-row">
        Cabinet Médical de la Sérénité<br>
        123 Rue de la Santé<br>
        75000 Paris<br>
        Tél: 01 23 45 67 89
    </div>
</div>

<div class="important">
    <strong>Important:</strong> En cas d'empêchement, veuillez annuler votre rendez-vous au moins 24 heures à l'avance 
    via votre espace patient ou en nous contactant directement.
</div>

<div class="footer">
    <p>Ce document a été généré automatiquement le {$this->getCurrentDateTime()}</p>
    <p>© 2025 Cabinet Médical - Tous droits réservés</p>
</div>
HTML;

        return $html;
    }

    /**
     * Get current date and time formatted
     */
    private function getCurrentDateTime()
    {
        return date('d/m/Y à H:i');
    }
}
