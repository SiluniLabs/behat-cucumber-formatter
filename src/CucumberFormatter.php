<?php

declare(strict_types=1);

namespace SiluniLabs\BehatCucumberFormatter;

use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Behat\Tester\Result\ExecutedStepResult;
use Behat\Gherkin\Node\ExampleNode;
use Behat\Gherkin\Node\NamedScenarioInterface;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Gherkin\Node\TaggedNodeInterface;
use Behat\Testwork\EventDispatcher\Event\AfterSuiteTested;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Behat\Testwork\EventDispatcher\Event\SuiteTested;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Tester\Result\TestResult;

/**
 * Writes a Cucumber JSON report, including feature and scenario tags.
 *
 * When the output path is a directory (ending with a slash), one "<prefix><suite>.json" report is written per suite instead.
 *
 * @see https://github.com/cucumber/cucumber-json-schema
 */
final class CucumberFormatter implements Formatter
{
    public const NAME = 'cucumber';

    /** @var array<string, mixed> */
    private array $parameters = [];

    /** @var array<int, array<string, mixed>> */
    private array $features = [];

    private ?int $currentFeature = null;

    private ?int $currentScenario = null;

    private float $stepStart = 0.0;

    public function __construct(
        private readonly ReportPrinter $printer,
        private readonly string $basePath,
        string $outputPath,
        private readonly string $prefix = '',
    ) {
        $this->printer->setOutputPath($outputPath);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SuiteTested::BEFORE => 'onBeforeSuite',
            FeatureTested::BEFORE => 'onBeforeFeature',
            ScenarioTested::BEFORE => 'onBeforeScenario',
            ExampleTested::BEFORE => 'onBeforeScenario',
            StepTested::BEFORE => 'onBeforeStep',
            StepTested::AFTER => 'onAfterStep',
            SuiteTested::AFTER => 'onAfterSuite',
            ExerciseCompleted::AFTER => 'onAfterExercise',
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDescription(): string
    {
        return 'Outputs a Cucumber JSON report.';
    }

    public function getOutputPrinter(): ReportPrinter
    {
        return $this->printer;
    }

    public function setParameter(string $name, mixed $value): void
    {
        $this->parameters[$name] = $value;
    }

    public function getParameter(string $name): mixed
    {
        return $this->parameters[$name] ?? null;
    }

    public function onBeforeSuite(): void
    {
        if ($this->isReportPerSuite()) {
            $this->features = [];
            $this->currentFeature = null;
            $this->currentScenario = null;
        }
    }

    public function onBeforeFeature(BeforeFeatureTested $event): void
    {
        $feature = $event->getFeature();

        $this->features[] = [
            'uri' => $this->relativePath((string) $feature->getFile()),
            'id' => $this->slug((string) $feature->getTitle()),
            'keyword' => $feature->getKeyword(),
            'name' => (string) $feature->getTitle(),
            'description' => (string) $feature->getDescription(),
            'line' => $feature->getLine(),
            'tags' => $this->tags($feature->getTags(), $feature->getLine() - 1),
            'elements' => [],
        ];
        $this->currentFeature = array_key_last($this->features);
        $this->currentScenario = null;
    }

    public function onBeforeScenario(BeforeScenarioTested $event): void
    {
        if (null === $this->currentFeature) {
            return;
        }

        $feature = $event->getFeature();
        $scenario = $event->getScenario();
        $name = $scenario instanceof NamedScenarioInterface ? (string) $scenario->getName() : (string) $scenario->getTitle();
        $tagsLine = $scenario instanceof ExampleNode ? $scenario->getLine() : $scenario->getLine() - 1;

        $this->features[$this->currentFeature]['elements'][] = [
            'id' => $this->slug((string) $feature->getTitle()).';'.$this->slug($name),
            'keyword' => $scenario->getKeyword(),
            'name' => $name,
            'description' => '',
            'line' => $scenario->getLine(),
            'type' => 'scenario',
            'tags' => [
                ...$this->tags($feature->getTags(), $feature->getLine() - 1),
                ...$this->tags($scenario instanceof TaggedNodeInterface ? $scenario->getTags() : [], $tagsLine),
            ],
            'steps' => [],
        ];
        $this->currentScenario = array_key_last($this->features[$this->currentFeature]['elements']);
    }

    public function onBeforeStep(): void
    {
        $this->stepStart = microtime(true);
    }

    public function onAfterStep(AfterStepTested $event): void
    {
        // Steps outside a reported scenario cannot be attached to it.
        if (null === $this->currentFeature || null === $this->currentScenario) {
            return;
        }

        $step = $event->getStep();
        $result = $event->getTestResult();

        $stepData = [
            'keyword' => rtrim($step->getKeyword()).' ',
            'name' => $step->getText(),
            'line' => $step->getLine(),
            'match' => ['location' => $result instanceof ExecutedStepResult ? (string) $result->getStepDefinition()?->getPath() : ''],
            'result' => [
                'status' => $this->status($result->getResultCode()),
                'duration' => (int) ((microtime(true) - $this->stepStart) * 1e9),
            ],
        ];

        foreach ($step->getArguments() as $argument) {
            if ($argument instanceof PyStringNode) {
                $stepData['doc_string'] = ['value' => $argument->getRaw(), 'line' => $argument->getLine()];
            } elseif ($argument instanceof TableNode) {
                $stepData['rows'] = array_map(static fn (array $row): array => ['cells' => $row], $argument->getRows());
            }
        }

        if ($result instanceof ExecutedStepResult && null !== $result->getException()) {
            $stepData['result']['error_message'] = $result->getException()->getMessage();
        }

        $this->features[$this->currentFeature]['elements'][$this->currentScenario]['steps'][] = $stepData;
    }

    public function onAfterSuite(AfterSuiteTested $event): void
    {
        // Suites without any feature, e.g. filtered out entirely, produce no report.
        if (!$this->isReportPerSuite() || [] === $this->features) {
            return;
        }

        $directory = (string) $this->printer->getOutputPath();
        $fileName = (string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $this->prefix.$event->getSuite()->getName()).'.json';

        $this->printer->setOutputPath(rtrim($directory, '/'.\DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR.$fileName);
        $this->writeReport();

        $this->printer->setOutputPath($directory);
    }

    public function onAfterExercise(): void
    {
        if (!$this->isReportPerSuite()) {
            $this->writeReport();
        }
    }

    private function isReportPerSuite(): bool
    {
        $path = (string) $this->printer->getOutputPath();

        return str_ends_with($path, '/') || str_ends_with($path, \DIRECTORY_SEPARATOR) || is_dir($path);
    }

    private function writeReport(): void
    {
        $this->printer->write(json_encode(array_values($this->features), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR));
        $this->printer->flush();
    }

    /**
     * @param list<string> $tags
     *
     * @return list<array{name: string, line: int}>
     */
    private function tags(array $tags, int $line): array
    {
        return array_map(static fn (string $tag): array => ['name' => '@'.ltrim($tag, '@'), 'line' => $line], $tags);
    }

    private function status(int $resultCode): string
    {
        return match ($resultCode) {
            TestResult::PASSED => 'passed',
            TestResult::PENDING => 'pending',
            TestResult::UNDEFINED => 'undefined',
            TestResult::FAILED => 'failed',
            default => 'skipped',
        };
    }

    private function relativePath(string $path): string
    {
        $base = rtrim($this->basePath, \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR;

        return str_starts_with($path, $base) ? substr($path, \strlen($base)) : $path;
    }

    private function slug(string $text): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($text)), '-');
    }
}
