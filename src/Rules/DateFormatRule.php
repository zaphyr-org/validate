<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

use DateTime;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class DateFormatRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, mixed $value, array $parameters, array $inputs): bool
    {
        $this->countRequiredParams(1, $parameters, 'date_format');

        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $format = $parameters[0];
        $date = DateTime::createFromFormat('!' . $format, (string)$value);

        return $date && $date->format($format) === $value;
    }

    /**
     * {@inheritdoc}
     */
    public function replace(string $message, array $parameters): string|null
    {
        return str_replace('%format%', $parameters[0], $message);
    }
}
