#!/bin/sh

# This script runs on Fedora particularly, so use docker to run on other Linux
# distributions as well

# Don't use sudo for environments without it (like Docker)
if command -v sudo &> /dev/null; then
    sudo="sudo"
else
    sudo=""
fi

$sudo dnf install -y \
    autoconf make gcc gcc-c++ bison re2c \
    libxml2-devel libicu-devel oniguruma-devel
