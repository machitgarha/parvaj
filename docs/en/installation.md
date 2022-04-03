# Installation Methods

## Which One to Use?

The preferred method is to use the AppImage. However, each method has its own pros and cons.

||AppImage|Phar|Git Submodule|
|:-:|:-:|:-:|:-:|
|Easy Installation|✅✅|✅||
|Less Requirements<br/>(Being Self-Contained)|✅✅|✅||
|Less (Download) Size||✅|✅|
|Good Startup Performance||✅|✅|
|Global Installation|✅|✅||
|Easy to Update|✅|✅|✅✅|
|Easy to Use|✅|✅||

### Notes

-   JIT (i.e. Just-In-Time compiler) for PHP is enabled by default in the AppImage. Although you can enable it for the other methods as well.

## Use the AppImage Bundle

See [here](../../README.md#use-the-appimage-bundle) for instructions.

## Use a Phar File

### Requirements

-   PHP 8.0+
-   GHDL
-   GTKWave

### Getting It

1.  Download [Parvaj Phar file](https://github.com/machitgarha/parvaj/releases/latest/download/parvaj.phar).

    ```bash
    wget https://github.com/machitgarha/parvaj/releases/latest/download/parvaj.phar
    ```

1.  Make it executable.

    ```bash
    chmod +x parvaj.phar
    ```

1.  Put it somewhere in your `$PATH`.

    ```bash
    # Supposing ~/.local/bin is in your $PATH
    mv parvaj.phar ~/.local/bin/parvaj
    ```

1.  Done! Make sure the installation was successful:

    ```bash
    parvaj
    ```

## As Git Submodule

### Requirements

-   PHP 8.0+
-   Composer
-   GHDL
-   GTKWave

### Getting It

1.  Add Parvaj as a Git submodule.

    ```bash
    git submodule add https://github.com/machitgarha/parvaj scripts/parvaj
    ```

1.  Install Parvaj dependencies:

    ```bash
    # -d: --working-directory
    composer install -d scripts/parvaj
    ```

1.  Done! To invoke it, run:

    ```bash
    ./scripts/parvaj/bin/parvaj
    ```
