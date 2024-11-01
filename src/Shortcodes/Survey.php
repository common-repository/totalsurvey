<?php

namespace TotalSurvey\Shortcodes;
! defined( 'ABSPATH' ) && exit();


use Exception;
use TotalSurvey\Models\Survey as SurveyModel;
use TotalSurvey\Plugin;
use TotalSurvey\Tasks\Surveys\DisplaySurvey;
use TotalSurveyVendors\TotalSuite\Foundation\WordPress\Shortcode;

/**
 * Class Survey
 *
 * @package TotalSurvey\Shortcodes
 */
class Survey extends Shortcode
{
    /**
     * Survey constructor.
     */
    public function __construct()
    {
        parent::__construct('totalsurvey');
    }

    /**
     * @return string
     */
    public function render(): string
    {
        try {
            $id     = (int) $this->getAttribute('id');
            $locale = (string) $this->getAttribute('language', $this->getAttribute('locale'));

            $survey = SurveyModel::byIdAndActiveForRendering($id);
            $survey->applyLocale($locale);

            return DisplaySurvey::invoke($survey);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
