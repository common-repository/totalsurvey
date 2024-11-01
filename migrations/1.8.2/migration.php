<?php
! defined( 'ABSPATH' ) && exit();


use TotalSurveyVendors\League\Container\Container;
use TotalSurveyVendors\TotalSuite\Foundation\Filesystem;
use TotalSurveyVendors\TotalSuite\Foundation\Migration\Migration;
use TotalSurveyVendors\TotalSuite\Foundation\WordPress\Database\WPConnection;
use TotalSurveyVendors\TotalSuite\Foundation\WordPress\Options;

/**
 * @class Anonymous migration class
 *
 * @param  Container  $container
 * @param           $path
 * @param           $previousVersion
 *
 * @return Migration
 */
return function (Container $container, $path, $previousVersion) {
    return new class($container, $path, $previousVersion) extends Migration {

        protected $version = '1.8.2';

        /**
         * @var Filesystem
         */
        protected $fs;

        /**
         * @var WPConnection
         */
        protected $db;

        /**
         * @var Options
         */
        protected $options;


        public function applyNewCapabilities()
        {
            \TotalSurvey\Tasks\Utils\DetachDefaultCapabilitiesFromDefaultRoles::invoke();
            \TotalSurvey\Tasks\Utils\AttachDefaultCapabilitiesToDefaultRoles::invoke();
        }
    };
};
