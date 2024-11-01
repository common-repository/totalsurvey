<?php

namespace TotalSurvey\Models;
! defined( 'ABSPATH' ) && exit();


use TotalSurvey\Capabilities\UserCanUpdateSurvey;
use TotalSurvey\Exceptions\Surveys\SurveyNotFound;
use TotalSurvey\Exceptions\Surveys\SurveySectionNotFound;
use TotalSurvey\Filters\Blocks\BlockMentionFilter;
use TotalSurvey\Filters\Surveys\SurveyLinkFilter;
use TotalSurvey\Filters\Surveys\SurveySettingsFilter;
use TotalSurvey\Models\Concerns\SurveySettings;
use TotalSurvey\Models\Concerns\Translatable;
use TotalSurvey\Plugin;
use TotalSurvey\Tasks\Utils\GetExpressions;
use TotalSurvey\Tasks\Utils\MaybeDecodeJSON;
use TotalSurveyVendors\TotalSuite\Foundation\Database\TableModel;
use TotalSurveyVendors\TotalSuite\Foundation\Exceptions\Exception;
use TotalSurveyVendors\TotalSuite\Foundation\Support\Collection;
use TotalSurveyVendors\TotalSuite\Foundation\Support\Strings;

/**
 * Class Survey
 *
 * @property int $id
 * @property int $user_id
 * @property string $uid
 * @property string $name
 * @property string $description
 * @property Collection<Section>|Section[] $sections
 * @property array $settings
 * @property string $created_at
 * @property string $updated_at
 * @property string $reset_at
 * @property string $deleted_at
 * @property string $status
 * @property bool $enabled
 *
 * @package TotalSurvey\Models
 */
class Survey extends TableModel
{
    use SurveySettings, Translatable;

    const STATUS_OPEN    = 'open';
    const STATUS_CLOSED  = 'closed';
    const STATUS_DELETED = 'deleted';

    const ACTION_NEXT       = 'next';
    const ACTION_SECTION    = 'section';
    const ACTION_CONDITIONS = 'conditions';
    const ACTION_SUBMIT     = 'submit';

    /**
     * @var string
     */
    protected $table = 'totalsurvey_surveys';

    /**
     * @var array
     */
    protected $types = [
        'user_id'      => 'int',
        'sections'     => 'sections',
        'settings'     => 'settings',
        'translations' => 'translations',
        'enabled'      => 'bool',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'uid',
        'user_id',
        'name',
        'description',
        'status',
        'sections',
        'settings',
        'translations',
    ];
    /**
     * @var array|mixed
     */
    protected $originalAttributes;

    public function __construct($attributes = [])
    {
        $this->originalAttributes = $attributes;
        $attributes               = static::applyTranslations($attributes);

        parent::__construct($attributes);
    }

    /**
     * @return $this
     */
    public function withStatistics(): self
    {
        $entries = Entry::query()
                        ->where('survey_uid', $this->uid);

        $statistics = [
            'total' => 0,
            'today' => 0,
        ];

        if ($entries !== null) {
            $statistics = [
                'total' => $entries->count(),
                'today' => $entries->where('created_at', date('Y-m-d'))->count(),
            ];
        }

        $this->setAttribute('statistics', $statistics);

        return $this;
    }


    /**
     * @param  string  $data
     *
     * @return Collection
     * @noinspection PhpUnused
     */
    public function castToSections($data): Collection
    {
        $sections = [];
        $previous = null;

        if (!empty($data)) {
            $data = MaybeDecodeJSON::invoke($data);

            foreach ($data as $index => $section) {
                $section['blocks'] = $section['questions'] ?? $section['blocks'];
                unset($section['questions']);
                $current        = new Section($this, $section);
                $current->index = $index;

                if ($previous instanceof Section) {
                    $current->setPreviousSection($previous);
                    $previous->setNextSection($current);
                }

                $sections[] = $previous = $current;
            }
        }

        return Collection::create($sections);
    }

