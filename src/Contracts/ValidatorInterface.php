<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Contracts;

use Closure;
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
     * @return $this
     */
    public function addRule(string $name, RuleInterface $rule): self;

    /**
     * @param Closure $closure
     *
     * @return $this
     */
    public function addBeforeValidationHook(Closure $closure): self;

    /**
     * @param Closure $closure
     *
     * @return $this
     */
    public function addAfterValidationHook(Closure $closure): self;
}
