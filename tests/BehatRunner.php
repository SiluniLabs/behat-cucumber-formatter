<?php

declare(strict_types=1);

namespace SiluniLabs\BehatCucumberFormatter\Tests;

use Behat\Behat\ApplicationFactory;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Runs Behat on the fixture project, in the current process so the formatter is included in code coverage.
 */
final class BehatRunner
{
    /**
     * @param list<string>          $options
     * @param string                $config  Configuration file of the fixture project
     * @param array<string, string> $env     Additional environment variables
     *
     * @return array{int, string} Exit code and output
     */
    public static function run(array $options, string $reportPath, string $config = 'behat.php', array $env = []): array
    {
        $env = ['CUCUMBER_REPORT' => $reportPath] + $env;
        $previousEnv = [];
        foreach ($env as $name => $value) {
            $previousEnv[$name] = getenv($name);
            putenv($name.'='.$value);
        }
        $previousDir = (string) getcwd();
        chdir(__DIR__.'/Fixtures');

        try {
            $application = (new ApplicationFactory())->createApplication();
            $application->setAutoExit(false);

            // Without "--no-interaction", Behat prompts for the context of the undefined step snippets when run from a terminal.
            $input = new ArgvInput(['behat', '--config='.__DIR__.'/Fixtures/'.$config, '--no-interaction', ...$options]);
            $output = new BufferedOutput();

            return [$application->run($input, $output), $output->fetch()];
        } finally {
            chdir($previousDir);
            foreach ($previousEnv as $name => $value) {
                putenv(false === $value ? $name : $name.'='.$value);
            }
        }
    }
}
