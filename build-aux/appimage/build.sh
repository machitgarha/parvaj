#!/bin/sh

# Exit on error
set -e

here="$(realpath "$(dirname "${0}")")"

arch="x86_64"
phpConfigureOptions="--disable-fileinfo --disable-phar --disable-session \
    --disable-cgi --disable-pdo --disable-simplexml --disable-xmlreader \
    --disable-xmlwriter --enable-phpdbg=no --without-sqlite3 --enable-mbstring \
    --with-zlib=static --enable-intl --enable-pcntl"
phpMakeJobsCount="4"
buildDir="build/"
skipPhpBuild=false
logFile="appimage-build.log"

options=$(getopt -l "build-directory:,help,skip-php-build" -o "b:hs" -- "$@")

showHelp() {
    echo "\
Builds Parvaj AppImage bundle.

Usage:
    ./build.sh parvaj-root php-src appimagetool [options]

Arguments:
    parvaj-root     Path to Parvaj root directory, with installed Composer
                    dependencies.
    php-src         Path to PHP source directory, either as a Git
                    repository or an extracted tarball.
    appimagetool    Path to appimagetool binary.

Options:
    -b, --build-directory   Path to build directory.
    -h, --help              Show this help.
    -s, --skip-php-build    Do not re-build PHP.
"
}

buildPhp() {
    previousPath="$(pwd)"

    phpSourcePath="$1"
    installationPrefix="$2"

    cd "$phpSourcePath"

    echo " Configuring..."
    ./buildconf --force >> "$logFile" 2>&1
    ./configure --prefix="$installationPrefix" $phpConfigureOptions >> "$logFile" 2>&1

    echo " Making..."
    make -j "$phpMakeJobsCount" >> "$logFile" 2>&1

    echo " Installing..."
    make install >> "$logFile" 2>&1

    cd "$previousPath"
}

bundlePhpSharedLibraries() {
    appDir="$1"

    mkdir -p "$appDir/usr/lib64"

    # 64-bit libraries
    for i in \
        libcrypt.so.2 libstdc++.so.6 libxml2.so.2 libz.so.1 libicuio.so.69 \
        libicui18n.so.69 libicuuc.so.69 libicudata.so.69 libonig.so.5 \
        libgcc_s.so.1 liblzma.so.5 \
    ; do
        cp "/lib64/$i" "$appDir/usr/lib64/$i"
    done
}

copyParvajRootToAppDir() {
    appDir="$1"
    parvajRootPath="$2"
    appDirParvajPath="$appDir/parvaj"

    mkdir -p "$appDirParvajPath"

    cp -r "$parvajRootPath"/{bin,docs,src,vendor,composer.json,LICENSE.md} \
        "$appDirParvajPath"
}

copyAssets() {
    appDir="$1"

    cp "$here/assets"/* "$appDir"

    chmod +x "$appDir/AppRun"
}

makeAppImage() {
    appDir="$1"
    appimagetool="$2"

    ARCH="$arch" "$appimagetool" "$appDir" >> "$logFile" 2>&1
}

# Parse options
eval set -- "$options"

while true; do
    case "$1" in
        -b|--build-directory)
            shift
            buildDir="$1"
            ;;
        -h|--help)
            showHelp
            exit
            ;;
        -s|--skip-php-build)
            skipPhpBuild=true
            ;;
        --)
            shift
            break
            ;;
    esac
    shift
done

if [[ $# -lt 3 ]]; then
    echo "Too few arguments. See --help for more information."
    exit 1
fi

parvajRootPath="$(realpath "$1")"
phpSourcePath="$(realpath "$2")"
appimagetool="$(realpath "$3")"

mkdir -p "$buildDir"
cd "$buildDir"

# Clean the previous log
[ -f "$logFile" ] && rm "$logFile"

appDir="./AppDir"
mkdir -p "$appDir"
appDir="$(realpath "$appDir")"

if [ "$skipPhpBuild" != true ]; then
    echo "Building PHP..."
    buildPhp "$phpSourcePath" "$appDir/usr"

    echo "Bundling PHP shared libs..."
    bundlePhpSharedLibraries "$appDir"
fi

echo "Copying Parvaj root to AppDir..."
copyParvajRootToAppDir "$appDir" "$parvajRootPath"

echo "Copying assets..."
copyAssets "$appDir"

echo "Building AppImage..."
makeAppImage "$appDir" "$appimagetool"

echo
echo "Done!"
echo "Look into the build directory for the AppImage!"
