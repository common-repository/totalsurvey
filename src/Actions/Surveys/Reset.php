<?php

namespace TotalSurvey\Actions\Surveys;
! defined( 'ABSPATH' ) && exit();


use TotalSurvey\Capabilities\UserCanDeleteEntries;
use TotalSurvey\Capabilities\UserCanDeleteOthersEntries;
use TotalSurvey\Exceptions\Surveys\SurveyNotFound;
use TotalSurvey\Tasks\Surveys\ResetSurvey;
use TotalSurveyVendors\TotalSuite\Foundation\Action;
use TotalSurveyVendors\TotalSuite\Foundation\Exceptions\Exception;
use TotalSurveyVendors\TotalSuite\Foundation\Http\Response;

/**
 * Class Reset
 *
 * @package TotalSurvey\Actions\Surveys
 */
class Reset extends Action
{
    /**
     * @param $surveyUid
     *
     * @return Response
     * @throws Exception
     */
    public function execute($surveyUid): Response
    {
        if (!UserCanDeleteOthersEntries::checkSurveyUid($surveyUid)) {
            SurveyNotFound::throw();
        }

        return ResetSurvey::invoke($surveyUid)
                          ->toJsonResponse();
    }

    /**
     * @inheritDoc
     */
    public function authorize(): bool
    {
        return UserCanDeleteEntries::check();
    }

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
