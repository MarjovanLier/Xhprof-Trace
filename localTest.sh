#!/usr/bin/env bash

set -euo pipefail

docker build -t php-xprof-trace-composer-80 -f docker/Dockerfile80 .
docker build -t php-xprof-trace-composer-81 -f docker/Dockerfile81 .
docker build -t php-xprof-trace-composer-82 -f docker/Dockerfile82 .
docker build -t php-xprof-trace-composer-83 -f docker/Dockerfile83 .

DOCKER_CMD_80="docker run -it --rm -v $(pwd):/var/www php-xprof-trace-composer-80"
DOCKER_CMD_81="docker run -it --rm -v $(pwd):/var/www php-xprof-trace-composer-81"
DOCKER_CMD_82="docker run -it --rm -v $(pwd):/var/www php-xprof-trace-composer-82"
DOCKER_CMD_83="docker run -it --rm -v $(pwd):/var/www php-xprof-trace-composer-83"

### PHP 8.0
rm -f composer.lock && \
$DOCKER_CMD_80 composer --version && \
$DOCKER_CMD_80 composer install && \
$DOCKER_CMD_80 composer test:vulnerabilities-check && \
$DOCKER_CMD_80 composer test:phpunit && \

### PHP 8.1
rm -f composer.lock && \
$DOCKER_CMD_81 composer --version && \
$DOCKER_CMD_81 composer install && \
$DOCKER_CMD_81 composer test:vulnerabilities-check && \
$DOCKER_CMD_81 composer test:phpunit && \


### PHP 8.2
rm -f composer.lock && \
$DOCKER_CMD_82 composer --version && \
$DOCKER_CMD_82 composer install && \
$DOCKER_CMD_82 composer test:vulnerabilities-check && \
$DOCKER_CMD_82 composer test:phpunit && \


### PHP 8.3
rm -f composer.lock && \
$DOCKER_CMD_83 composer --version && \
$DOCKER_CMD_83 composer install && \
$DOCKER_CMD_83 composer test:vulnerabilities-check && \
$DOCKER_CMD_83 composer test:phpunit && \

echo "All tests passed successfully!"
