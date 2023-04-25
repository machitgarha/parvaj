<?php

namespace MAChitgarha\Parvaj\Runner;

class OptionBuilder
{
    public static function build(array $associativeOptions): array
    {
        $result = [];

        foreach ($associativeOptions as $key => $value) {
            if (\strlen($key) === 1) {
                $result[] = "-$key";
                if (isset($value)) {
                    $result[] = "$value";
                }
            } else {
                $result[] = "--$key" . (isset($value) ? "=$value" : "");
            }
        }

        return $result;
    }
}
