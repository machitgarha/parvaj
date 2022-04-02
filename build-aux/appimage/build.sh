#!/bin/sh

# Exit on error
set -e

here="$(realpath "$(dirname "${0}")")"

help="\
Builds Parvaj AppImage bundle.

Usage:
    ./build.sh parvaj-root php-src appimagetool version [options]

Arguments:
    parvaj-root     Path to Parvaj root directory, with installed Composer
                    dependencies.
    php-src         Path to PHP source directory, either as a Git
                    repository or an extracted tarball.
    appimagetool    Path to appimagetool binary.
    version         The version name of the AppImage, used in the final AppImage
                    name. It may be a tag representing a version (e.g. 0.2.0),
                    or other specifiers like 'prerelease' or 'nightly'.

Options:
    -b, --build-directory   Path to build directory.
    -h, --help              Show this help.
    -s, --skip-php-build    Do not re-build PHP.
"

phpConfigureOptions="--disable-fileinfo --disable-phar --disable-session \
    --disable-cgi --disable-pdo --disable-simplexml --disable-xmlreader \
    --disable-xmlwriter --enable-phpdbg=no --without-sqlite3 --enable-mbstring \
    --with-zlib=static --enable-intl --enable-pcntl"
phpMakeJobsCount="4"
phpIniCustomSettings="
[PHP]
zend_extension=opcache

[opcache]
opcache.enable=1
opcache.enable_cli=1

# Enable tracing JIT
opcache.jit_buffer_size=128M
"

arch="x86_64"

buildDir="$here/../../build"
resultingAppImageDir="$buildDir/appimage"
skipPhpBuild=false

showHelp() {
    echo "$help"
}

echoSection() {
    echo
    echo "---------------------------------------------------------------------"
    echo "$@"
}

buildPhp() {
    phpSourcePath="$1"
    installationPrefix="$2"

    cd "$phpSourcePath"

    echoSection " Configuring..."
    ./buildconf --force
    ./configure --prefix="$installationPrefix" $phpConfigureOptions

    echoSection " Making..."
    make -j "$phpMakeJobsCount"

    echoSection " Installing..."
    make install

    echoSection " Preparing INI..."
    iniPath="$installationPrefix/lib/php.ini"
    cp ./php.ini-development "$iniPath"
    customizePhpIni "$iniPath"

    cd -
}

customizePhpIni() {
    iniPath="$1"

    echo "$phpIniCustomSettings" >> "$iniPath"
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
    version="$3"

    mkdir -p "$resultingAppImageDir"
    cd "$resultingAppImageDir"

    ARCH="$arch" "$appimagetool" \
        -u "gh-releases-zsync|machitgarha|parvaj|latest|parvaj-*-$arch.AppImage.zsync" \
        "$appDir" "parvaj-$version-$arch.AppImage"

    cd -
}


# Parse options
options=$(getopt -l "build-directory:,help,skip-php-build" -o "b:hs" -- "$@")
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

if [[ $# -lt 4 ]]; then
    echo "Too few arguments. See --help for more information."
    exit 1
fi

parvajRootPath="$(realpath "$1")"
phpSourcePath="$(realpath "$2")"
appimagetool="$(realpath "$3")"
version="$4"

mkdir -p "$buildDir"
cd "$buildDir"

# Clean the previous log
[ -f "$logFilePath" ] && rm "$logFilePath"

appDir="./AppDir"
mkdir -p "$appDir"
appDir="$(realpath "$appDir")"

if [ "$skipPhpBuild" != true ]; then
    phpInstallationPath="$appDir/usr"

    echoSection "Building PHP..."
    buildPhp "$phpSourcePath" "$phpInstallationPath"

    echoSection "Bundling PHP shared libs..."
    bundlePhpSharedLibraries "$appDir"
fi

echoSection "Copying Parvaj root to AppDir..."
copyParvajRootToAppDir "$appDir" "$parvajRootPath"

echoSection "Copying assets..."
copyAssets "$appDir"

echoSection "Building AppImage..."
makeAppImage "$appDir" "$appimagetool" "$version"

echoSection "Done!
Look into the build directory for the AppImage!"
