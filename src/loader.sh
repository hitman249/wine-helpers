#!/bin/sh

cd -P -- "$(dirname -- "$0")"

RUN_FROM="./php"

if [ ! -f "$RUN_FROM" ]; then

    wget --no-check-certificate -O php https://raw.githubusercontent.com/hitman249/wine-helpers/master/php

    if [ ! -f "$RUN_FROM" ]; then
        RUN_FROM="php"
    else
        chmod +x "$RUN_FROM"
    fi

else
    chmod +x "$RUN_FROM"
fi

tail -n +27 ./start > "$(pwd -P)/start-tmp"

"$RUN_FROM" -f "$(pwd -P)/start-tmp" "$@"

exit;