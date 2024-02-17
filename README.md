# Parvaj

[![Version](https://img.shields.io/github/v/release/machitgarha/parvaj?color=darkgreen&label=Version&style=flat-square)](https://github.com/machitgarha/parvaj/releases) [![Available As AppImage](https://img.shields.io/badge/Available%20As-AppImage-lawngreen?style=flat-square)](https://github.com/machitgarha/parvaj/releases/latest/download/parvaj-x86_64.AppImage) [![Available As Phar](https://img.shields.io/badge/Available%20As-Phar-lawngreen?style=flat-square)](https://github.com/machitgarha/parvaj/releases/latest/download/parvaj.phar) [![Available At AUR package](https://img.shields.io/badge/Available%20At-AUR-lawngreen?style=flat-square)](https://aur.archlinux.org/packages/parvaj-bin)

Easy and fast (both in the sense of performance and development speed) VHDL simulation tool, integrating GHDL and GTKWave.

## Features

-   **Easy:**

    -   💡 Zero-configuration by default. Go ahead and simulate your design!

    -   ☕ Easy to use. You don't need to remember or look for dependencies everytime, Parvaj does it for you. You wouldn't even need to know most of the GHDL command-line options.

    -   ☔ Simple installation process, by providing AppImage and Phar files.

-   **Fast:**

    -   ⛽ Speed up your development. Don't get your hands dirty by invoking GHDL and GTKWave manually; use the simple `simulate` command instead to do all the steps for you.

    -   ⚡ The tool itself is designed to have good performance. It uses a proper cache mechanism for this. Although it uses regex patterns for major tasks, thanks to fast PHP regex engine, you wouldn't notice slowdowns.

-   **Semi-intelligent:**

    -   🔥 Automatic detection of dependencies. Forget about what depends on what.

    -   🧠 After finding where a unit (e.g. entity) lives, Parvaj remembers it. Don't worry, it is smart enough to check if it was moved around or was renamed. Just use it!

## Let's Install!

**Note:** Currently, we only cover and support Linux distributions (feel free to add support for other platforms as well).

There are four methods to install Parvaj:

-   [Use the AppImage bundle (recommended)](#use-the-appimage-bundle)
-   [Use the Phar file](docs/en/installation.md#use-the-phar-file)
-   [Via package manager](docs/en/installation.md#via-package-manager) (Currently Arch-based only)
-   [As Git submodule (deprecated)](docs/en/installation.md#as-git-submodule)

In doubt? See [Which one to use?](docs/en/installations.md#which-one-to-use).

## Use the AppImage Bundle

### Requirements

-   GHDL
-   GTKWave

Having a Linux distribution, installing these should be easy:

<details>

<summary>Fedora-based</summary>

```bash
sudo dnf install ghdl gtkwave
```

</details>

<details>

<summary>Arch-based</summary>

<br/>

GTKWave can be installed through Pacman and GHDL through [AUR](https://aur.archlinux.org/packages/ghdl-gcc-git):

```bash
sudo pacman -S gtkwave
yay -S ghdl-gcc-git
```

</details>

<details>

<summary>Debian-based (e.g. Ubuntu)</summary>

```bash
sudo apt install ghdl gtkwave
```

</details>

### Getting It

1.  Download [latest Parvaj AppImage](https://github.com/machitgarha/parvaj/releases/latest/download/parvaj-x86_64.AppImage).

    ```bash
    wget https://github.com/machitgarha/parvaj/releases/latest/download/parvaj-x86_64.AppImage
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

For example, to simulate a test-bench named `test_multiplexer_2_to_1` (note that it's the name of the test-bench, not its file path), run:

```bash
# Or even shorter:
parvaj s test_multiplexer_2_to_1
```


Note that, for the `simulate` command to work, you must be in the project root, not one of its sub-paths. It might be annoying for some, but not implemented yet ([#2](https://github.com/machitgarha/parvaj/issues/2)).

### Options

You may also want to use some of the [GHDL's simulation options](https://ghdl.github.io/ghdl/using/Simulation.html#simulation-options), or the options provided by Parvaj. You can use the command `help` to see the list of available options:

```bash
parvaj help simulate
# Or:
parvaj simulate --help
```

#### Examples

-   With `--workdir`, you can change the working directory (which is `build/` by default):

    ```bash
    parvaj simulate test_multiplexer_2_to_1 --workdir=obj/
    # The order does not matter:
    parvaj simulate --workdir=obj/ test_multiplexer_2_to_1
    ```

-   With `--option` or `-o`, you may pass arbitrary simulation options to GHDL:

    ```bash
    parvaj simulate test_clock_generator -o stop-time=3ns -o vcd-nodate
    ```

    **Hint:** `stop-time` option is useful when your test-bench doesn't end in a finite period of time and could be run infinitely. In this case, you must inform GHDL to limit the simulation time to a specific period, e.g. 3ns; otherwise, the simulation (i.e. elab-running phase) will never stop.

### Other Commands

Although Parvaj is designed to work mostly config-free, you can configure a few things using the `config` command:

-   `gtkwave.cmdline`: If set, this command is used to run GTKWave. This is useful if you want to use a different application for viewing waveforms, or having problems with the default invocation command.

    For instance, on MacOS, you can set it to `open`.

-   `ghdl.version`: GHDL version should be auto-detected, but this sets its major version.

#### Example

Some MacOS users cannot invoke GTKWave directly from the command-line using `gtkwave` command. In this case, the fix is to use `open` command.

You can set it like the following:

```bash
parvaj config gtkwave.cmdline open
```

Want to make sure it was set?

```bash
parvaj config gtkwave.cmdline
# Output: open
```

Want to unset it (i.e. reset it to the default value)?

```bash
parvaj config gtkwave.cmdline ""
```

## Platform Support

Tested platforms include:

-   Fedora 28+
-   Ubuntu 18.04+

Parvaj should work on Linux-based platforms, and also generally Unix-like ones (e.g. OS X, Windows WSL).

It should run on Windows as well, but not properly tested. By the way, it might be harder to install GHDL on Windows than WSL.

## Contributions? Of Course!

Feel free to open an issue or create a pull request. You can also consider the to-do list below.

But hey, contribution can be simpler than that: Recommend Parvaj to your friends, if you liked it!

### Donations

If you live in Iran, you can make donations [here](https://coffeebede.ir/buycoffee/machitgarha). Otherwise, open an issue telling why you cannot donate from the outside. :)

## License

[![AGPL 3.0](https://www.gnu.org/graphics/agplv3-155x51.png)](./LICENSE.md)
