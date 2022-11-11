<?php

declare(strict_types=1);

namespace Zaphyr\ValidateTests\TestAssets\Rules;

use Zaphyr\Validate\Rules\AbstractRule;

class Banana extends AbstractRule
{
    /**
     * {@inheritdoc }
     */
    public function validate(string $field, $value, array $parameters, array $inputs): bool
    {
        return $value === 'banana';
    }
}
