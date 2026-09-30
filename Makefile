DOCKER=docker compose
DC_RUN=$(DOCKER) run --rm php

build:
	$(DOCKER) build --pull --no-cache
	$(DC_RUN) composer install --no-interaction --no-progress --prefer-dist

phpstan:
	$(DC_RUN) vendor/bin/phpstan

rector:
	$(DC_RUN) vendor/bin/rector --dry-run

phpcs:
	$(DC_RUN) vendor/bin/php-cs-fixer fix

phpunit:
	$(DC_RUN) vendor/bin/phpunit

coverage:
	$(DC_RUN) vendor/bin/phpunit --coverage-text

quality: phpstan rector phpcs

