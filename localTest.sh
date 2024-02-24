#!/usr/bin/env bash

set -euo pipefail


docker build -t php-xprof-trace-composer-81 -f docker/Dockerfile81 .
docker build -t php-xprof-trace-composer-82 -f docker/Dockerfile82 .
docker build -t php-xprof-trace-composer-83 -f docker/Dockerfile83 .

mkdir -p "$HOME"/.composer/docker-cache

DOCKER_CMD_81="docker run -it --rm -v $(pwd):/var/www -v $HOME/.composer/docker-cache:/root/.composer php-xprof-trace-composer-81"
DOCKER_CMD_82="docker run -it --rm -v $(pwd):/var/www -v $HOME/.composer/docker-cache:/root/.composer php-xprof-trace-composer-82"
DOCKER_CMD_83="docker run -it --rm -v $(pwd):/var/www -v $HOME/.composer/docker-cache:/root/.composer php-xprof-trace-composer-83"

### PHP 8.1
rm -f composer.lock && \
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
