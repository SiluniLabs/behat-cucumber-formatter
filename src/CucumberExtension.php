<?php

declare(strict_types=1);

namespace SiluniLabs\BehatCucumberFormatter;

use Behat\Testwork\Output\ServiceContainer\OutputExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** This class is responsible for integrating the Cucumber formatter into Behat's extension system */
final class CucumberExtension implements Extension
{
    public function getConfigKey(): string
    {
        return 'cucumber';
    }

    public function load(ContainerBuilder $container, array $config): void
    {
        $outputPrinterDefinition = $container
            ->register(CucumberOutputPrinter::class)
            ->addArgument('%paths.base%');

        $container
            ->register(OutputExtension::FORMATTER_TAG.'.'.CucumberFormatter::NAME, CucumberFormatter::class)
            ->addArgument('%paths.base%')
            ->addArgument($outputPrinterDefinition)
            ->addTag(OutputExtension::FORMATTER_TAG, ['priority' => 100]);
    }

    public function configure(ArrayNodeDefinition $builder): void {}

    public function initialize(ExtensionManager $extensionManager): void {}

    public function process(ContainerBuilder $container): void {}
}