    public function castToSettings($data)
    {
        $settings = [];
        if (!empty($data)) {
            $settings = MaybeDecodeJSON::invoke($data);

            if (Plugin::options('design.override', false)) {
                $settings['design'] = Plugin::options('design', []);
            }

            if (empty($settings['expressions'])) {
                $expressions = array_keys(GetExpressions::invoke());
                $expressions = array_combine(
                    array_map(
                        function ($expression) {
                            return sanitize_title_with_dashes($expression);
                        },
                        $expressions
                    ),
                    array_fill(0, count($expressions), '')
                );

                $settings['expressions'] = $expressions;
            }

            if (empty($settings['language_switcher'])) {
                $settings['language_switcher'] = [
                    'enabled'   => false,
                    'languages' => [],
                ];
            }

            if (!empty($settings['contents']['welcome']['content'])) {
                $settings['contents']['welcome'] = [
                    'enabled' => $settings['contents']['welcome']['enabled'],
                    'title'   => '',
                    'blocks'  => [
                        [
                            'type'    => 'content',
                            'typeId'  => 'content:paragraph',
                            'uid'     => Strings::uid(),
                            'content' => [
                                'value'   => $settings['contents']['welcome']['content'],
                                'options' => [
                                    'size'      => 'h2',
                                    'alignment' => 'start',
                                ],
                                'type'    => 'paragraph',
                            ],
                        ],
                    ],

                ];
            }

            if (!empty($settings['contents']['thankyou']['content'])) {
                $settings['contents']['thankyou'] = [
                    'enabled' => $settings['contents']['thankyou']['enabled'],
                    'title'   => '',
                    'blocks'  => [
                        [
                            'type'    => 'content',
                            'typeId'  => 'content:paragraph',
                            'uid'     => Strings::uid(),
                            'content' => [
                                'value'   => $settings['contents']['thankyou']['content'],
                                'options' => [
                                    'alignment' => 'start',
                                ],
                                'type'    => 'paragraph',
                            ],
                        ],
                    ],
                ];
            }

            if (empty($settings['contents']['thankyou']['blocks'])) {
                $settings['contents']['thankyou']['blocks'] = [
                    [
                        'type'    => 'content',
                        'typeId'  => 'content:title',
                        'uid'     => Strings::uid(),
                        'content' => [
                            'value'   => __('Thank you!'),
                            'options' => [
                                'size'      => 'h2',
                                'alignment' => 'start',
                            ],
                            'type'    => 'title',
                        ],
                    ],
                    [
                        'type'    => 'content',
                        'typeId'  => 'content:paragraph',
                        'uid'     => Strings::uid(),
                        'content' => [
                            'value'   => __('Entry received. Thank you for participating in this survey!'),
                            'options' => [
                                'alignment' => 'start',
                            ],
                            'type'    => 'paragraph',
                        ],
                    ],
                ];
            }

            $settings['contents']['welcome']  = new Section($this, $settings['contents']['welcome']);
            $settings['contents']['thankyou'] = new Section($this, $settings['contents']['thankyou']);
        }


        return Collection::create(SurveySettingsFilter::apply($settings));
    }

    public function castToTranslations($data)
    {
        $translations = [];

        if (!empty($data)) {
            $translations = MaybeDecodeJSON::invoke($data);
        }

        return Collection::create($translations);
    }

    /**
     * @param $uid
     *
     * @return Section
     * @throws Exception
     */
    public function getSection($uid): Section
    {
        $section = $this->sections->where(['uid' => $uid])->first();
        SurveySectionNotFound::throwUnless($section, __('Section not found', 'totalsurvey'));

        return $section;
    }

    /**
     * @param  string  $surveyUid
     *
     * @return Survey
     * @throws Exception
     */
    public static function byUid($surveyUid): Survey
    {
        $survey = static::query()->where('uid', $surveyUid)->first();
        SurveyNotFound::throwUnless($survey, __('Survey not found', 'totalsurvey'));

        return $survey;
    }

    /**
     * @param $uid
     *
     * @return Survey
     * @throws Exception
     */
    public static function byUidAndActive($uid): Survey
    {
        $survey = static::query()
                        ->where('uid', $uid)
                        ->where('enabled', true)
                        ->first();

        SurveyNotFound::throwUnless($survey, __('Survey not found', 'totalsurvey'));

        return $survey;
    }

