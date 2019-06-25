#!/bin/bash -ex
./vendor/bin/phpunit --stop-on-failure
./vendor/bin/phpcs src
#./vendor/bin/phpdoccheck -d src --no-ansi
