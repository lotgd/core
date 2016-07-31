#!/bin/bash -ex
phpunit
./vendor/bin/phpdoccheck -d src --no-ansi
