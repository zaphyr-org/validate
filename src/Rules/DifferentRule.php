<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

use Zaphyr\Utils\Arr;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class DifferentRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, $value, array $parameters, array $inputs): bool
    {
        $this->countRequiredParams(1, $parameters, 'different');

        foreach ($parameters as $parameter) {
            if (!Arr::has($inputs, $parameter)) {
                return false;
            }

            $other = Arr::get($inputs, $parameter);

            if ($value === $other) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function replace(string $message, array $parameters): ?string
    {
        return str_replace('%other%', $parameters[0], $message);
    }
}
