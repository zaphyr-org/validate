<?php

declare(strict_types=1);

namespace Zaphyr\Validate;

use Closure;
use Zaphyr\Utils\Str;
use Zaphyr\Validate\Contracts\RuleInterface;
use Zaphyr\Validate\Contracts\ValidatorInterface;
use Zaphyr\Validate\Exceptions\ValidatorException;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class Validator implements ValidatorInterface
{
    /**
     * @var RuleParser
     */
    protected RuleParser $ruleParser;

    /**
     * @var MessageBag
     */
    protected MessageBag $messageBag;

    /**
     * @var RuleInterface[]
     */
    protected array $cachedRules = [];

    /**
     * @var Closure[]
     */
    protected array $beforeValidationHooks = [];

    /**
     * @var Closure[]
     */
    protected array $afterValidationHooks = [];

    /**
     * @param string|null $locale
     * @param string|null $translationMessagesDirectory
     * @param string|null $translationMessagesNamespace
     */
    public function __construct(
        ?string $locale = null,
        ?string $translationMessagesDirectory = null,
        ?string $translationMessagesNamespace = null
    ) {
        $this->ruleParser = new RuleParser();
        $this->messageBag = new MessageBag($locale, $translationMessagesDirectory, $translationMessagesNamespace);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(
        array $inputs,
        array $rules,
        array $customMessages = [],
        array $customFieldReplacers = [],
        bool $allowExtraInputs = true
    ): void {
        $this->initializeValidation($customMessages, $customFieldReplacers);

        foreach ($this->beforeValidationHooks as $callback) {
            $callback($this);
        }

        if (!$allowExtraInputs) {
            $this->validateExtraInputs($inputs, $rules);
        }

        $this->validateAllInputs($inputs, $rules);
    }

    /**
     * @param array<string, string> $customMessages
     * @param array<string, mixed>  $customFieldReplacers
     *
     * @return void
     */
    protected function initializeValidation(array $customMessages, array $customFieldReplacers): void
    {
        $this->messageBag->clear();
        $this->messageBag->setCustomMessages($customMessages);
        $this->messageBag->setCustomFieldReplacers($customFieldReplacers);
    }

    /**
     * @param array<string, mixed>  $inputs
     * @param array<string, string> $rules
     *
     * @return void
     */
    protected function validateExtraInputs(array $inputs, array $rules): void
    {
        $extraFields = array_diff(array_keys($inputs), array_keys($rules));

        foreach ($extraFields as $extraField) {
            $this->messageBag->addMessage($extraField, 'not.allowed');
        }
    }

    /**
     * @param array<string, mixed>  $inputs
     * @param array<string, string> $rules
     *
     * @throws ValidatorException
     * @return void
     */
    protected function validateAllInputs(array $inputs, array $rules): void
    {
        foreach ($rules as $field => $fieldRules) {
            $value = $inputs[$field] ?? null;
            $ruleList = explode('|', $fieldRules);

            if ($this->isNullableValue($ruleList, $value)) {
                continue;
            }

            foreach ($ruleList as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }

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
     * @throws ValidatorException
     * @return void
     */
    protected function validateInput(array $inputs, string $field, mixed $value, string $rule): void
    {
        $ruleName = $this->ruleParser->getRuleName($rule);
        $ruleParameters = $this->ruleParser->getRuleParameters($rule);
        $ruleInstance = $this->getRule($ruleName);

        if (!$ruleInstance->validate($field, $value, $ruleParameters, $inputs)) {
            $this->messageBag->addRuleMessage($field, $ruleInstance, $ruleParameters);
        }
    }

    /**
     * @param list<string> $ruleList
     * @param mixed        $value
     *
     * @return bool
     */
    protected function isNullableValue(array $ruleList, mixed $value): bool
    {
        return in_array('nullable', $ruleList, true) && $value === null;
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
     * @throws ValidatorException
     * @return RuleInterface
     */
    protected function getRule(string $ruleName): RuleInterface
    {
        if ($this->hasRule($ruleName)) {
            return $this->cachedRules[$ruleName];
        }

        $class = 'Zaphyr\\Validate\\Rules\\' . Str::studly(trim($ruleName)) . 'Rule';

        if (!class_exists($class)) {
            throw new ValidatorException('Invalid rule name "' . $ruleName . '"');
        }

        /** @var RuleInterface $ruleObject */
        $ruleObject = new $class();

        return $this->cachedRules[$ruleName] = $ruleObject;
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
    public function addRule(string $name, RuleInterface $rule): static
    {
        if ($this->hasRule($name)) {
            throw new ValidatorException('A rule with the name "' . $name . '" is already in use');
        }

        $this->cachedRules[$name] = $rule;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addBeforeValidationHook(Closure $closure): static
    {
        $this->beforeValidationHooks[] = $closure;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addAfterValidationHook(Closure $closure): static
    {
        $this->afterValidationHooks[] = $closure;

        return $this;
    }
}
