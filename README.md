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
<?php // ./behat.php

use SiluniLabs\BehatCucumberFormatter\CucumberExtension;

$profile = new Profile('default')
    ->withExtension(new Extension(CucumberExtension::class))
    ->withFormatter(new PrettyFormatter())
    ->withFormatter(new Formatter('cucumber')
    // outputPath is optional and handled directly by Behat (default : '%paths.base%' )
    ->withOutputPath('./var/behat'))
;

# optional : create "%outputPath%/tag-001.json"
# if no suite are set, create "%outputPath%/default.json" (all suite)
$profile->withSuite(new Suite("TAG-001")
    ->withPaths('%paths.base%/features')
    ->withContexts(FeatureContext::class)
    ->withFilter(new TagFilter('@TAG-999')),
);

return new Config()->withProfile($profile);
```

## Usage

```bash
vendor/bin/behat --format=cucumber
```

## GitLab CI example

```yaml
behat:
    script:
        - vendor/bin/behat --format=progress --out=std --format=cucumber
    artifacts:
        when: always
        paths:
            - var/behat/default.json
```

## Development kit

```bash
# make build 
# composer install
docker compose build
docker compose run --rm php composer install

# make phpstan rector phpcs / make quality
# composer phpstan / composer rector / composer cs
docker compose run --rm php vendor/bin/phpstan
docker compose run --rm php vendor/bin/rector --dry-run
docker compose run --rm php vendor/bin/php-cs-fixer fix

# make phpunit / make coverage
# composer test / composer test:coverage
docker compose run --rm php vendor/bin/phpunit
docker compose run --rm php vendor/bin/phpunit --coverage-text
```

With Docker (PHP 8.4 by default, `PHP_VERSION=8.5` to change it, PCOV included):
```bash
docker compose build --build-arg PHP_VERSION=8.5
```
