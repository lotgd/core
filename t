#!/bin/bash -ex
./vendor/bin/phpunit --stop-on-failure
./vendor/bin/php-cs-fixer fix --verbose --dry-run