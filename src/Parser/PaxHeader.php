<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Parser;

use JakubBoucek\Tar\Exception\InvalidArchiveFormatException;
use JakubBoucek\Tar\Exception\NotImplementedException;

use function sprintf;
use function strlen;

use const PHP_INT_MAX;

/** @internal */
class PaxHeader
{
    public const int MaxPayloadSize = 1048576;

    /** @return array{path?: string, size?: int} */
    public static function parse(string $payload, bool $global = false): array
    {
        $total = strlen($payload);
        if ($total === 0) {
            throw new InvalidArchiveFormatException('PAX header must contain at least one record.');
        }
        if ($total > self::MaxPayloadSize) {
            throw new NotImplementedException('PAX payloads larger than 1 MiB are not supported.');
        }

        $values = [];
        $offset = 0;
        while ($offset < $total) {
            $space = strpos($payload, ' ', $offset);
            if ($space === false) {
                throw new InvalidArchiveFormatException('Missing PAX record length separator.');
            }
            $length = self::decimal(substr($payload, $offset, $space - $offset));
            $prefixLength = $space - $offset + 1;
            if ($length < $prefixLength + 3 || $length > $total - $offset) {
                throw new InvalidArchiveFormatException('Invalid PAX record length.');
            }
            if ($payload[$offset + $length - 1] !== "\n") {
                throw new InvalidArchiveFormatException('Missing PAX record final newline.');
            }
            $record = substr($payload, $space + 1, $length - $prefixLength - 1);
            $equals = strpos($record, '=');
            if ($equals === false || $equals === 0) {
                throw new InvalidArchiveFormatException('Missing PAX record keyword or value separator.');
            }
            $key = substr($record, 0, $equals);
            if ($key === 'size' || $key === 'path') {
                if ($global) {
                    throw new NotImplementedException(sprintf('Global PAX keyword "%s" is not supported.', $key));
                }
                $values[$key] = substr($record, $equals + 1);
            }
            $offset += $length;
        }

        $result = [];
        foreach ($values as $key => $value) {
            if ($value === '') {
                throw new NotImplementedException(sprintf('Deleting PAX keyword "%s" is not supported.', $key));
            }
            if ($key === 'size') {
                $result['size'] = self::decimal($value);
            } else {
                $result['path'] = $value;
            }
        }
        return $result;
    }

    private static function decimal(string $value): int
    {
        if (preg_match('/^[0-9]+$/D', $value) !== 1) {
            throw new InvalidArchiveFormatException('PAX length and size must be unsigned decimal integers.');
        }
        $digits = ltrim($value, '0');
        $maximum = (string) PHP_INT_MAX;
        if (
            strlen($digits) > strlen($maximum)
            || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)
        ) {
            throw new InvalidArchiveFormatException('PAX integer exceeds PHP_INT_MAX.');
        }
        return (int) $digits;
    }
}
