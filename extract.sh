#!/bin/bash

cd -P -- "$(dirname -- "$0")"

tar -xvf ./static.tar.gz

chmod +x ./start