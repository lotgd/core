#!/bin/bash -ex
phpunit --stop-on-failure
./vendor/bin/phpdoccheck -d src --no-ansi
