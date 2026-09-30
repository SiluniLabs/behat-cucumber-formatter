<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Formatter\Formatter;
use Behat\Config\Profile;
use Behat\Config\Suite;
use SiluniLabs\BehatCucumberFormatter\CucumberExtension;

return new Config()->withProfile(new Profile('default')
    ->withExtension(new Extension(CucumberExtension::class))
    ->withFormatter(new Formatter('cucumber')->withOutputPath((string) getenv('CUCUMBER_REPORT')))
    ->withSuite(new Suite('default')
        ->withPaths('%paths.base%/features')
        ->withContexts(CalculatorContext::class),
    ),
);
