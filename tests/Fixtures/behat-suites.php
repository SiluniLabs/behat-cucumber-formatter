<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Filter\TagFilter;
use Behat\Config\Formatter\Formatter;
use Behat\Config\Profile;
use Behat\Config\Suite;
use SiluniLabs\BehatCucumberFormatter\CucumberExtension;

$profile = new Profile('default')
    ->withExtension(new Extension(CucumberExtension::class))
    ->withFormatter(new Formatter('cucumber')->withOutputPath((string) getenv('CUCUMBER_REPORT')));

// The "unknown" suite matches no scenario and produces no report.
foreach (['PROJ-1', 'PROJ-2', 'unknown'] as $tag) {
    $profile->withSuite(new Suite($tag)
        ->withPaths('%paths.base%/features')
        ->withContexts(CalculatorContext::class)
        ->withFilter(new TagFilter('@'.$tag)),
    );
}

return new Config()->withProfile($profile);
