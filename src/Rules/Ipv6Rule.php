<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class Ipv6Rule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, mixed $value, array $parameters, array $inputs): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }
}
