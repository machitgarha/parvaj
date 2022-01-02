<?php

namespace MAChitgarha\Parvaj\File\ContentGenerator;

use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;
use Twig\Environment as TwigEnvironment;

abstract class AbstractEntityContent
{
    private const TEMPLATES_PATH =
        __DIR__ . '/../../../templates/entity-creation';

    private string $entityName;
    private string $architectureName;

    public function __construct(
        string $entityName,
        string $architectureName
    ) {
        $this->entityName = $entityName;
        $this->architectureName = $architectureName;
    }

    public function generate(): string
    {
        return $this->renderTemplate(
            $this->prepareTwig()
        );
    }

    abstract protected function getTemplateFileName(): string;

    private function prepareTwig(): TwigEnvironment
    {
        return new TwigEnvironment(
            new TwigFilesystemLoader(self::TEMPLATES_PATH)
        );
    }

    protected function renderTemplate(TwigEnvironment $twig): string
    {
        return $twig->render($this->getTemplateFileName(), [
            'entity_name' => $this->entityName,
            'architecture_name' => $this->architectureName,
        ]);
    }
}
