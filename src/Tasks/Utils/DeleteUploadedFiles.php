<?php

namespace TotalSurvey\Tasks\Utils;
! defined( 'ABSPATH' ) && exit();


use TotalSurvey\Plugin;
use TotalSurveyVendors\TotalSuite\Foundation\Task;

class DeleteUploadedFiles extends Task
{
    /**
     * @var string
     */
    protected $dir;

    /**
     * DeleteUploadedFiles constructor.
     *
     * @param  string  $dir
     */
    public function __construct(string $dir = '')
    {
        $this->dir = $dir;
    }


    protected function validate()
    {
        return true;
    }

    protected function execute()
    {
        $root = Plugin::env('path.userUploads');

        if (empty($this->dir)) {
            $this->dir = basename($root);
            $root      = dirname($root);
        }

        /**
         * @var $wp_filesystem \WP_Filesystem_Direct
         */
        global $wp_filesystem;

        require_once ABSPATH.'wp-admin/includes/file.php';
        WP_Filesystem();

        if ($wp_filesystem->is_dir($this->dir)) {
            return $wp_filesystem->rmdir($this->dir, true);
        }

        return false;
    }
}
