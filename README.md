# Behat Cucumber JSON Formatter

[Behat 4](https://behat.org) extension writing a Cucumber JSON report (with feature and scenario tags).

## Requirements

- PHP >= 8.4
- Behat ^4.0

## Installation

```bash
composer require --dev silunilabs/behat-cucumber-formatter
```

## Configuration

Register the extension in your `behat.php`:

```php
<?php

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Profile;
use SiluniLabs\BehatCucumberFormatter\CucumberExtension;

return (new Config())
    ->withProfile(
        (new Profile('default'))
            ->withExtension(new Extension(CucumberExtension::class, [
                'output_path' => '%paths.base%/var/behat/cucumber.json',
                'auto_enable' => false,
            ])),
    );
```

| Option        | Default                                 | Description                                                                  |
|---------------|-----------------------------------------|------------------------------------------------------------------------------|
| `output_path` | `%paths.base%/var/behat/cucumber.json`  | Path of the generated report; a directory (ending with `/`) writes one report per suite |
| `prefix`      | `''`                                    | Prefix of the report file names, when one report is written per suite       |
| `auto_enable` | `false`                                 | Enable the formatter alongside the configured ones, without declaring it in the profile |

## Usage

The `cucumber` formatter is disabled by default. Enable it in one of the following ways.

From the command line (keep another formatter for the console output):

```bash
vendor/bin/behat --format=progress --out=std --format=cucumber
```

In the profile:

```php
use Behat\Config\Formatter\Formatter;
use Behat\Config\Formatter\ProgressFormatter;

(new Profile('default'))
    ->withExtension(new Extension(CucumberExtension::class))
    ->withFormatter(new ProgressFormatter())
    ->withFormatter(new Formatter('cucumber'));
```

Or with `'auto_enable' => true`: the report is then written on every `vendor/bin/behat` run, next to the formatters already configured. Passing `--format` on the command line still takes precedence.

The report path can be overridden with `--out`:

```bash
vendor/bin/behat --format=progress --out=std --format=cucumber --out=build/cucumber.json
```

## One report per suite

When the output path is a directory (ending with a slash, or an existing directory), a separate `<prefix><suite>.json` report is written at the end of each suite. Suites without any scenario produce no report.

```php
$profile = (new Profile('default'))
    ->withExtension(new Extension(CucumberExtension::class, [
        'output_path' => '%paths.base%/var/behat/',
        'prefix' => 'cucumber-',
    ]))
    ->withFormatter(new ProgressFormatter())
    ->withFormatter(new Formatter('cucumber'));

foreach (['smoke', 'regression'] as $tag) {
    $profile->withSuite(
        (new Suite($tag))
            ->withPaths('%paths.base%/features')
            ->withContexts(FeatureContext::class)
            ->withFilter(new TagFilter('@'.$tag)),
    );
}
```

This writes `var/behat/cucumber-smoke.json` and `var/behat/cucumber-regression.json` in a single run. Without any declared suite, the report is `default.json`. A directory also works with `--out`:

```bash
vendor/bin/behat --format=progress --out=std --format=cucumber --out=build/reports/
```

Characters other than letters, digits, `.`, `-` and `_` in file names are replaced by `-`. A scenario matched by several suites is run, and reported, once per suite.

## GitLab CI example

```yaml
behat:
    script:
        - vendor/bin/behat --format=progress --out=std --format=cucumber
    artifacts:
        when: always
        paths:
            - var/behat/cucumber.json
```

## Development

```bash
composer install
composer test
composer phpstan
composer cs
```

`composer test:coverage` prints a code coverage report and requires PCOV or Xdebug.

With Docker (PHP 8.4 by default, `PHP_VERSION=8.5` to change it, PCOV included):

```bash
docker compose build
docker compose run --rm php composer install
docker compose run --rm php composer test
docker compose run --rm php composer test:coverage
docker compose run --rm php composer phpstan
docker compose run --rm php composer cs
```

Behind a proxy intercepting HTTPS, pass its CA certificate (PEM) to the build: `CA_CERT=/path/to/ca.pem docker compose build`.

## License

MIT, see [LICENSE](LICENSE).
