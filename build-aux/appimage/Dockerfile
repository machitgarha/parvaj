FROM fedora:28

WORKDIR /root

ENV parvajRootPath ""
ENV phpSourcePath ""
ENV appimagetool ""
ENV version ""

COPY . .

ENTRYPOINT \
    "./$parvajRootPath/build-aux/appimage/install-php-build-prerequisites.sh" \
    && "./$parvajRootPath/build-aux/appimage/build.sh" \
        "$parvajRootPath" "$phpSourcePath" "$appimagetool" "$version"
