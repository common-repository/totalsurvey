<?php

namespace TotalSurvey\Capabilities;
! defined( 'ABSPATH' ) && exit();


use TotalSurvey\Models\Survey;
use TotalSurveyVendors\TotalSuite\Foundation\WordPress\Capability;

/**
 * Class ViewSurveys
 *
 * @package TotalSurvey\Capabilities
 */
class UserCanViewOthersSurveys extends Capability
{
    const NAME = 'totalsurvey_view_others_surveys';

    public static function checkSurvey(Survey $survey)
    {
        return static::check() || $survey->user_id === get_current_user_id();
    }

    public static function checkSurveyUid($surveyUid)
    {
        $survey = Survey::byUid($surveyUid);

        return static::check() || $survey->user_id === get_current_user_id();
    }
}
