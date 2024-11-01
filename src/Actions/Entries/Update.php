<?php

namespace TotalSurvey\Actions\Entries;
! defined( 'ABSPATH' ) && exit();


use TotalSurvey\Filters\Entries\EntryCreatedResponseFilter;
use TotalSurvey\Models\Entry;
use TotalSurvey\Models\Survey;
use TotalSurvey\Tasks\Entries\ExtractEntryData;
use TotalSurvey\Tasks\Entries\UpdateEntry;
use TotalSurvey\Tasks\Utils\GetIP;
use TotalSurvey\Tasks\Utils\GetUserAgent;
use TotalSurveyVendors\TotalSuite\Foundation\Action;
use TotalSurveyVendors\TotalSuite\Foundation\Exceptions\Exception;
use TotalSurveyVendors\TotalSuite\Foundation\Exceptions\ValidationException;
use TotalSurveyVendors\TotalSuite\Foundation\Http\Response;
use TotalSurveyVendors\TotalSuite\Foundation\Support\Arrays;

class Update extends Action {
	/**
	 * @return Response
	 * @throws Exception
	 */
	protected function execute( $entryUid ): Response {
		$data          = $this->request->getParsedBody();
		$data['ip']    = GetIP::invoke();
		$data['agent'] = GetUserAgent::invoke();
		$files         = $this->request->getUploadedFiles();

		$surveyUid = Arrays::pull( $data, 'survey_uid', '' );
		$survey    = Survey::byUidAndActive( $surveyUid );
		$entryUid  = Arrays::pull( $data, 'entry_uid', '' );
		$entry     = Entry::byUid( $entryUid );
		$editToken = Arrays::pull( $data, 'edit_token', '' );

		if ( ! $survey->canEditEntry() || $entry->survey_uid !== $survey->uid || $editToken !== $entry->data->meta['edit_token'] ) {
			ValidationException::throw( esc_html__( 'You are not allowed to edit this entry.', 'totalsurvey' ) );
		}

		$entryData = ExtractEntryData::invoke( $survey, $data, $files );

		$entry = UpdateEntry::invoke( $survey, $entry, $entryData );

		return EntryCreatedResponseFilter::apply( $entry->withSurvey( $survey )->toPublic( $survey )->toJsonResponse(), $survey, $entry );
	}

	public function authorize(): bool {
		return true;
	}

	/**
	 * @return array
	 */
	public function getParams(): array {
		return [
			'entryUid' => [
				'expression'        => '(?<entryUid>([\w-]+))',
				'sanitize_callback' => static function ( $uid ) {
					return (string) $uid;
				},
			],
		];
	}
}
