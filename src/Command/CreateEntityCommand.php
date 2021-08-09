<?php

namespace MAChitgarha\Parvaj\Command;

use MAChitgarha\Parvaj\SourceEntityFileCreator;
use MAChitgarha\Parvaj\UnitTestEntityFileCreator;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateEntityCommand extends Command
{
    protected const NAME = 'create-entity';
    protected const DESCRIPTION = <<<'DESCRIPTION'
        Creates a VHDL entity file.
        DESCRIPTION;
    // TODO: Add an example
    protected const HELP = <<<'HELP'
        Generates a VHDL entity file.

        Currently, there are two types of files:

        Source files:
          A VHDL file including one entity providing a functionality for the
          program. It will be created in the src/ directory.

        Unit-test (aka test-bench) files:
          A VHDL file including one entity for testing a particular source
          entity. It will be located in the tests/unit/ directory.

        The resulting entity file will be created in a subdirectory, known as
        group. The purpose of the groups is to categorize entities into
        different directories. Take groups here like namespaces in programming
        languages, specifically Python.

        The entity filename is the hyphen-styled version of the entity name,
        extended by .vhd extension. The recommended convension for the entity
        name is snake_case.
        HELP;

    protected const ARG_ENTITY_TYPE_NAME = 'entity-type';
    protected const ARG_ENTITY_TYPE_DESCRIPTION =
        'The entity type. Either source or unit-test.';

    protected const ARG_ENTITY_NAME_NAME = 'entity-name';
    protected const ARG_ENTITY_NAME_DESCRIPTION =
        'The name of the entity. The recommended convension for it is ' .
        'snake_case.';

    protected const ARG_GROUP_NAME_NAME = 'group-name';
    protected const ARG_GROUP_NAME_DESCRIPTION =
        'The group name of the entity.';

    protected const ARG_ARCHITECTURE_NAME_NAME = 'architecture-name';
    protected const ARG_ARCHITECTURE_NAME_DESCRIPTION =
        'The name of the architecture in the entity file.';
    protected const ARG_ARCHITECTURE_NAME_DEFAULT = 'structural';

    protected function configure()
    {
        // Metadata
        $this
            ->setName(static::NAME)
            ->setDescription(static::DESCRIPTION)
            ->setHelp(static::HELP)
        ;

        // Arguments and options
        $this
            ->addArgument(
                static::ARG_ENTITY_TYPE_NAME,
                InputArgument::REQUIRED,
                static::ARG_ENTITY_TYPE_DESCRIPTION,
            )
            ->addArgument(
                static::ARG_ENTITY_NAME_NAME,
                InputArgument::REQUIRED,
                static::ARG_ENTITY_NAME_DESCRIPTION,
            )
            ->addArgument(
                static::ARG_GROUP_NAME_NAME,
                InputArgument::REQUIRED,
                static::ARG_GROUP_NAME_DESCRIPTION,
            )
            ->addArgument(
                static::ARG_ARCHITECTURE_NAME_NAME,
                InputArgument::OPTIONAL,
                static::ARG_ARCHITECTURE_NAME_DESCRIPTION,
                static::ARG_ARCHITECTURE_NAME_DEFAULT,
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityFileCreatorClass = $this->getEntityFileCreatorClassName(
            $input->getArgument(static::ARG_ENTITY_TYPE_NAME)
        );

        (new $entityFileCreatorClass(
            $input->getArgument(static::ARG_ENTITY_NAME_NAME),
            $input->getArgument(static::ARG_GROUP_NAME_NAME),
            $input->getArgument(static::ARG_ARCHITECTURE_NAME_NAME)
        ))->write();

        $output->writeln('File created successfully.');

        return 0;
    }

    private function getEntityFileCreatorClassName(?string $entityType): string
    {
        if ($entityType === 'source') {
            return SourceEntityFileCreator::class;
        } elseif ($entityType === 'unit-test') {
            return UnitTestEntityFileCreator::class;
        } else {
            // TODO: Handle with custom exceptions
            throw new \Exception('Wrong entity type');
        }
    }
}
