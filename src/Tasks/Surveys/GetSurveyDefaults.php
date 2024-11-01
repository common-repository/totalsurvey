<?php

namespace TotalSurvey\Tasks\Surveys;
! defined( 'ABSPATH' ) && exit();


use Exception;
use TotalSurvey\Plugin;
use TotalSurvey\Tasks\Utils\GetExpressions;
use TotalSurvey\Tasks\Utils\GetRoles;
use TotalSurveyVendors\TotalSuite\Foundation\Support\Strings;
use TotalSurveyVendors\TotalSuite\Foundation\Task;

/**
 * Class GetSurveyDefaults
 *
 * @package TotalSurvey\Tasks\Survey
 * @method static array invoke()
 * @method static array invokeWithFallback($fallback)
 */
class GetSurveyDefaults extends Task
{
    /**
     * @inheritDoc
     */
    protected function validate()
    {
        return true;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    protected function execute()
    {
        $designDefaults = wp_parse_args(
            Plugin::options('design', []),
            [
                'template' => 'default-template',
                'colors'   => [
                    'primary'    => [
                        'base'     => '#3A9278',
                        'contrast' => '#FFFFFF',
                    ],
                    'secondary'  => [
                        'base'     => '#364959',
                        'contrast' => '#FFFFFF',
                    ],
                    'background' => [
                        'base'     => '#f6f6f6',
                        'contrast' => '#FFFFFF',
                    ],
                    'dark'       => [
                        'base'     => '#263440',
                        'contrast' => '#FFFFFF',
                    ],
                    'error'      => [
                        'base'     => '#F26418',
                        'contrast' => '#FFFFFF',
                    ],
                    'success'    => [
                        'base'     => '#90BE6D',
                        'contrast' => '#FFFFFF',
                    ],
                ],
                'size'     => 'regular',
                'space'    => 'normal',
                'radius'   => 'rounded',
            ]
        );

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

        return [
            'name'        => 'Untitled Survey',
            'description' => '',
            'settings'    => [
                'limitations'       => [
                    'authentication' => [
                        'enabled' => false,
                        'options' => [
                            'specificRoles' => false,
                            'roles'         => wp_list_pluck(GetRoles::invoke(), 'name', 'id'),
                        ],
                    ],
                ],
                'contents'          => [
                    'welcome'  => [
                        'enabled' => false,
                        'title'   => __('Welcome', 'totalsurvey'),
                        'blocks'  => [],
                    ],
                    'thankyou' => [
                        'enabled' => false,
                        'title'   => __('Thank you!', 'totalsurvey'),
                        'blocks'  => [
                            [
                                'type'    => 'content',
                                'typeId'  => 'content:title',
                                'uid'     => Strings::uid(),
                                'content' => [
                                    'value'   => __('Thank you!', 'totalsurvey'),
                                    'options' => [
                                        'size' => 'h2',
                                    ],
                                    'type'    => 'title',
                                ],
                            ],
                            [
                                'type'    => 'content',
                                'typeId'  => 'content:paragraph',
                                'uid'     => Strings::uid(),
                                'content' => [
                                    'value'   => __(
                                        'Entry received. Thank you for participating in this survey!',
                                        'totalsurvey'
                                    ),
                                    'options' => [],
                                    'type'    => 'paragraph',
                                ],
                            ],
                        ],
                    ],
                ],
                'design'            => $designDefaults,
                'workflow'          => [
                    'rules' => [],
                ],
                'redirection'       => [
                    'enabled'      => false,
                    'url'          => '',
                    'blank_target' => false,

                ],
                'can_view_entry'    => [
                    'enabled' => false,
                ],
                'can_edit_entry'    => [
                    'enabled' => false,
                ],
                'language_switcher' => [
                    'enabled'   => false,
                    'languages' => [],
                ],
                'reports'           => [
                    'enabled'  => false,
                    'email'    => wp_get_current_user()->user_email,
                    'interval' => '7',
                    'sections' => [],
                ],
                'categories'        => [
                    'enabled' => false,
                    'items'   => [],
                ],
                'expressions'       => $expressions,
            ],
            'sections'    => [
                [
                    'uid'              => Strings::uid(),
                    'title'            => 'Sample section',
                    'description'      => '',
                    'blocks'           => [],
                    'action'           => 'next',
                    'next_section_uid' => null,
                    'conditions'       => [],
                ],
            ],
            'status'      => 'open',
            'enabled'     => true,
        ];
    }
}
