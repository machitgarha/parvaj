# Parvaj

Automation scripts helping you working with GHDL and GtkWave on a VHDL code. You can create main entities, make tests for them and test them automagically.

## Why?

-   Automate the process of simulation (i.e. compilation) and testing. Powerfully. It should drastically increase development speed.

-   It forces everyone in the project to implicitly follow the same standards (However, changing the standards is not possible for now, e.g. naming conventions).

## Requirements

You must have the following stuff installed:

-   PHP 7.4+
-   Composer
-   GHDL
-   GtkWave

## Getting It

The easiest way to include it in your project is to add it as a Git submodule:

```bash
git submodule add https://github.com/machitgarha/parvaj scripts/
```

Then, install the required dependencies:

```bash
cd scripts
composer install
```

That's it!

**Note**: Using it as a Composer package is not supported (and would not supported).

## How to Use It?

There are two scripts for two different purposes:

1.  `create-entity.php`: Creates entities in a standard way. There are two types: Source entities and unit-test ones (their name suggest what they are).

2.  `start-unit-test.php`: Unit-tests a specific entity, with the help of a unit-test entity. It first invokes GHDL to make things ready (i.e. auto-analysis, elaborate and run the requested entity and the required ones), and then represents the results in GtkWave.

For running a script, do:

```bash
# You can omit php if you're on Linux
php ./scripts/create-entity.php <...args>
```

### Usage Guide

For knowing how a script works or what arguments it gets, you can use the help command included in both scripts. For example:

```bash
$ php ./scripts/create-entity.php help

Usage:
    ./scripts/create-entity.php <entity-type> <entity-name> <group-name> [<architecture-name>]

...
```

Unfortunately, there is no documentation available for these scripts, except the helps themselves (which should be enough in most cases).

## Platform Support

Everything should work fine on Linux-based platforms, and generally Unix-like ones (e.g. OS X). Even further, the code will probably run on Windows as well without any problems, but not properly tested.

## ToDo

-   Make it a lightweight Symfony application.
