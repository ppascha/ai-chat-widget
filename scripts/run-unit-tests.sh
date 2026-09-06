#!/bin/sh
set -eu

docker build -f Dockerfile.tests -t ai-widget-wp-plugin-unit-tests .
mkdir -p build/coverage
docker run --rm \
	-v "$(pwd)/build:/app/build" \
	--entrypoint php \
	ai-widget-wp-plugin-unit-tests \
	vendor/bin/phpunit \
	--configuration phpunit.xml.dist \
	--testdox \
	--coverage-text \
	--coverage-clover build/coverage/clover.xml \
	--coverage-filter includes
