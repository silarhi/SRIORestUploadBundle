#!/bin/bash

export TEST_FILESYSTEM="gaufrette"
vendor/bin/phpunit

export TEST_FILESYSTEM="flysystem"
vendor/bin/phpunit
