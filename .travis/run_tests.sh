#!/bin/bash

# Check modified PHP files with PHP's internal syntax checker
git diff --name-only --diff-filter=ACMRTUXB HEAD^ | grep '\.php$' | xargs -r -n 1 php -l || exit 1

# Run test suite
vendor/bin/phpunit --coverage-clover=coverage.xml tests/DoctrineTestSuite1.php
