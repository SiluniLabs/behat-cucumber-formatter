<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Filter\TagFilter;
use Behat\Config\Formatter\PrettyFormatter;
use Behat\Config\Profile;
use Behat\Config\Suite;
use SiluniLabs\BehatCucumberFormatter\CucumberExtension;

$profile = (new Profile('default'))
    ->withExtension(new Extension(CucumberExtension::class, [
        'output_path' => getenv('CUCUMBER_REPORT') ?: '%paths.base%/var/',
        'prefix' => getenv('CUCUMBER_PREFIX') ?: '',
        'auto_enable' => true,
    ]))
    // Keeps the console output out of the PHPUnit output, Behat running in the PHPUnit process.
    ->withFormatter((new PrettyFormatter())->withOutputPath('/dev/null'));

foreach (['PROJ-1', 'PROJ-3', 'unknown'] as $tag) {
    $profile->withSuite(
        (new Suite($tag))
            ->withPaths('%paths.base%/features')
            ->withContexts(FeatureContext::class)
            ->withFilter(new TagFilter('@'.$tag)),
    );
}

return (new Config())->withProfile($profile);
