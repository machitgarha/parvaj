<?php

namespace MAChitgarha\Parvaj\Runner\Ghdl;

use MAChitgarha\Parvaj\WaveformType;

use Symfony\Component\Console\Exception\InvalidOptionException;

class ElabRunUserOptions
{
    private string $waveformType;
    private array $simulationOptions;

    public function __construct(
        string $rawWaveformType,
        array $rawSimulationOptions,
    ) {
        $this->waveformType = self::normalizeWaveformType($rawWaveformType);
        $this->simulationOptions = self::normalizeSimulationOptions($rawSimulationOptions);
    }

    private static function normalizeWaveformType(string $rawWaveformType): string
    {
        $rawWaveformType = \strtolower($rawWaveformType);

        return match ($rawWaveformType) {
            "vcd" => WaveformType::VCD,
            "ghw" => WaveformType::GHW,
            default => throw new InvalidOptionException(
                "Invalid waveform type '$rawWaveformType'"
            ),
        };
    }

    private static function normalizeSimulationOptions(array $rawSimulationOptions): array
    {
        $result = [];

        foreach ($rawSimulationOptions as $option) {
            $optionParts = \explode('=', $option, 2);

            $optionName = $optionParts[0];
            $optionValue = $optionParts[1] ?? null;

            $result[$optionName] = $optionValue;
        }

        return $result;
    }

    /**
     * Get waveform type.
     * @return string One of the {@see WaveformType} class constants.
     */
    public function getWaveformType(): string
    {
        return $this->waveformType;
    }

    public function getSimulationOptions(): array
    {
        return $this->simulationOptions;
    }
}
