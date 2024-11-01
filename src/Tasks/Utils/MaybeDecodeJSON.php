<?php

namespace TotalSurvey\Tasks\Utils;
! defined( 'ABSPATH' ) && exit();


use TotalSurveyVendors\TotalSuite\Foundation\Task;

class MaybeDecodeJSON extends Task
{
    protected $value;
    protected $recursive = false;

    public function __construct($value, $recursive = false)
    {
        $this->value     = $value;
        $this->recursive = $recursive;
    }

    protected function validate()
    {
        return true;
    }

    protected function execute()
    {
        $this->value = $this->handle($this->value);

        return $this->value;
    }

    protected function handle($value)
    {
        if ($this->recursive) {
            if (is_array($value)) {
                foreach ($value as $itemIndex => $itemValue) {
                    $value[$itemIndex] = $this->handle($itemValue);
                }
            }
        }

        if (!is_string($value) || '' === $value) {
            return $value;
        }

        if (stripos($value, '{') === false && stripos($value, '[') === false) {
            return $value;
        }

        $json = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $this->recursive ? $this->handle($json) : $json : $value;
    }
}
