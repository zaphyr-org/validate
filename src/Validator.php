<?php

declare(strict_types=1);

namespace Zaphyr\Validate;

use Closure;
use InvalidArgumentException;
use Zaphyr\Utils\Str;
use Zaphyr\Validate\Contracts\RuleInterface;
use Zaphyr\Validate\Contracts\ValidatorInterface;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class Validator implements ValidatorInterface
{
    /**
     * @var RuleParser
     */
    protected $ruleParser;

    /**
     * @var MessageBag
     */
    protected $messageBag;

    /**
     * @var RuleInterface[]
     */
    protected $cachedRules = [];

    /**
     * @var Closure[]
     */
    protected $beforeValidationHooks = [];

    /**
     * @var Closure[]
     */
    protected $afterValidationHooks = [];

    /**
     * @param string|null $locale
     * @param string|null $translationMassagesDirectory
     * @param string|null $translationMassagesNamespace
     */
    public function __construct(
        string $locale = null,
        string $translationMassagesDirectory = null,
        string $translationMassagesNamespace = null
    ) {
        $this->ruleParser = new RuleParser();
        $this->messageBag = new MessageBag($locale, $translationMassagesDirectory, $translationMassagesNamespace);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(
        array $inputs,
        array $rules,
        array $customMessages = [],
        array $customFieldReplacers = []
    ): void {
        $this->messageBag->clear();
        $this->messageBag->setCustomMessages($customMessages);
        $this->messageBag->setCustomFieldReplacers($customFieldReplacers);

        foreach ($this->beforeValidationHooks as $callback) {
            $callback($this);
        }

        foreach ($inputs as $field => $value) {
            if (!isset($rules[$field])) {
                continue;
            }

            foreach (explode('|', $rules[$field]) as $rule) {
                $this->validateInput($inputs, $field, $value, $rule);

                if ($this->shouldStopValidating($field)) {
                    break;
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $inputs
     * @param string               $field
     * @param mixed                $value
     * @param string               $rule
     *
     * @return void
     */
    protected function validateInput(array $inputs, string $field, $value, string $rule): void
    {
        $ruleName = $this->ruleParser->getRuleName($rule);
        $ruleParameters = $this->ruleParser->getRuleParameters($rule);
        $ruleInstance = $this->getRule($ruleName);

        if (!$this->hasRule('nullable') && !$ruleInstance->validate($field, $value, $ruleParameters, $inputs)) {
            $this->messageBag->add($field, $ruleInstance, $ruleParameters);
        }
    }

    /**
     * @param string $field
     *
     * @return bool|null
     */
    protected function shouldStopValidating(string $field): ?bool
    {
        return $this->hasRule('bail') ? $this->messageBag->has($field) : null;
    }

    /**
     * @param string $ruleName
     *
     * @return RuleInterface
     */
    protected function getRule(string $ruleName): RuleInterface
    {
        if ($this->hasRule($ruleName)) {
            return $this->cachedRules[$ruleName];
        }

        $class = 'Zaphyr\\Validate\\Rules\\' . Str::studly(trim($ruleName)) . 'Rule';

        if (!class_exists($class)) {
            throw new InvalidArgumentException('Invalid rule name "' . $ruleName . '"');
        }

        /** @var RuleInterface $class */
        return $this->cachedRules[$ruleName] = new $class();
    }

    /**
     * @param string $ruleName
     *
     * @return bool
     */
    protected function hasRule(string $ruleName): bool
    {
        return isset($this->cachedRules[$ruleName]);
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(): bool
    {
        foreach ($this->afterValidationHooks as $callback) {
            $callback($this);
        }

        return $this->messageBag->isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function errors(): MessageBag
    {
        return $this->messageBag;
    }

    /**
     * {@inheritdoc}
     */
    public function addRule(string $name, RuleInterface $rule): ValidatorInterface
    {
        if ($this->hasRule($name)) {
            throw new InvalidArgumentException('A rule with the name "' . $name . '" is already in use');
        }

        $this->cachedRules[$name] = $rule;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addBeforeValidationHook(Closure $closure): ValidatorInterface
    {
        $this->beforeValidationHooks[] = $closure;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addAfterValidationHook(Closure $closure): ValidatorInterface
    {
        $this->afterValidationHooks[] = $closure;

        return $this;
    }
}
