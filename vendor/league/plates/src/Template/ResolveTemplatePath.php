<?php

namespace TotalSurveyVendors\League\Plates\Template;
! defined( 'ABSPATH' ) && exit();


use TotalSurveyVendors\League\Plates\Exception\TemplateNotFound;

interface ResolveTemplatePath
{
    /**
     * @throws TemplateNotFound if the template could not be properly resolved to a file path
     */
    public function __invoke(Name $name): string;
}
