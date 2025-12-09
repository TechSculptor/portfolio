<?php
require_once 'helpers/TranslationHelper.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'fr'; ?>">

<head>
    <title><?php echo __('about_title'); ?> - Cabinet Fictif Médical</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="/frontend/img/favicon.png">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat">
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
            font-family: "Lato", sans-serif
        }

        .w3-bar,
        h1,
        button {
            font-family: "Montserrat", sans-serif
        }
    </style>
</head>

<body>

    <?php include '../frontend/partials/navbar.php'; ?>

    <!-- Header -->
    <header class="w3-container w3-red w3-center" style="padding:128px 16px">
        <h1 class="w3-margin w3-jumbo"><?php echo __('about_title'); ?></h1>
        <p class="w3-xlarge"><?php echo __('about_subtitle'); ?></p>
    </header>

    <!-- First Grid -->
    <div class="equal-height-section w3-white">
        <div class="w3-content">
            <div class="w3-row-padding">
                <div class="w3-twothird w3-container content-flex" style="padding-right: 60px;">
                    <h1><?php echo __('about_section1_title'); ?></h1>
                    <h5 class="w3-padding-32"><?php echo __('about_section1_text'); ?></h5>
                    <p class="w3-text-grey">
                        <?php echo __('about_section2_text'); ?>
                    </p>
                </div>

                <div class="w3-third w3-center w3-container content-flex">
                    <div class="image-card w3-round-large w3-hover-shadow">
                        <img src="/frontend/img/img2.png" alt="Cabinet Medical" class="w3-image w3-round-large"
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
                    <div class="image-card w3-round-large w3-hover-shadow">
                        <img src="/frontend/img/img3.png" alt="Salle d'attente" class="w3-image w3-round-large"
                            style="width:100%; max-width:400px;">
                    </div>
                </div>

                <div class="w3-twothird w3-container content-flex" style="padding-left: 60px;">
                    <h1><?php echo __('about_section2_title'); ?></h1>
                    <h5 class="w3-padding-32"><?php echo __('about_section2_text'); ?></h5>
                    <p class="w3-text-grey">
                        <?php echo __('about_section1_text'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include '../frontend/partials/footer.php'; ?>

</body>

</html>