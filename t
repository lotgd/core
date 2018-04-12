#!/bin/bash -ex
./vendor/bin/phpunit --stop-on-failure
./vendor/bin/phpdoccheck -d src --no-ansi
