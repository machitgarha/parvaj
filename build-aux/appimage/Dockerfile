FROM fedora:28

RUN ./install-php-build-prerequisites.sh

ENV parvajRootPath
ENV phpSourcePath
ENV appimagetool
ENV version

COPY . .

ENTRYPOINT ./$parvajRootPath/build-aux/appimage/build.php \
    "$parvajRootPath" "$phpSourcePath" "$appimagetool" "$version"
