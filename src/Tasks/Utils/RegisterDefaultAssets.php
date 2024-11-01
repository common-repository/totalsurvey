<?php

namespace TotalSurvey\Tasks\Utils;
! defined( 'ABSPATH' ) && exit();


use TotalSurvey\Plugin;
use TotalSurveyVendors\TotalSuite\Foundation\Task;

/**
 * Class RegisterDefaultAssets
 *
 * @package TotalSurvey\Tasks
 * @method static array invoke()
 * @method static array invokeWithFallback(array $fallback)
 */
class RegisterDefaultAssets extends Task
{
    /**
     * @inheritDoc
     */
    protected function validate()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function execute()
    {
        $baseUrl          = Plugin::env('url.base');
        $min              = Plugin::env()->isDebug() ? '' : '.min';
        $version          = Plugin::env('version');

        wp_register_script(
            'chart-js',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.1.1/chart.umd.min.js',
            [],
            $version,
            false
        );


        // Enqueue vendors
        wp_register_script(
            'totalsurvey-vue-js',
            "{$baseUrl}/assets/js/vue{$min}.js",
            ['jquery'],
            $version
        );

        wp_register_style('totalsurvey-loading', $baseUrl."/assets/frontend/loading.css", [], $version);
    }
}
