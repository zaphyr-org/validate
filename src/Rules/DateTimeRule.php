<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

use DateTimeInterface;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class DateTimeRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, $value, array $parameters, array $inputs): bool
    {
        if ($value instanceof DateTimeInterface) {
            return true;
        }

        if ((!is_string($value) && !is_numeric($value)) || strtotime((string)$value) === false) {
            return false;
        }

        $date = date_parse((string)$value);

        if (!is_array($date)) {
            return false;
        }

        $month = $date['month'] ?: 0;
        $day = $date['day'] ?: 0;
        $year = $date['year'] ?: 0;

        if (!checkdate($month, $day, $year)) {
            return false;
        }

        $hour = $date['hour'] ?: 0;
        $minute = $date['minute'] ?: 0;
        $second = $date['second'] ?: 0;

        return mktime($hour, $minute, $second, $month, $day, $year) !== false;
    }
}
