#!/usr/bin/env bash

if [ "$#" -ne 1 ]; then
    echo "Usage: $0 {test|phpstan}" >&2
    exit 2
fi

case "$1" in
    test|phpstan)
        ;;
    *)
        echo "Usage: $0 {test|phpstan}" >&2
        exit 2
        ;;
esac

project_dir="$(cd -- "$(dirname -- "$0")" && pwd)"

exec docker run --rm \
    -v "$project_dir:/app" \
    -w /app \
    jakubboucek/lamp-devstack-php \
    composer run "$1"
