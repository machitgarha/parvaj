# Parvaj

![Version](https://img.shields.io/github/v/tag/machitgarha/parvaj?color=purple&label=Version&style=flat-square)

Easy and semi-intelligent VHDL simulation tool, integrating GHDL and GTKWave.

## Features

-   **Easy:**

    -   💡 Zero-configuration by default. Go ahead and simulate your design!

    -   ☕ Easy to use. You don't need to remember or look for dependencies everytime, Parvaj does this for you. You wouldn't even need to know most of the GHDL command-line options.

    <!-- TODO: Add this when AppImage is provided:
    -   ☔ Simple installation process, by providing Phar files.
    -->

-   **Fast:**

    -   ⛽ Speed up your development. Don't get your hands dirty by invoking GHDL and GTKWave manually, use the simple `simulate` command instead to do all the steps for you.

    -   ⚡ The tool itself is designed to have good performance. It uses a proper cache mechanism for this. Although it uses regex patterns for major tasks, thanks to fast PHP regex engine, you wouldn't notice slowdowns.

-   **Semi-intelligent:**

    -   🔥 Automatic detection of dependencies. Forget about what depends on what.

    -   🧠 After finding where a unit (e.g. entity) leaves, it remembers it. Don't worry, it is smart enough to check if it was moved around or has been renamed. Just use it!

## Let's Install!

There are three methods to install Parvaj: Use the AppImage bundle, use the Phar file, or include it as Git submodule. We only cover the preferred method here. For other methods and why you should choose each, please refer to [installation methods](docs/en/installation.md).

### Use the AppImage Bundle

### Requirements

-   GHDL
-   GTKWave

Having a Linux distribution, installing these should be easy. On Fedora 35, for example, you could simply do:

```bash
sudo dnf install ghdl gtkwave
```

### Getting It

1.  Download [latest Parvaj AppImage](https://github.com/machitgarha/parvaj/releases/download/latest/parvaj-x86_64.AppImage).

    ```bash
    wget https://github.com/machitgarha/parvaj/releases/download/latest/parvaj-x86_64.AppImage
    ```

    **Note:** The AppImage does not provide a GUI, so double-clicking it does nothing.

1.  Make it executable.

    ```bash
    chmod +x parvaj-x86_64.AppImage
    ```

1.  Put it somewhere in your `$PATH`.

    ```bash
    # Supposing ~/.local/bin is in your $PATH
    mv parvaj-x86_64.AppImage ~/.local/bin/parvaj
    ```

1.  Done! Make sure the installation was successful:

    ```bash
    parvaj
    ```

Throughout this document, it is supposed you installed Parvaj using this method.

## How to Use?

The primary Parvaj command is `simulate`. It simulates a test-bench for you, given its name. Yes, it is really that simple!

For example, to simulate a test-bench named `test_multiplexer_2_to_1`, run:

```bash
parvaj simulate test_multiplexer_2_to_1
```

Woah! Got the magic?

Note that, for the `simulate` command to work correctly, you must be in the project root, not one of its sub-paths. It might be annoying for some, but not implemented yet ([#2](https://github.com/machitgarha/parvaj/issues/2)).

### Other Options and the Use of Helps

You may also want to use specific GHDL options, or some options provided by Parvaj. In this case, you could use the `help` command to see a full list of options with their explanations:

```bash
parvaj help simulate
# Or
parvaj simulate --help
```

For example, with `--workdir` option, you can change the working directory (which is `build/` by default):

```bash
parvaj simulate test_multiplexer_2_to_1 --workdir=obj/
```
## Platform Support

Tested platforms include:

-   Fedora 28+
-   Ubuntu 18.04+

Parvaj should work on Linux-based platforms, and also generally Unix-like ones (e.g. OS X, Windows WSL).

It should run on Windows as well, but not properly tested. By the way, it might be hard to install GHDL on Windows than WSL.

## Contributions? Of Course!

Feel free to open an issue or create a pull request. You can also consider the to-do list below.

But hey, contribution can be simpler than that: Recommend Parvaj to your friends, if you liked it!

### Donations

If you live in Iran, you can make donations [here](https://coffeebede.ir/buycoffee/machitgarha). Otherwise, open an issue telling why you cannot donate from the outside. :)

### To-Do

-   Distribute as a completely self-contained AppImage, without the need to install GHDL or GTKWave.

## License

This is free software. The project is licensed under [AGPL3](./LICENSE.md).
