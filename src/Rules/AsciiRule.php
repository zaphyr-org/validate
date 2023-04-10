<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

use Zaphyr\Utils\Str;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class AsciiRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, mixed $value, array $parameters, array $inputs): bool
    {
        return is_string($value) && Str::isAscii($value);
    }
}
