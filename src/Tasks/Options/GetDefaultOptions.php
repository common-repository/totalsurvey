<?php


namespace TotalSurvey\Tasks\Options;
! defined( 'ABSPATH' ) && exit();



use TotalSurvey\Tasks\Utils\GetExpressions;
use TotalSurveyVendors\TotalSuite\Foundation\Task;

//@TODO: Extract this task to the foundation framework

/**
 * Class DefaultOptions
 *
 * @package TotalSurvey\Tasks\Options
 */
class GetDefaultOptions extends Task
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
     */
    protected function execute()
    {
        return [
            'privacy'     => [
                'hashIP'    => false,
                'hashAgent' => false,
                'honorDNT'  => false,
            ],
            'design'      => [
                'override' => true,
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
            ],
            'advanced'    => [
                'cacheCompatibility' => true,
                'recaptcha'          => [
                    'enabled'   => false,
                    'key'       => '',
                    'secret'    => '',
                    'threshold' => 0.5,
                ],
            ],
            'expressions' => array_map(
                static function ($item) {
                    $item['translations'] = [];

                    return $item;
                },
                GetExpressions::invoke()
            ),
            'general'     => [
                'showCredits' => false,
            ],
            'uninstall'   => [
                'wipeOnUninstall' => false,
            ],
        ];
    }
}