    /**
     * @param $id
     *
     * @return Survey
     * @throws Exception
     */
    public static function byIdAndActive($id): Survey
    {
        $survey = static::query()
                        ->where('id', $id)
                        ->where('enabled', true)
                        ->first();

        SurveyNotFound::throwUnless($survey, __('Survey not found', 'totalsurvey'));

        return $survey;
    }

    /**
     * @return void
     */
    protected static function handleMentions()
    {
        BlockMentionFilter::add(
            function ($mention, $part) {
                return "{{ variable('{$part['_uid']}', '{$part['fallback']}') }}";
            },
            10,
            2
        );
    }

    /**
     * @param $id
     *
     * @return Survey
     * @throws Exception
     */
    public static function byIdAndActiveForRendering($id)
    {
        static::handleMentions();

        return static::byIdAndActive($id);
    }

    /**
     * @param $uid
     *
     * @return Survey
     * @throws Exception
     */
    public static function byUidAndActiveForRendering($uid)
    {
        static::handleMentions();

        $survey = static::byUidAndActive($uid);

        $survey->applyLocale();

        return $survey;
    }

    /**
     * @return Collection
     */
    public function getEntries()
    {
        return Entry::query()
                    ->where('survey_uid', $this->uid)
                    ->where('status', Entry::STATUS_OPEN)
                    ->get();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $survey        = parent::toArray();
        $survey['url'] = $this->getUrl();

        if (UserCanUpdateSurvey::check()) {
            $survey['insights_url'] = $this->getInsightsUrl();
        }

        return $survey;
    }

    /**
     * @param  array  $arguments
     *
     * @return string
     */
    public function getUrl($arguments = [])
    {
        $baseUrl                 = home_url();
        $arguments['survey_uid'] = $this->uid;

        if (Plugin::env()->isPrettyPermalinks()) {
            $base = SurveyLinkFilter::apply('survey');

            $baseUrl = home_url("{$base}/{$this->uid}/");
            unset($arguments['survey_uid']);
        }

        return add_query_arg($arguments, $baseUrl);
    }


    /**
     * @param  array  $arguments
     *
     * @return string
     */
    public function getInsightsUrl($arguments = []): string
    {
        $baseUrl                 = home_url();
        $arguments['survey_uid'] = $this->uid;

        $publishToken = $this->getInsightsPublishToken();

        if (!$publishToken) {
            $publishToken = $this->fillInsightsPublishToken();
        }

        $arguments['publish_token'] = $publishToken;

        if (get_option('permalink_structure')) {
            $baseUrl = home_url("survey-insights/{$this->uid}/");
            unset($arguments['survey_uid']);
        }

        return add_query_arg($arguments, $baseUrl);
    }

    public function getInsightsPublishToken()
    {
        return get_option("publish_token:$this->uid");
    }

    public function isValidInsightsPublishToken($token)
    {
        return $this->getInsightsPublishToken() === $token;
    }

    protected function fillInsightsPublishToken()
    {
        $publishToken = wp_generate_password(6, false);
        update_option("publish_token:$this->uid", $publishToken);

        return $publishToken;
    }

    /**
     * @return $this
     */
    public function toPublic(): Survey
    {
        $this->deleteAttribute('settings.limitations');
        $this->deleteAttribute('settings.reports');
        $this->deleteAttribute('settings.workflow');
        $this->deleteAttribute('settings.scoring');
        $this->deleteAttribute('id');
        $this->deleteAttribute('created_at');
        $this->deleteAttribute('updated_at');
        $this->deleteAttribute('deleted_at');
        $this->deleteAttribute('reset_at');
        $this->deleteAttribute('enabled');
        $this->deleteAttribute('status');
        $this->deleteAttribute('translations');
        $this->deleteAttribute('user_id');

        return $this;
    }

    public static function getTranslatableAttributes()
    {
        $expressions = array_map(
            function ($expression) {
                $expression = sanitize_title_with_dashes($expression);

                return "settings.expressions.{$expression}";
            },
            array_keys(GetExpressions::invoke())
        );

        return array_merge(
            [
                'name',
                'description',
            ],
            $expressions
        );
    }

    public function isEditContext()
    {
        return $this->getAttribute('context') === 'edit';
    }
}
