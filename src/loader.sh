#!/bin/bash

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

if [ -f "./restart" ]; then
    rm "./restart"
fi

tail -n +37 ./start > "$(pwd -P)/start-tmp"
"$RUN_FROM" -f "$(pwd -P)/start-tmp" "$@" 2> >(grep -v "no version information available" 1>&2)

while [ -f "./restart" ]
do
    rm "./restart"
    tail -n +37 ./start > "$(pwd -P)/start-tmp" 2> >(grep -v "no version information available" 1>&2)
    "$RUN_FROM" -f "$(pwd -P)/start-tmp" "$@"
done

exit;