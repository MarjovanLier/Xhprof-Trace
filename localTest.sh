#!/usr/bin/env bash

set -euo pipefail


docker build -t php-xprof-trace-composer-81 -f docker/Dockerfile81 .
docker build -t php-xprof-trace-composer-82 -f docker/Dockerfile82 .
docker build -t php-xprof-trace-composer-83 -f docker/Dockerfile83 .

mkdir -p "$HOME"/.composer/docker-cache

for version in 81 82 83; do
  DOCKER_CMD="docker run -it --rm -v $(pwd):/var/www -v $HOME/.composer/docker-cache:/root/.composer php-xprof-trace-composer-$version"
  rm -f composer.lock && \
  $DOCKER_CMD composer --version && \
  $DOCKER_CMD composer install && \
  $DOCKER_CMD composer test:vulnerabilities-check && \
  $DOCKER_CMD composer test:lint && \
  $DOCKER_CMD composer test:code-style && \
  $DOCKER_CMD composer test:phpmd && \
  $DOCKER_CMD composer test:phpunit
done

echo "All tests passed successfully!"
