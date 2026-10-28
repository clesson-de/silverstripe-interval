<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\Interval\ORM;

use Clesson\Silverstripe\Interval\Forms\IntervalField;
use Clesson\Silverstripe\Interval\Helpers\IntervalFormatter;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\FieldType\DBVarchar;

/**
 * Stores ISO-8601 interval strings (e.g., "P1Y2M3D") and provides localized formatting.
 *
 * @package Clesson\Silverstripe\Interval
 * @subpackage ORM
 */
class DBInterval extends DBVarchar
{
    /**
     * @inheritdoc
     */
    public function __construct($name = null, $size = 50)
    {
        parent::__construct($name, $size);
    }

    /**
     * Returns a localized, human-readable string representation of the interval.
     *
     * @return string
     */
    public function Nice(): string
    {
        return IntervalFormatter::format($this->value);
    }

    /**
     * @inheritdoc
     */
    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        return IntervalField::create($this->name, $title);
    }
}

