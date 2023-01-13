<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Api;

class CodeGenerator
{
    /**
     * avoided numbers: 0, 1
     * avoided letters B,G,I,O,Q,S,Z.
     *
     * @var string
     */
    private const CHARS = '23456789ACDEFHJKLMNPRSTUVWXY';

    /**
     * Generate a random string of non-ambiguous numbers and letters.
     */
    public static function generate(?int $length = 5): string
    {
        $random = '';

        for ($i = 0; $i < $length; ++$i) {
            $random .= self::randomChar();
        }

        return $random;
    }

    private static function randomChar(): string
    {
        $index = mt_rand(0, strlen(self::CHARS) - 1);

        return (string) self::CHARS[$index];
    }
}
