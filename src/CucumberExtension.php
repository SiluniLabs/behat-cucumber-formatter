<?php

declare(strict_types=1);

namespace SiluniLabs\BehatCucumberFormatter;

use Behat\Testwork\Output\Printer\Factory\FileOutputFactory;
use Behat\Testwork\Output\ServiceContainer\OutputExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Registers the "cucumber" formatter, producing a Cucumber JSON report.
 */
final class CucumberExtension implements Extension
{
    public const FORMATTER_ID = OutputExtension::FORMATTER_TAG.'.'.CucumberFormatter::NAME;

    public function getConfigKey(): string
    {
        return 'cucumber';
    }

    public function initialize(ExtensionManager $extensionManager): void
    {
    }

    public function configure(ArrayNodeDefinition $builder): void
    {
        $builder
            ->children()
                ->scalarNode('output_path')
                    ->info('Path of the generated report; a directory (ending with a slash) writes one report per suite')
                    ->defaultValue('%paths.base%/var/behat/cucumber.json')
                ->end()
                ->scalarNode('prefix')
                    ->info('Prefix of the report file names, when one report is written per suite')
                    ->defaultValue('')
                ->end()
                ->booleanNode('auto_enable')
                    ->info('Enable the formatter alongside the configured ones, without declaring it in the profile')
                    ->defaultFalse()
                ->end()
            ->end();
    }

    /**
     * @param array{output_path: string, prefix: string, auto_enable: bool} $config
     */
    public function load(ContainerBuilder $container, array $config): void
    {
        $definition = new Definition(CucumberFormatter::class, [
            new Definition(ReportPrinter::class, [new Definition(FileOutputFactory::class)]),
            '%paths.base%',
            $config['output_path'],
            $config['prefix'],
        ]);
        $definition->addTag(OutputExtension::FORMATTER_TAG, ['priority' => 100]);
        $container->setDefinition(self::FORMATTER_ID, $definition);
        $container->setParameter('cucumber.auto_enable', $config['auto_enable']);
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->getParameter('cucumber.auto_enable') || !$container->hasDefinition(OutputExtension::MANAGER_ID)) {
            return;
        }

        $manager = $container->getDefinition(OutputExtension::MANAGER_ID);
        foreach ($manager->getMethodCalls() as [$method, $arguments]) {
            if (\in_array($method, ['enableFormatter', 'disableFormatter'], true) && CucumberFormatter::NAME === ($arguments[0] ?? null)) {
                return;
            }
        }

        $manager->addMethodCall('enableFormatter', [CucumberFormatter::NAME]);
    }
}
