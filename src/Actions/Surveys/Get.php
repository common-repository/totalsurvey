<?php

namespace TotalSurvey\Actions\Surveys;
! defined( 'ABSPATH' ) && exit();


use TotalSurvey\Capabilities\UserCanViewOthersSurveys;
use TotalSurvey\Capabilities\UserCanViewSurveys;
use TotalSurvey\Exceptions\Surveys\SurveyNotFound;
use TotalSurvey\Tasks\Surveys\GetSurvey;
use TotalSurveyVendors\TotalSuite\Foundation\Action;
use TotalSurveyVendors\TotalSuite\Foundation\Exceptions\Exception;
use TotalSurveyVendors\TotalSuite\Foundation\Http\Response;

/**
 * Class Get
 *
 * @package TotalSurvey\Actions\Surveys
 */
class Get extends Action
{

    /**
     * @param  string  $surveyUid
     *
     * @return Response
     * @throws Exception
     */
    public function execute($surveyUid): Response
    {
        $survey = GetSurvey::invoke($surveyUid);

        if (!UserCanViewOthersSurveys::checkSurvey($survey)) {
            SurveyNotFound::throw();
        }

        return $survey->toJsonResponse();
    }

    /**
     * @inheritDoc
     */
    public function authorize(): bool
    {
        return UserCanViewSurveys::check();
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return [
            'surveyUid' => [
                'expression'        => '(?<surveyUid>([\w-]+))',
                'sanitize_callback' => static function ($surveyUid) {
                    return (string) $surveyUid;
                },
            ],
        ];
    }
}
