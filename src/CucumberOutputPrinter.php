<?php

declare(strict_types=1);

namespace SiluniLabs\BehatCucumberFormatter;

use Behat\Testwork\Output\Printer\OutputPrinter;

/** This class is responsible for printing Cucumber JSON output files */
class CucumberOutputPrinter implements OutputPrinter
{
    private ?string $outputPath = null;

    private string $fileName = 'default.json';

    public function __construct(
        private readonly string $pathBase,
    ) {}

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function setOutputPath(string $path): void
    {
        $this->outputPath = $path;
    }

    public function getOutputPath(): string
    {
        return $this->outputPath ?? $this->pathBase;
    }

    public function setOutputStyles(array $styles): void {}

    public function setOutputDecorated(bool $decorated): void {}

    public function setOutputVerbosity(int $level): void {}

    /**
     * Writes message(s) to the report file, replacing its content.
     *
     * @param string|array<string> $messages
     */
    public function write($messages): void
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        $this->doWrite($messages, false);
    }

    /**
     * Writes newlined message(s) at the end of the report file.
     *
     * @param string|array<string> $messages
     */
    public function writeln($messages = ''): void
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        $this->doWrite($messages, true);
    }

    /**
     * Clear output stream, so on next write formatter will need to init (create) it again.
     * Not needed in my case.
     */
    public function flush(): void {}

    /**
     * Called by the formatter when a suite starts.
     */
    public function removeOldFile(): void
    {
        $filePath = $this->getFilePath();

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * @param array<string> $messages
     */
    private function doWrite(array $messages, bool $append): void
    {
        // create the output path if it does not exist.
        if (!is_dir($this->getOutputPath())) {
            mkdir($this->getOutputPath(), 0777, true);
        }

        // write data to the file
        file_put_contents($this->getFilePath(), implode("\n", $messages)."\n", $append ? \FILE_APPEND : 0);
    }

    private function getFilePath(): string
    {
        return rtrim($this->getOutputPath(), '/'.\DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR.$this->fileName;
    }
}
