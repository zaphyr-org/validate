<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Contracts;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
interface RuleInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string               $field
     * @param mixed                $value
     * @param array<int, mixed>    $parameters
     * @param array<string, mixed> $inputs
     *
     * @return bool
     */
    public function validate(string $field, mixed $value, array $parameters, array $inputs): bool;

    /**
     * @param string            $message
     * @param array<int, mixed> $parameters
     *
     * @return string|null
     */
    public function replace(string $message, array $parameters): string|null;
}
