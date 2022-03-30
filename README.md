# Parvaj

![Version](https://img.shields.io/github/v/tag/machitgarha/parvaj?color=purple&label=Version&style=flat-square)

Easy and semi-intelligent VHDL simulation tool, integrating GHDL and GTKWave.

## Features

-   **Easy:**

    -   💡 Zero-configuration by default. Go ahead and simulate your design!

    -   ☕ Easy to use. You don't need to remember or look for dependencies everytime, Parvaj does this for you. You wouldn't even need to know most of the GHDL command-line options.

    -   ☔ Simple installation process, by providing Phar files.

-   **Fast:**

    -   ⛽ Speed up your development. Don't get your hands dirty by invoking GHDL and GTKWave manually, use the simple `simulate` command instead to do all the steps for you.

    -   ⚡ The tool itself is designed to have good performance. It uses a proper cache mechanism for this. Although it uses regex patterns for major tasks, thanks to fast PHP regex engine, you wouldn't notice slowdowns.

-   **Semi-intelligent:**

    -   🔥 Automatic detection of dependencies. Forget about what depends on what.

    -   🧠 After finding where a unit (e.g. entity) leaves, it remembers it. Don't worry, it is smart enough to check if it was moved around or has been renamed. Just use it!

## Requirements

You must have the following installed:

-   PHP 7.4+
-   Composer
-   GHDL
-   GtkWave

If you have a Linux distribution, then installing all these should be fairly easy. For example, on Fedora, you could simply do:

```bash
sudo dnf install php composer ghdl gtkwave
```

## Getting It

The easiest way to use it in your project is to add it as a Git submodule:

```bash
git submodule add https://github.com/machitgarha/parvaj scripts/
```

Then, install the required dependencies:

```bash
cd scripts
composer install
```

That's it!

## How to Use It?

Suppose you have it under `scripts` directory. Running Parvaj should be easy:

```bash
./scripts/bin/parvaj --help
```

There are two commands available:

1.  `create-entity`: Create an entity, either a source or a unit-test one (their name suggest what they are), and make some basic (but incomplete) contents in it. Instead of creating entities manually, you must use this command, otherwise the simulation functionality won't work (because it uses rules to find source and unit-test files).

2.  `simulate`: Simulates a specific unit-test entity. It first invokes GHDL to make things ready (i.e. auto-analysis and elab-running), and then represents the results in GtkWave.

### Examples

-   Create a source entity named `multiplexer_2_to_1` in `src/multiplexers/multiplexer-2-to-1.vhd`:

    ```bash
    ./scripts/bin/parvaj create-entity source multiplexer_2_to_1 multiplexers
    ```
-   Runs simulation of the unit-test entity named `test_multiplexer_2_to_1` (by auto-finding the correspending file):

    ```bash
    ./scripts/bin/parvaj simulate test_multiplexer_2_to_1
    ```

### Helps Everywhere!

You can always use `--help` in order to see how a command works:

```bash
./scripts/bin/parvaj simulate --help
```

## Platform Support

Everything should work fine on Linux-based platforms, and generally Unix-like ones (e.g. OS X). The code should run on Windows as well, but not properly tested.

## Contributions? Of Course!

Feel free to open an issue or create a pull request. You can also consider the to-do list below.

But hey, contribution can be simpler than that: Recommend Parvaj to your friends, if you liked it!

### Donations

If you live in Iran, you can make donations [here](https://coffeebede.ir/buycoffee/machitgarha). Otherwise, open an issue telling why you cannot donate from the outside. :)

### To-Do

-   Distribute in other probably-better formats, like Phar or AppImage. With Phar, users would get rid of installing Composer (particularly useful for Windows users). Having an AppImage format could be even better: An average Linux user does not need to install anything, and everything (even GHDL or GtkWave) could be bundled into the package.

## License

This is free software. The project is licensed under [AGPL3](./LICENSE.md).
