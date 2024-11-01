<?php

namespace TotalSurvey\Actions\Surveys;
! defined( 'ABSPATH' ) && exit();


use TotalSurvey\Capabilities\UserCanUpdateOthersSurveys;
use TotalSurvey\Capabilities\UserCanUpdateSurvey;
use TotalSurvey\Exceptions\Surveys\SurveyNotFound;
use TotalSurvey\Tasks\Surveys\EnableSurvey;
use TotalSurveyVendors\TotalSuite\Foundation\Action;
use TotalSurveyVendors\TotalSuite\Foundation\Exceptions\Exception;
use TotalSurveyVendors\TotalSuite\Foundation\Http\Response;

/**
 * Class Enable
 *
 * @package TotalSurvey\Actions\Surveys
 */
class Enable extends Action
{

    /**
     * @param  int  $surveyUid
     *
     * @return Response
     * @throws Exception
     */
    public function execute($surveyUid): Response
    {
        if (!UserCanUpdateOthersSurveys::checkSurveyUid($surveyUid)) {
            SurveyNotFound::throw();
        }

        $enable = (bool) $this->request->getParam('enabled');

        return EnableSurvey::invoke($surveyUid, $enable)
                           ->toJsonResponse();
    }

    /**
     * @inheritDoc
     */
    public function authorize(): bool
    {
        return UserCanUpdateSurvey::check();
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
