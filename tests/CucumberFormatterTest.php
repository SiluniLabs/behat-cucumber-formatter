<?php

declare(strict_types=1);

namespace SiluniLabs\BehatCucumberFormatter\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SiluniLabs\BehatCucumberFormatter\CucumberExtension;
use SiluniLabs\BehatCucumberFormatter\CucumberFormatter;
use SiluniLabs\BehatCucumberFormatter\CucumberOutputPrinter;

#[CoversClass(CucumberExtension::class)]
#[CoversClass(CucumberFormatter::class)]
#[CoversClass(CucumberOutputPrinter::class)]
final class CucumberFormatterTest extends TestCase
{
    /** @var list<array<string, mixed>> */
    private static array $report;

    private static int $exitCode;

    public static function setUpBeforeClass(): void
    {
        $reportDir = self::reportDir();
        [self::$exitCode] = BehatRunner::run(['--no-colors'], $reportDir);

        self::$report = self::readReport($reportDir.'/default.json');
        self::removeDirectory($reportDir);
    }

    public function testBehatFailsBecauseOfTheFailingScenario(): void
    {
        self::assertSame(1, self::$exitCode);
    }

    public function testFeatureIsReportedWithItsTags(): void
    {
        self::assertCount(1, self::$report);
        $feature = self::$report[0];

        self::assertSame('features/calculator.feature', $feature['uri']);
        self::assertSame('calculator', $feature['id']);
        self::assertSame('Feature', $feature['keyword']);
        self::assertSame('Calculator', $feature['name']);
        self::assertSame(2, $feature['line']);
        self::assertSame([['name' => '@calculator', 'line' => 1]], $feature['tags']);
        self::assertCount(6, $feature['elements']);
    }

    public function testScenarioTagsIncludeInheritedFeatureTags(): void
    {
        $scenario = $this->scenario('Passing scenario');

        self::assertSame('calculator;passing-scenario', $scenario['id']);
        self::assertSame('scenario', $scenario['type']);
        self::assertSame(11, $scenario['line']);
        self::assertSame([
            ['name' => '@calculator', 'line' => 1],
            ['name' => '@PROJ-1', 'line' => 10],
        ], $scenario['tags']);
    }

    public function testPassingScenarioStepsIncludeBackgroundAndDocString(): void
    {
        $steps = $this->scenario('Passing scenario')['steps'];

        self::assertSame(['Given ', 'When ', 'Then ', 'And '], array_column($steps, 'keyword'));
        self::assertSame(['passed', 'passed', 'passed', 'passed'], array_column(array_column($steps, 'result'), 'status'));
        self::assertSame('a value of 1', $steps[0]['name']);
        self::assertSame(8, $steps[0]['line']);
        self::assertSame('CalculatorContext::aValueOf()', $steps[0]['match']['location']);
        self::assertIsInt($steps[0]['result']['duration']);
        self::assertSame('value 3', $steps[3]['doc_string']['value']);
    }

    public function testFailingStepHasErrorMessageAndFollowingStepsAreSkipped(): void
    {
        $steps = $this->scenario('Failing scenario')['steps'];

        self::assertSame(['passed', 'passed', 'failed', 'skipped'], array_column(array_column($steps, 'result'), 'status'));
        self::assertSame('Expected 4, got 3.', $steps[2]['result']['error_message']);
        self::assertArrayNotHasKey('error_message', $steps[3]['result']);
    }

    public function testUndefinedStepIsReported(): void
    {
        $steps = $this->scenario('Undefined step')['steps'];

        self::assertSame('undefined', $steps[1]['result']['status']);
        self::assertSame('', $steps[1]['match']['location']);
        self::assertSame([['name' => '@calculator', 'line' => 1]], $this->scenario('Undefined step')['tags']);
    }

    public function testTableArgumentIsReported(): void
    {
        $steps = $this->scenario('Table argument')['steps'];

        self::assertSame([['cells' => ['2']], ['cells' => ['3']]], $steps[1]['rows']);
        self::assertSame('passed', $steps[2]['result']['status']);
    }

    public function testEachOutlineExampleIsAScenarioWithTheOutlineTags(): void
    {
        $examples = array_values(array_filter(
            self::$report[0]['elements'],
            static fn (array $element): bool => str_starts_with($element['name'], 'Outline scenario'),
        ));

        self::assertSame(['Outline scenario #1', 'Outline scenario #2'], array_column($examples, 'name'));
        self::assertSame([41, 42], array_column($examples, 'line'));

        foreach ($examples as $example) {
            self::assertSame(['@calculator', '@PROJ-3'], array_column($example['tags'], 'name'));
            self::assertSame(['passed', 'passed', 'passed'], array_column(array_column($example['steps'], 'result'), 'status'));
        }

        self::assertSame('I add 4', $examples[1]['steps'][1]['name']);
    }

    public function testCliFormatOptionReplacesTheConfiguredFormatter(): void
    {
        $reportDir = self::reportDir();
        BehatRunner::run(['--format=progress', '--out=/dev/null'], $reportDir);

        self::assertDirectoryDoesNotExist($reportDir);
    }

    public function testCliCanEnableTheFormatterWithACustomOutput(): void
    {
        $reportDir = self::reportDir();
        BehatRunner::run(['--format=progress', '--out=/dev/null', '--format=cucumber', '--out='.$reportDir], self::reportDir());

        self::assertCount(6, self::readReport($reportDir.'/default.json')[0]['elements']);
        self::removeDirectory($reportDir);
    }

    public function testOneReportIsWrittenPerSuite(): void
    {
        $reportDir = self::reportDir();
        BehatRunner::run(['--no-colors'], $reportDir, 'behat-suites.php');

        // The "unknown" suite matches no scenario and produces no report.
        self::assertSame(['proj-1.json', 'proj-2.json'], array_values(array_diff((array) scandir($reportDir), ['.', '..'])));

        $firstReport = self::readReport($reportDir.'/proj-1.json');
        $secondReport = self::readReport($reportDir.'/proj-2.json');

        self::assertCount(1, $firstReport);
        self::assertSame(['Passing scenario'], array_column($firstReport[0]['elements'], 'name'));
        self::assertCount(1, $secondReport);
        self::assertSame(['Failing scenario'], array_column($secondReport[0]['elements'], 'name'));

        self::removeDirectory($reportDir);
    }

    public function testPreviousReportIsReplaced(): void
    {
        $reportDir = self::reportDir();
        mkdir($reportDir);
        file_put_contents($reportDir.'/default.json', 'stale');
        BehatRunner::run(['--no-colors'], $reportDir);

        self::assertCount(1, self::readReport($reportDir.'/default.json'));
        self::removeDirectory($reportDir);
    }

    private static function reportDir(): string
    {
        return sys_get_temp_dir().'/behat-cucumber-'.bin2hex(random_bytes(4));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function readReport(string $path): array
    {
        self::assertFileExists($path);

        return json_decode((string) file_get_contents($path), true, flags: \JSON_THROW_ON_ERROR);
    }

    private static function removeDirectory(string $dir): void
    {
        foreach (glob($dir.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($dir);
    }

    /**
     * @return array<string, mixed>
     */
    private function scenario(string $name): array
    {
        foreach (self::$report[0]['elements'] as $element) {
            if ($name === $element['name']) {
                return $element;
            }
        }

        self::fail(\sprintf('Scenario "%s" not found in the report.', $name));
    }
}
