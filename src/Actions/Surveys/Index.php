<?php

namespace TotalSurvey\Actions\Surveys;
! defined( 'ABSPATH' ) && exit();


use TotalSurvey\Capabilities\UserCanViewOthersSurveys;
use TotalSurvey\Capabilities\UserCanViewSurveys;
use TotalSurvey\Models\Survey;
use TotalSurvey\Tasks\Surveys\GetSurveys;
use TotalSurveyVendors\TotalSuite\Foundation\Action;
use TotalSurveyVendors\TotalSuite\Foundation\Exceptions\Exception;
use TotalSurveyVendors\TotalSuite\Foundation\Http\Response;

/**
 * Class Index
 *
 * @package TotalSurvey\Actions\Surveys
 */
class Index extends Action
{
    /**
     * @return Response
     * @throws Exception
     */
    public function execute(): Response
    {
        $filters = $this->request->getQueryParams();

        if (!UserCanViewOthersSurveys::check()) {
            $filters['user_id'] = get_current_user_id();
        }

        return GetSurveys::invoke($filters)
                         ->map([$this, 'filter'])
                         ->toJsonResponse();
    }

    /**
     * @param  Survey  $survey
     *
     * @return Survey
     */
    public function filter(Survey $survey)
    {
        return $survey->withStatistics();
    }

    /**
     * @inheritDoc
     */
    public function authorize(): bool
    {
        return UserCanViewSurveys::check();
    }
}
