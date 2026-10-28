<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\Interval\Forms;

use Clesson\Silverstripe\Interval\Helpers\IntervalFormatter;
use InvalidArgumentException;
use SilverStripe\Forms\TextField;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;

/**
 * Form field for user-friendly interval input with ISO-8601 output.
 * Renders numeric sub-inputs for each allowed unit.
 * Stores and loads a single ISO-8601 string (e.g. "P1Y2M3D").
 *
 * Usage:
 *   IntervalField::create('IntervalSpec', 'Interval')
 *       ->setAllowedUnits(['Years', 'Months', 'Days'])
 *
 * @package Clesson\Silverstripe\Interval
 * @subpackage Forms
 */
class IntervalField extends TextField
{
    public const UNIT_YEARS   = 'Years';
    public const UNIT_MONTHS  = 'Months';
    public const UNIT_WEEKS   = 'Weeks';
    public const UNIT_DAYS    = 'Days';
    public const UNIT_HOURS   = 'Hours';
    public const UNIT_MINUTES = 'Minutes';
    public const UNIT_SECONDS = 'Seconds';

    private const ALL_UNITS = [
        self::UNIT_YEARS,
        self::UNIT_MONTHS,
        self::UNIT_WEEKS,
        self::UNIT_DAYS,
        self::UNIT_HOURS,
        self::UNIT_MINUTES,
        self::UNIT_SECONDS,
    ];

    /**
     * Which units are shown and accepted. Defaults to all units.
     *
     * @var string[]
     */
    private array $allowedUnits = self::ALL_UNITS;

    /**
     * Parsed component values, keyed by unit name.
     *
     * @var array<string, int>
     */
    private array $parts = [];

    /**
     * @inheritdoc
     */
    public function __construct($name, $title = null, $value = '')
    {
        parent::__construct($name, $title, $value);
        $this->parts = array_fill_keys(self::ALL_UNITS, 0);
        $this->setTemplate('Clesson\\Silverstripe\\Interval\\Forms\\IntervalField');
        $this->addExtraClass('interval-field--horizontal');
    }

    /**
     * Sets the units that are shown in the field.
     * Valid values: IntervalField::UNIT_YEARS, UNIT_MONTHS, UNIT_DAYS,
     *               UNIT_HOURS, UNIT_MINUTES, UNIT_SECONDS
     *
     * @param string[] $units
     * @return static
     */
    public function setAllowedUnits(array $units): static
    {
        $invalid = array_diff($units, self::ALL_UNITS);
        if ($invalid) {
            throw new InvalidArgumentException(
                'Invalid interval units: ' . implode(', ', $invalid)
                . '. Valid: ' . implode(', ', self::ALL_UNITS)
            );
        }
        // Preserve canonical order
        $this->allowedUnits = array_values(array_intersect(self::ALL_UNITS, $units));
        return $this;
    }

    /**
     * Returns the currently allowed units.
     *
     * @return string[]
     */
    public function getAllowedUnits(): array
    {
        return $this->allowedUnits;
    }

    /**
     * Returns an ArrayList of ArrayData objects for use in the template.
     * Each item has: Unit, Name, ID, Value, Label.
     *
     * @return ArrayList
     */
    public function getUnitFields(): ArrayList
    {
        $list = ArrayList::create();
        foreach ($this->allowedUnits as $unit) {
            $list->push(ArrayData::create([
                'Unit'  => $unit,
                'Name'  => $this->getName() . '[' . $unit . ']',
                'ID'    => $this->ID() . '_' . $unit,
                'Value' => $this->parts[$unit] ?? 0,
                'Label' => $this->getLabelForUnit($unit),
            ]));
        }
        return $list;
    }

    /**
     * @inheritdoc
     */
    public function setValue($value, $record = null)
    {
        if (is_array($value)) {
            // From POST data: ['Years' => '2', 'Months' => '0', ...]
            $parts = array_fill_keys(self::ALL_UNITS, 0);
            foreach ($this->allowedUnits as $unit) {
                $parts[$unit] = max(0, (int) ($value[$unit] ?? 0));
            }
            $this->parts = $parts;
            return parent::setValue(IntervalFormatter::buildIso($parts), $record);
        }

        // From DB: ISO-8601 string e.g. "P1Y2M"
        $iso = is_string($value) ? $value : '';
        $parsed = IntervalFormatter::parse($iso) ?? [];
        foreach (self::ALL_UNITS as $unit) {
            $this->parts[$unit] = $parsed[$unit] ?? 0;
        }
        return parent::setValue($iso, $record);
    }

    /**
     * Returns the formatted interval string for display.
     *
     * @return string
     */
    public function getFormattedValue(): string
    {
        $iso = (string) $this->value;
        if (empty($iso)) {
            return '';
        }
        return IntervalFormatter::format($iso);
    }

    /**
     * Returns the translated label for a given unit.
     *
     * @param string $unit
     * @return string
     */
    public function getLabelForUnit(string $unit): string
    {
        return _t(
            'Clesson\\Silverstripe\\Interval\\Forms\\IntervalField.' . strtoupper($unit),
            $unit
        );
    }

    /**
     * Returns all translation strings needed for JS-side formatting, as a JSON string.
     *
     * @return string
     */
    public function getLabelsJson(): string
    {
        $ns = 'Clesson\\Silverstripe\\Interval\\ORM\\DBInterval';
        $units = ['Years', 'Months', 'Weeks', 'Days', 'Hours', 'Minutes', 'Seconds'];

        $singular = [];
        $plural   = [];
        $adverbs  = [];
        foreach ($units as $unit) {
            $base = rtrim(strtoupper($unit), 'S');
            $singular[$unit] = _t($ns . '.' . $base,       $unit);
            $plural[$unit]   = _t($ns . '.' . $base . 'S', $unit);
            $adverbs[$unit]  = IntervalFormatter::labelForSingleUnit($unit);
        }

        return json_encode([
            'prefix'   => _t($ns . '.EVERY_PREFIX', 'Every {interval}'),
            'and'      => _t($ns . '.AND', 'and'),
            'singular' => $singular,
            'plural'   => $plural,
            'adverbs'  => $adverbs,
            'units'    => $units,
        ]);
    }
}
