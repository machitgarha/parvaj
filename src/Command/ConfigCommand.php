<?php

namespace MAChitgarha\Parvaj\Command;

use MAChitgarha\Parvaj\Config;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigCommand extends Command
{
    protected const NAME = "config";
    protected const DESCRIPTION = <<<DESCRIPTION
        Get or set global options.
        DESCRIPTION;

    protected const ARG_NAME_NAME = "name";
    protected const ARG_NAME_DESCRIPTION =
        "The name of the option.";

    protected const ARG_VALUE_NAME = "value";
    protected const ARG_VALUE_DESCRIPTION =
        "The value to be set for the option.";

    protected function configure()
    {
        // Metadata
        $this
            ->setName(static::NAME)
            ->setDescription(static::DESCRIPTION)
        ;

        // Arguments and options
        $this
            ->addArgument(
                static::ARG_NAME_NAME,
                InputArgument::REQUIRED,
                static::ARG_NAME_DESCRIPTION,
            )
            ->addArgument(
                static::ARG_VALUE_NAME,
                InputArgument::OPTIONAL,
                static::ARG_VALUE_DESCRIPTION,
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument(static::ARG_NAME_NAME);
        $value = $input->getArgument(static::ARG_VALUE_NAME);

        if (!Config::isKeyValid($name)) {
            throw new InvalidArgumentException("Invalid option name '$name'");
        }

        $config = new Config();
        if (!isset($value)) {
            $output->writeln($config->get($name));
        } else {
            $config->set($name, $value);
        }

        return 0;
    }
}
