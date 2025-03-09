#!/usr/bin/env bash

docker run -it --rm \
        --name my-running-script \
        -v "$PWD":/usr/src/myapp \
        -w /usr/src/myapp \
        php:8.2-cli php "$@"
