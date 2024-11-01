<?php

namespace TotalSurvey\Models;
! defined( 'ABSPATH' ) && exit();


use TotalSurvey\Exceptions\Entries\EntryNotFound;
use TotalSurvey\Filters\Entries\EntryToExportDataFilter;
use TotalSurvey\Filters\Entries\PublicEntryFilter;
use TotalSurvey\Plugin;
use TotalSurvey\Tasks\Entries\ConvertEntryToHTMLTable;
use TotalSurvey\Tasks\Entries\ConvertEntryToMap;
use TotalSurvey\Tasks\Entries\RenderEntry;
use TotalSurveyVendors\TotalSuite\Foundation\Database\TableModel;
use TotalSurveyVendors\TotalSuite\Foundation\Exceptions\Exception;
use TotalSurveyVendors\TotalSuite\Foundation\Exceptions\ModuleException;
use TotalSurveyVendors\TotalSuite\Foundation\WordPress\Modules\Manager;
use WP_User;

/**
 * Class Entry
 *
 * @property int $id
 * @property int $survey_uid
 * @property int $user_id
 * @property string uid
 * @property string $ip
 * @property string $agent
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property EntryData $data
 * @property string $status
 *
 * @package TotalSurvey\Models
 */
class Entry extends TableModel {
	const STATUS_OPEN = 'open';

	/**
	 * @var string
	 */
	protected $table = 'totalsurvey_entries';

	/**
	 * @var array
	 */
	protected $types = [
		'data' => 'data',
	];

	/**
	 * @var array
	 */
	protected $fillable = [ 'id', 'survey_uid', 'user_id', 'ip', 'agent', 'data', 'score', 'total' ];

	protected $survey = null;

	/**
	 * @param $data
	 *
	 * @return EntryData
	 */
	public function castToData( $data ) {
		$data = is_string( $data ) ? json_decode( $data, true ) : $data;

		return EntryData::from( $this, $data );
	}

	/**
	 * @param string $uid
	 *
	 * @return Entry
	 * @throws Exception
	 */
	public static function byUid( string $uid ): Entry {
		$entry = static::query()
		               ->where( 'uid', $uid )
		               ->first();

		EntryNotFound::throwUnless( $entry instanceof self, __( 'Entry not found', 'totalsurvey' ) );

		return $entry;
	}

	/**
	 * @return $this
	 */
	public function withUser() {
		$user = $this->getUser();

		if ( $user instanceof WP_User ) {
			$user = [
				'id'     => $user->ID,
				'name'   => esc_html( $user->display_name ),
				'email'  => esc_html( $user->user_email ),
				'avatar' => get_avatar_url( $user->user_email ),
			];
		}

		$this->setAttribute( 'user', $user ?? null );

		return $this;
	}

	/**
	 * @return $this
	 */
	public function withSurvey( Survey $survey = null ) {
		if ( $survey ) {
			$this->survey = $survey;
		} else {
			try {
				$this->survey = $this->survey();
			} catch ( Exception $exception ) {
			}
		}

		return $this;
	}

	/**
	 * Get survey.
	 *
	 * @return Survey
	 * @throws Exception
	 */
	public function survey(): Survey {
		return Survey::byUid( $this->survey_uid );
	}

	/**
	 * Get user.
	 *
	 * @return bool|WP_User
	 */
	public function getUser() {
		return get_user_by( 'ID', $this->user_id );
	}

	/**
	 * @return Entry
	 */
	public function toPublic() {
		$clone = clone $this;
		$clone->setRawAttributes( [] );
		$clone->setAttribute( 'uid', $this->uid );
		$clone->setAttribute( 'url', $this->getUrl() );

		$clone->setAttribute(
			'canEditEntry',
			$clone->survey && $clone->survey->getAttribute( 'settings.can_edit_entry.enabled', false )
		);
		$clone->setAttribute( 'edit_token', $this->data->meta['edit_token'] ?? '' );

		$formData = [];
		foreach ( $this->data->sections as $section ) {
			foreach ( $section->blocks as $block ) {
				$formData = array_merge($formData, $block->blockType::getFormDataFromRawValue($block, $this));
			}
		}
		$clone->setAttribute( 'form', $formData );

		return PublicEntryFilter::apply( $clone, $this );
	}

	/**
	 * @param $format
	 *
	 * @return array
	 */
	public function toExport( $format ): array {
		$entry = $this
			->withSurvey()
			->withUser()
			->toArray();

		$data['id']         = (int) $entry['id'];
		$data['survey']     = empty( $entry['survey']['name'] ) ? 'Survey #' . (int) $entry['survey']['id'] : $entry['survey']['name'];
		$data['user']       = $entry['user'] ?? [];
		$data['created_at'] = $entry['created_at'];
		$data['ip']         = $entry['ip'];
		$data['agent']      = $entry['agent'];
		$data['data']       = $entry['data']['sections'];

		return EntryToExportDataFilter::apply( $data, $entry, $format );
	}

	/**
	 * @throws Exception
	 * @throws ModuleException
	 */
	public function render() {
		$template = Manager::instance()->loadTemplate( $this->survey()->getTemplate() );

		return RenderEntry::invoke( $template, $this );
	}

	/**
	 * @param array $arguments
	 *
	 * @return string
	 */
	public function getUrl( $arguments = [] ) {
		$baseUrl                = site_url();
		$arguments['entry_uid'] = $this->uid;

		if ( Plugin::env()->isPrettyPermalinks() ) {
			$base    = 'entry';
			$baseUrl = site_url( "{$base}/{$this->uid}/" );
			unset( $arguments['entry_uid'] );
		}

		return add_query_arg( $arguments, $baseUrl );
	}

	/**
	 * @return array
	 */
	public function toArray(): array {
		$entry        = parent::toArray();
		$entry['url'] = $this->getUrl();


		return $entry;
	}

	public function toMap() {
		return ConvertEntryToMap::invoke( $this );
	}

	public function toHtml() {
		return ConvertEntryToHTMLTable::invoke( $this );
	}
}
