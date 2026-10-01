<?php

declare(strict_types=1);

namespace SiluniLabs\BehatCucumberFormatter\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Compares the whole generated reports with the expected ones.
 *
 * Run with UPDATE_EXPECTED=1 to regenerate the expected reports after an intended change of the output.
 */
#[CoversNothing]
final class CucumberReportTest extends TestCase
{
    private const string EXPECTED_DIR = __DIR__.'/Fixtures/expected';

    /**
     * @return iterable<string, array{string, string, list<string>}>
     */
    public static function configurations(): iterable
    {
        yield 'single suite' => ['behat.php', 'default', ['default.json']];
        // The "unknown" suite matches no scenario and produces no report.
        yield 'multiple suites' => ['behat-suites.php', 'suites', ['proj-1.json', 'proj-2.json']];
    }

    /**
     * @param list<string> $expectedFiles
     */
    #[DataProvider('configurations')]
    public function testGeneratedReportsMatchTheExpectedOnes(string $config, string $expectedDir, array $expectedFiles): void
    {
        $reportDir = sys_get_temp_dir().'/behat-cucumber-'.bin2hex(random_bytes(4));
        BehatRunner::run(['--no-colors'], $reportDir, $config);

        $files = array_values(array_diff((array) scandir($reportDir), ['.', '..']));
        $reports = [];
        foreach ($files as $file) {
            $reports[$file] = self::normalize((string) file_get_contents($reportDir.'/'.$file));
            unlink($reportDir.'/'.$file);
        }
        rmdir($reportDir);

        self::assertSame($expectedFiles, $files);

        foreach ($reports as $file => $report) {
            $expectedReport = self::EXPECTED_DIR.'/'.$expectedDir.'/'.$file;

            if ('1' === getenv('UPDATE_EXPECTED')) {
                file_put_contents($expectedReport, $report);
            }

            self::assertJsonStringEqualsJsonFile($expectedReport, $report, (string) $file);
        }
    }

    /**
     * Durations change on every run, so they are reset to keep the reports comparable.
     */
    private static function normalize(string $json): string
    {
        $report = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);

        array_walk_recursive($report, static function (mixed &$value, int|string $key): void {
            if ('duration' === $key) {
                $value = 0;
            }
        });

        return json_encode($report, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR)."\n";
    }
}
