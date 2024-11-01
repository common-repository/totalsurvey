<?php

namespace TotalSurvey\Actions\Entries;
! defined( 'ABSPATH' ) && exit();



use TotalSurvey\Capabilities\UserCanViewEntries;
use TotalSurvey\Capabilities\UserCanViewOthersSurveys;
use TotalSurvey\Exceptions\Entries\EntryNotFound;
use TotalSurvey\Exceptions\Surveys\SurveyNotFound;
use TotalSurvey\Filters\Entries\EntryFilter;
use TotalSurvey\Models\Entry;
use TotalSurvey\Tasks\Entries\GetEntries;
use TotalSurveyVendors\TotalSuite\Foundation\Action;
use TotalSurveyVendors\TotalSuite\Foundation\Exceptions\Exception;
use TotalSurveyVendors\TotalSuite\Foundation\Http\Response;

class Index extends Action
{
    /**
     * @return Response
     * @throws Exception
     */
    public function execute(): Response
    {
        $filters = $this->request->getQueryParams();

        if (empty($filters['survey_uid'])) {
            SurveyNotFound::throw();
        }

        if (!UserCanViewOthersSurveys::checkSurveyUid($filters['survey_uid'])) {
            EntryNotFound::throw();
        }

        return GetEntries::invoke($filters, true)
                         ->map([$this, 'filter'])
                         ->toJsonResponse();
    }

    /**
     * @param  Entry  $entry
     *
     * @return Entry
     */
    public function filter(Entry $entry)
    {
        return EntryFilter::apply($entry->withUser());
    }

    /**
     * @inheritDoc
     */
    public function authorize(): bool
    {
        return UserCanViewEntries::check();
    }
}
