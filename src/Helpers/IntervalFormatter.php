<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\Interval\Helpers;

/**
 * Converts ISO-8601 interval strings to localized human-readable strings and back.
 *
 * @package Clesson\Silverstripe\Interval
 * @subpackage Helpers
 */
final class IntervalFormatter
{
    /**
     * Returns a localized string for the given ISO-8601 interval.
     *
     * @param string $value
     * @return string
     */
    public static function format(string $value): string
    {
        if (trim($value) === '') {
            return _t('Clesson\\Silverstripe\\Interval\\ORM\\DBInterval.ONCE', 'One-time');
        }

        $parts = self::parse($value);
        if ($parts === null) {
            return $value;
        }

        if (self::isEmpty($parts)) {
            return _t('Clesson\\Silverstripe\\Interval\\ORM\\DBInterval.ONCE', 'One-time');
        }

        if (self::isSingleUnit($parts, 1)) {
            return self::formatSingleUnit($parts);
        }

        return _t(
            'Clesson\\Silverstripe\\Interval\\ORM\\DBInterval.EVERY_PREFIX',
            'Every {interval}',
            ['interval' => self::joinParts(self::partsToStrings($parts))]
        );
    }

    /**
     * Parses an ISO-8601 interval string into component values.
     *
     * @param string $value
     * @return array<string, int>|null
     */
    public static function parse(string $value): ?array
    {
        if (!preg_match('~^P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$~i', trim($value), $matches)) {
            return null;
        }

        return [
            'Years' => (int) ($matches[1] ?? 0),
            'Months' => (int) ($matches[2] ?? 0),
            'Weeks' => (int) ($matches[3] ?? 0),
            'Days' => (int) ($matches[4] ?? 0),
            'Hours' => (int) ($matches[5] ?? 0),
            'Minutes' => (int) ($matches[6] ?? 0),
            'Seconds' => (int) ($matches[7] ?? 0),
        ];
    }

    /**
     * Builds an ISO-8601 interval string from component values.
     *
     * @param array<string, mixed> $parts
     * @return string
     */
    public static function buildIso(array $parts): string
    {
        $parts = self::normalizeParts($parts);
        if (self::isEmpty($parts)) {
            return '';
        }

        return 'P' . self::buildDatePart($parts) . self::buildTimePart($parts);
    }

    /**
     * Returns the localized unit label.
     *
     * @param string $unit
     * @param int $count
     * @return string
     */
    public static function labelForUnit(string $unit, int $count = 1): string
    {
        return _t(
            'Clesson\\Silverstripe\\Interval\\ORM\\DBInterval.' . self::unitKey($unit, $count),
            self::unitFallback($unit, $count)
        );
    }

    /**
     * Returns the localized singular adverb for a single-unit interval.
     *
     * @param string $unit
     * @return string
     */
    public static function labelForSingleUnit(string $unit): string
    {
        return _t(
            'Clesson\\Silverstripe\\Interval\\ORM\\DBInterval.' . self::singleKey($unit),
            self::singleFallback($unit)
        );
    }

    /**
     * @param array<string, int> $parts
     * @return bool
     */
    private static function isEmpty(array $parts): bool
    {
        return array_sum($parts) === 0;
    }

    /**
     * @param array<string, int> $parts
     * @param int $value
     * @return bool
     */
    private static function isSingleUnit(array $parts, int $value): bool
    {
        return count(array_filter($parts)) === 1 && max($parts) === $value;
    }

    /**
     * @param array<string, int> $parts
     * @return string
     */
    private static function formatSingleUnit(array $parts): string
    {
        foreach (self::unitOrder() as $unit) {
            if (($parts[$unit] ?? 0) === 1) {
                return self::labelForSingleUnit($unit);
            }
        }

        return self::joinParts(self::partsToStrings($parts));
    }

