#!/bin/sh

# Exit on error
set -e

here="$(realpath "$(dirname "${0}")")"

help="\
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
    -v, --version           The version name of the AppImage, used in the final
                            AppImage name. It may be a tag representing a
                            version (e.g. 0.2.0), or other specifiers like
                            'prerelease' or 'nightly'.
"

phpConfigureOptions="--disable-fileinfo --disable-phar --disable-session \
    --disable-cgi --disable-pdo --disable-simplexml --disable-xmlreader \
    --disable-xmlwriter --enable-phpdbg=no --without-sqlite3 --enable-mbstring \
    --with-zlib=static --enable-pcntl"
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
    echo "---------------------------------------------------------------------"
    echo
}

buildPhp() {
    phpSourcePath="$1"
    installationPrefix="$2"

    cd "$phpSourcePath"

    echoSection "Configuring..."
    ./buildconf --force
    ./configure --prefix="$installationPrefix" $phpConfigureOptions

    echoSection "Making..."
    make -j "$phpMakeJobsCount"

    echoSection "Installing..."
    make install

    echoSection "Preparing INI..."
    iniPath="$installationPrefix/lib/php.ini"
    cp ./php.ini-development "$iniPath"
    customizePhpIni "$iniPath"

    echoSection "Bundling PHP shared libs..."
    bundlePhpSharedLibraries "$appDir"

    echoSection "Cleaning up..."
    minimizePhpInstallationSize "$installationPrefix"

    cd -
}

customizePhpIni() {
    iniPath="$1"

    echo "$phpIniCustomSettings" >> "$iniPath"
}

minimizePhpInstallationSize() {
    installationPrefix="$1"

    rm -rf "$installationPrefix/php/man"
    rm -rf "$installationPrefix/include/php"
    rm -rf "$installationPrefix/lib/php/build"

    strip -s "$installationPrefix/bin/php"
    strip -s "$installationPrefix/lib/php/extensions"/**/*.so
}

bundlePhpSharedLibraries() {
    appDir="$1"

    mkdir -p "$appDir/usr/lib64"

    # Extract all libraries, which came after an arrow in ldd output
    lddOutput="$(ldd "$appDir/usr/bin/php")"

    # For debugging purposes
    echo "ldd of PHP binary:"
    echo "$lddOutput"

    prevWasArrow=false
    for i in $lddOutput; do
        # Reached the argument after an arrow, so bundle it
        if [[ "$prevWasArrow" = true ]]; then
            # Don't include libc and related libraries
            if ! [[ "$i" =~ lib(m|c|rt|dl|pthread).so ]]; then
                echo "Bundling '$i'..."
                cp "$i" "$appDir/usr/$i"
            fi

            prevWasArrow=false
        fi

        if [[ "$i" = "=>" ]]; then
            prevWasArrow=true
        else
            prevWasArrow=false
        fi
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

    if [ -n "$version" ]; then
        version="$version-"
    fi

    mkdir -p "$resultingAppImageDir"
    cd "$resultingAppImageDir"

    output="parvaj-$version$arch.AppImage"

    ARCH="$arch" "$appimagetool" \
        -u "gh-releases-zsync|machitgarha|parvaj|latest|parvaj-*$arch.AppImage.zsync" \
        "$appDir" "$output"

    # Thanks to https://github.com/AppImage/AppImageKit/issues/828#issuecomment-731895751
    sed "0,/AI\x02/{s|AI\x02|\x00\x00\x00|}" -i "$output"

    cd -
}

# Parse options
options=$(\
    getopt -l "build-directory:,help,skip-php-build,version:" \
        -o "b:hsv:" -- "$@" \
)
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
        -v|--version)
            shift
            version="$1"
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
[ -f "$logFilePath" ] && rm "$logFilePath"

appDir="./AppDir"
mkdir -p "$appDir"
appDir="$(realpath "$appDir")"

if [ "$skipPhpBuild" != true ]; then
    phpInstallationPath="$appDir/usr"

    echoSection "Building PHP..."
    buildPhp "$phpSourcePath" "$phpInstallationPath"
fi

echoSection "Copying Parvaj root to AppDir..."
copyParvajRootToAppDir "$appDir" "$parvajRootPath"

echoSection "Copying assets..."
copyAssets "$appDir"

echoSection "Building AppImage..."
makeAppImage "$appDir" "$appimagetool" "$version"

echoSection "Done!
Look into the build directory for the AppImage!"
