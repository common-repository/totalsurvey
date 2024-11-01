<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>">

    <title><?php wp_title(); ?></title>
    <?php wp_head(); ?>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            border: 0;
            font-family: sans-serif;
        }

        html {
            overflow: auto;
        }

        body::before, body::after {
            display: none !important;
        }

        body {
            background: #eeeeee !important;
        }

        <?php if (isset($embed) && $embed == '1'): ?>
        html {
            margin: 0 !important;
        }
        <?php endif; ?>
    </style>
    <title><?php esc_html_e('Survey insights', 'totalsurvey') ?></title>

</head>
<body>

<main class="totalsurvey-content">
    <?php echo wp_kses($content, \TotalSurvey\Tasks\Utils\GetAllowedSurveyTags::invoke()); ?>
</main>

<?php
! defined( 'ABSPATH' ) && exit();
 wp_footer(); ?>
</body>
</html>
