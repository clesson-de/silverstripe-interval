# SilverStripe Interval Module

A reusable SilverStripe 6 module providing ISO-8601 interval storage and user-friendly interval input.

## Features

- **DBInterval**: A custom database field type that stores ISO-8601 interval strings (e.g., `P1Y2M3DT4H5M6S`)
- **IntervalField**: A composite form field with separate inputs for years, months, days, hours, minutes, and seconds
- **IntervalFormatter**: Utility class for parsing, formatting, and building ISO-8601 interval strings
- **Localized Output**: Supports German (de) and English (en) translations for interval formatting

## Installation

```bash
composer require clesson-de/silverstripe-interval:@dev
```

## Usage

### In a DataObject Model

```php
use Clesson\Silverstripe\Interval\ORM\DBInterval;
use SilverStripe\ORM\DataObject;

class MyModel extends DataObject
{
    private static $db = [
        'RepeatInterval' => DBInterval::class,  // Stores ISO-8601 strings
    ];
}
```

The `IntervalField` will automatically be used in the CMS for editing.

### Format Examples

- `P1Y` → "Yearly" (1 year)
- `P4M8D` → "4 months and 8 days"
- `P1Y2M3DT4H5M6S` → "1 year, 2 months, 3 days, 4 hours, 5 minutes and 6 seconds"
- (empty string) → "One-time"

### Manual Formatting

```php
use Clesson\Silverstripe\Interval\Helpers\IntervalFormatter;

$formatted = IntervalFormatter::format('P1Y2M');  // Output: "Every 1 year and 2 months"
```

## ISO-8601 Interval Format

The module uses the standard ISO-8601 duration format:

- `P` = Period designator
- `nY` = years
- `nM` = months (in date part)
- `nW` = weeks (converted to days if other date parts exist)
- `nD` = days
- `T` = Time designator (separates date and time parts)
- `nH` = hours
- `nM` = minutes (in time part)
- `nS` = seconds

## Translations

The module includes translations for German and English. Add new translations to:

- `lang/de.yml` for German labels and messages
- `lang/en.yml` for English labels and messages

## License

BSD-3-Clause
