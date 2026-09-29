<?php

declare(strict_types=1);

namespace SiluniLabs\BehatCucumberFormatter;

use Behat\Testwork\Output\Printer\StreamOutputPrinter;

/**
 * Stream printer exposing its configured output path, so the formatter can derive one report path per suite.
 */
final class ReportPrinter extends StreamOutputPrinter
{
    public function getOutputPath(): ?string
    {
        return $this->getOutputFactory()->getOutputPath();
    }
}
