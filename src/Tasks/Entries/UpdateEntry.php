<?php

namespace TotalSurvey\Tasks\Entries;
! defined( 'ABSPATH' ) && exit();



use TotalSurvey\Events\Entries\OnEntryReceived;
use TotalSurvey\Events\Entries\OnPostValidateEntry;
use TotalSurvey\Filters\Entries\BeforeEntrySaveFilter;
use TotalSurvey\Models\Entry;
use TotalSurvey\Models\Survey;
use TotalSurveyVendors\TotalSuite\Foundation\Exceptions\DatabaseException;
use TotalSurveyVendors\TotalSuite\Foundation\Exceptions\Exception;
use TotalSurveyVendors\TotalSuite\Foundation\Support\Collection;
use TotalSurveyVendors\TotalSuite\Foundation\Task;

/**
 * Class CreateEntry
 *
 * @package TotalSurvey\Tasks\Entries
 * @method static Entry invoke( Survey $survey, Entry $entry, Collection $data )
 * @method static Entry invokeWithFallback( $fallback, Survey $survey, Entry $entry, Collection $data )
 */
class UpdateEntry extends Task {
	/**
	 * @var Collection
	 */
	protected $data;

	/**
	 * @var Survey
	 */
	protected $survey;

	/**
	 * @var Entry
	 */
	protected $entry;

	/**
	 * CreateEntry constructor.
	 *
	 * @param Survey $survey
	 * @param Collection $data
	 *
	 */
	public function __construct( Survey $survey, Entry $entry, Collection $data ) {
		$this->survey = $survey;
		$this->entry  = $entry;
		$this->data   = $data;
		$this->survey->setAttribute( 'context', 'edit' );
	}

	/**
	 * @return bool|mixed|void
	 */
	protected function validate() {
		OnPostValidateEntry::emit( $this->survey, $this->data );

		return true;
	}

	/**
	 * @return Entry
	 * @throws Exception
	 * @throws DatabaseException
	 * @throws \Exception
	 */
	protected function execute() {
		$this->entry->fill( $this->data->toArray() );
		$this->entry->ip                       = esc_html( $this->data->get( 'ip' ) );
		$this->entry->agent                    = esc_html( $this->data->get( 'agent' ) );
		$this->entry->status                   = Entry::STATUS_OPEN;
		$this->entry->data                     = TransformEntryDataToModels::invoke( $this->survey, $this->entry, $this->data );
		$this->entry->data->meta['language']   = esc_html( $this->data->get( 'language' ) );
		$this->entry->data->meta['edit_token'] = wp_generate_uuid4();
		$this->entry->data->meta['updated_at'] = wp_date( 'Y-m-d H:i:s' );
		$this->entry                           = BeforeEntrySaveFilter::apply( $this->entry, $this->survey, $this->data );

		Exception::throwUnless( $this->entry->save(), esc_html__( 'Could not save the entry', 'totalsurvey' ) );

		OnEntryReceived::emit( $this->survey, $this->entry );

		return $this->entry;
	}
}
