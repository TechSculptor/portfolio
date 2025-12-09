<?php
require_once 'helpers/TranslationHelper.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Cabinet Fictif Médical</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Les liens des fichiers CSS -->
  <link rel="icon" type="image/png" href="/frontend/img/favicon.png">
  <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="/frontend/css/styles.css">

</head>

<body>

  <!-- Inclure la barre de navigation -->
  <?php include '../frontend/partials/navbar.php'; ?>

  <!-- Header -->
  <header class="w3-container w3-red w3-center" style="padding:128px 16px">
    <div class="w3-content">
      <h1 class="w3-margin w3-jumbo"><?php echo __('home_welcome'); ?></h1>
      <p class="w3-xlarge"><?php echo __('home_subtitle'); ?></p>
      <h2><?php echo __('home_desc'); ?></h2>

      <a href="/login" class="w3-button w3-blue w3-round-large"><?php echo __('home_cta'); ?></a>
    </div>
  </header>

  <!-- First Grid -->
  <div class="equal-height-section w3-white">
    <div class="w3-content">
      <div class="w3-row-padding">
        <div class="w3-twothird w3-container content-flex" style="padding-right: 60px;">
          <h2><?php echo __('home_hours'); ?></h2>
          <h5 class="w3-padding-32"><?php echo __('home_hours_text'); ?></h5>
          <p class="w3-text-grey"><?php echo __('home_desc'); ?></p>
        </div>

        <div class="w3-third w3-center w3-container content-flex">
          <div class="image-card w3-round-large w3-hover-shadow">
            <img src="/frontend/img/img1.png" alt="Medical Care" class="w3-image w3-round-large"
              style="width:100%; max-width:400px;">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Second Grid -->
  <div class="equal-height-section w3-light-grey">
    <div class="w3-content">
      <div class="w3-row-padding">
        <div class="w3-third w3-center w3-container content-flex">
          <i class="fa fa-ambulance w3-text-red" style="font-size:150px; padding: 20px;"></i>
        </div>

        <div class="w3-twothird w3-container content-flex" style="padding-left: 80px;">
          <h1><?php echo __('home_emergency'); ?></h1>
          <h5 class="w3-padding-32"><?php echo __('home_emergency_text'); ?></h5>
          <p class="w3-text-grey"><?php echo __('home_desc'); ?></p>
        </div>
      </div>
    </div>
  </div>

  <div class="w3-container w3-black w3-center w3-opacity w3-padding-64">
    <div class="w3-content">
      <h3 class="w3-margin w3-xlarge">Citation du jour : Vivez votre vie</h3>
    </div>
  </div>

  <!-- Include le pied de page -->
  <?php include '../frontend/partials/footer.php'; ?>

  <script src="/frontend/js/script.js"></script>

</body>

</html>