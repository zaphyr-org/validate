<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

use Zaphyr\Utils\Str;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class StartsWithoutRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, $value, array $parameters, array $inputs): bool
    {
        $this->countRequiredParams(1, $parameters, 'starts_without');

        return is_string($value) && !Str::beginsWith($value, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function replace(string $message, array $parameters): string|null
    {
        return str_replace('%values%', implode(', ', $parameters), $message);
    }
}
