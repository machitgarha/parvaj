FROM fedora:28

WORKDIR /root

# Invoking AppImage directly does not work as expected, because of missing FUSE2
ENV APPIMAGE_EXTRACT_AND_RUN 1

ENV parvajRootPath ""
ENV phpSourcePath ""
ENV appimagetool ""

COPY . .

ENTRYPOINT \
    "./$parvajRootPath/build-aux/appimage/install-php-build-prerequisites.sh" \
    && "./$parvajRootPath/build-aux/appimage/build.sh" \
        "$parvajRootPath" "$phpSourcePath" "$appimagetool"
