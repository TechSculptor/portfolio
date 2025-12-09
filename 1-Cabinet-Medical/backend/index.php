<?php
require_once 'helpers/TranslationHelper.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>
<!DOCTYPE html>
<html lang="<?php echo getLang(); ?>">

<head>
  <title><?php echo __('home_title'); ?> - Cabinet Médical</title>
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
    <h1 class="w3-margin w3-jumbo"><?php echo __('home_hero_title'); ?></h1>
    <p class="w3-xlarge"><?php echo __('home_hero_subtitle'); ?></p>
    <?php if (!isset($_SESSION['user_id'])): ?>
      <a href="<?php echo _route('login'); ?>"
        class="w3-button w3-black w3-padding-large w3-large w3-margin-top"><?php echo __('home_hero_cta'); ?></a>
    <?php else: ?>
      <a href="<?php echo _route('dashboard'); ?>"
        class="w3-button w3-black w3-padding-large w3-large w3-margin-top"><?php echo __('nav_dashboard'); ?></a>
    <?php endif; ?>
  </header>

  <!-- First Grid: Why Choose Us -->
  <div class="w3-row-padding w3-padding-64 w3-container">
    <div class="w3-content">
      <div class="w3-twothird">
        <h1><?php echo __('home_section1_title'); ?></h1>
        <h5 class="w3-padding-32"><?php echo __('home_section1_subtitle'); ?></h5>
        <p class="w3-text-grey"><?php echo __('home_section1_text'); ?></p>
      </div>
      <div class="w3-third w3-center">
        <img src="/frontend/img/img1.png" alt="Cabinet Médical" style="width:100%; max-width:300px; border-radius:8px;">
      </div>
    </div>
  </div>

  <!-- Second Grid: Our Team -->
  <div class="w3-row-padding w3-light-grey w3-padding-64 w3-container">
    <div class="w3-content">
      <div class="w3-third w3-center">
        <img src="/frontend/img/img2.png" alt="Équipe Médicale" style="width:100%; max-width:300px; border-radius:8px;">
      </div>
      <div class="w3-twothird">
        <h1><?php echo __('home_section2_title'); ?></h1>
        <h5 class="w3-padding-32"><?php echo __('home_section2_subtitle'); ?></h5>
        <p class="w3-text-grey"><?php echo __('home_section2_text'); ?></p>
      </div>
    </div>
  </div>

  <!-- Quote Section -->
  <div class="w3-container w3-black w3-center w3-opacity w3-padding-64">
    <h1 class="w3-margin w3-xlarge"><?php echo __('home_quote'); ?></h1>
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