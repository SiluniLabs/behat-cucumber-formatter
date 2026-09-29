<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Formatter\PrettyFormatter;
use Behat\Config\Profile;
use Behat\Config\Suite;
use SiluniLabs\BehatCucumberFormatter\CucumberExtension;

return (new Config())
    ->withProfile(
        (new Profile('default'))
            ->withExtension(new Extension(CucumberExtension::class, [
                'output_path' => getenv('CUCUMBER_REPORT') ?: '%paths.base%/var/cucumber.json',
                'auto_enable' => true,
            ]))
            // Keeps the console output out of the PHPUnit output, Behat running in the PHPUnit process.
            ->withFormatter((new PrettyFormatter())->withOutputPath('/dev/null'))
            ->withSuite(
                (new Suite('default'))
                    ->withPaths('%paths.base%/features')
                    ->withContexts(FeatureContext::class),
            ),
    );
