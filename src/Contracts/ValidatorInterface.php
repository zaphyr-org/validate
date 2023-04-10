<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Contracts;

use Closure;
use Zaphyr\Validate\Exceptions\ValidatorException;
use Zaphyr\Validate\MessageBag;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
interface ValidatorInterface
{
    /**
     * @param array<string, mixed>  $inputs
     * @param array<string, string> $rules
     * @param array<string, string> $customMessages
     * @param array<string, mixed>  $customFieldReplacers
     *
     * @throws ValidatorException If validation rule is not available
     * @return void
     */
    public function validate(
        array $inputs,
        array $rules,
        array $customMessages = [],
        array $customFieldReplacers = []
    ): void;

    /**
     * @return bool
     */
    public function isValid(): bool;

    /**
     * @return MessageBag
     */
    public function errors(): MessageBag;

    /**
     * @param string        $name
     * @param RuleInterface $rule
     *
     * @throws ValidatorException If validation rule already exists
     * @return $this
     */
    public function addRule(string $name, RuleInterface $rule): static;

    /**
     * @param Closure $closure
     *
     * @return $this
     */
    public function addBeforeValidationHook(Closure $closure): static;

    /**
     * @param Closure $closure
     *
     * @return $this
     */
    public function addAfterValidationHook(Closure $closure): static;
}
