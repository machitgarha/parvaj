# Installation Methods

## Use a Phar File

See [here](../../README.md#use-a-phar-file).

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