    /**
     * @param array<string, int> $parts
     * @return array<int, string>
     */
    private static function partsToStrings(array $parts): array
    {
        $strings = [];
        foreach (self::unitOrder() as $unit) {
            $count = (int) ($parts[$unit] ?? 0);
            if ($count > 0) {
                $strings[] = $count . ' ' . self::labelForUnit($unit, $count);
            }
        }

        return $strings;
    }

    /**
     * @param array<int, string> $parts
     * @return string
     */
    private static function joinParts(array $parts): string
    {
        if (count($parts) === 1) {
            return $parts[0];
        }

        $last = array_pop($parts);
        return implode(', ', $parts) . ' ' . _t('Clesson\\Silverstripe\\Interval\\ORM\\DBInterval.AND', 'and') . ' ' . $last;
    }

    /**
     * @param array<string, mixed> $parts
     * @return array<string, int>
     */
    private static function normalizeParts(array $parts): array
    {
        $normalized = [];
        foreach (self::unitOrder() as $unit) {
            $normalized[$unit] = max(0, (int) ($parts[$unit] ?? 0));
        }

        return $normalized;
    }

    /**
     * @param array<string, int> $parts
    /**
     * @param array<string, int> $parts
     * @return string
     */
    private static function buildDatePart(array $parts): string
    {
        $date = '';
        foreach (['Years' => 'Y', 'Months' => 'M', 'Weeks' => 'W', 'Days' => 'D'] as $unit => $suffix) {
            $value = $parts[$unit];
            if ($value > 0) {
                $date .= $value . $suffix;
            }
        }

        return $date;
    }

    /**
     * @param array<string, int> $parts
     * @return string
     */
    private static function buildTimePart(array $parts): string
    {
        $time = '';
        foreach (['Hours' => 'H', 'Minutes' => 'M', 'Seconds' => 'S'] as $unit => $suffix) {
            $value = $parts[$unit];
            if ($value > 0) {
                $time .= $value . $suffix;
            }
        }

        return $time === '' ? '' : 'T' . $time;
    }

    /**
     * @return array<int, string>
     */
    private static function unitOrder(): array
    {
        return ['Years', 'Months', 'Weeks', 'Days', 'Hours', 'Minutes', 'Seconds'];
    }

    /**
     * @param string $unit
     * @param int $count
     * @return string
     */
    private static function unitKey(string $unit, int $count): string
    {
        $base = rtrim(strtoupper($unit), 'S'); // "YEAR", "MONTH", "DAY", etc.
        return $count === 1 ? $base : $base . 'S';
    }

    /**
     * @param string $unit
     * @return string
     */
    private static function singleKey(string $unit): string
    {
        return match ($unit) {
            'Years' => 'YEARLY',
            'Months' => 'MONTHLY',
            'Weeks' => 'WEEKLY',
            'Days' => 'DAILY',
            'Hours' => 'HOURLY',
            'Minutes' => 'MINUTELY',
            'Seconds' => 'SECONDLY',
            default => 'EVERY_PREFIX',
        };
    }

    /**
     * @param string $unit
     * @param int $count
     * @return string
     */
    private static function unitFallback(string $unit, int $count): string
    {
        $map = [
            'Years' => ['year', 'years'],
            'Months' => ['month', 'months'],
            'Weeks' => ['week', 'weeks'],
            'Days' => ['day', 'days'],
            'Hours' => ['hour', 'hours'],
            'Minutes' => ['minute', 'minutes'],
            'Seconds' => ['second', 'seconds'],
        ];

        return $map[$unit][$count === 1 ? 0 : 1] ?? $unit;
    }

    /**
     * @param string $unit
     * @return string
     */
    private static function singleFallback(string $unit): string
    {
        return match ($unit) {
            'Years' => 'Yearly',
            'Months' => 'Monthly',
            'Weeks' => 'Weekly',
            'Days' => 'Daily',
            'Hours' => 'Hourly',
            'Minutes' => 'Every minute',
            'Seconds' => 'Every second',
            default => 'Every interval',
        };
    }
}
