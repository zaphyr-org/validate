<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Traits;

use Zaphyr\Utils\Str;
use Zaphyr\Validate\Contracts\RuleInterface;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
trait MessageTrait
{
    use TranslationTrait;

    /**
     * @var array<string, string>
     */
    protected $customMessages = [];

    /**
     * @var array<string, string>
     */
    protected $customFieldReplacers = [];

    /**
     * @param array<string, string> $customMessages
     *
     * @return void
     */
    public function setCustomMessages(array $customMessages): void
    {
        $this->customMessages = $customMessages;
    }

    /**
     * @param array<string, string> $customFieldReplacers
     *
     * @return void
     */
    public function setCustomFieldReplacers(array $customFieldReplacers): void
    {
        $this->customFieldReplacers = $customFieldReplacers;
    }

    /**
     * @param string                  $field
     * @param RuleInterface           $rule
     * @param array<int, string|null> $parameters
     *
     * @return string
     */
    protected function getMessage(string $field, RuleInterface $rule, array $parameters): string
    {
        if ($customMessage = $this->getCustomMessage($field, $rule)) {
            return $customMessage;
        }

        $message = $this->getTranslation($rule->getName());

        if ($message) {
            return $this->makeReplacements($message, $field, $rule, $parameters);
        }

        return $rule->getName();
    }

    /**
     * @param string        $field
     * @param RuleInterface $rule
     *
     * @return string|null
     */
    protected function getCustomMessage(string $field, RuleInterface $rule): ?string
    {
        // Check for custom inline messages
        $ruleName = $rule->getName();
        $keys = [$field . '.' . $ruleName, $ruleName];

        foreach ($keys as $key) {
            foreach (array_keys($this->customMessages) as $sourceKey) {
                if ($sourceKey === $key) {
                    return $this->customMessages[$sourceKey];
                }
            }
        }

        // Check for custom translation file messages
        return $this->getTranslation('_custom.' . $field . '.' . $rule->getName());
    }

    /**
     * @param string                  $message
     * @param string                  $field
     * @param RuleInterface           $rule
     * @param array<int, string|null> $parameters
     *
     * @return string
     */
    protected function makeReplacements(string $message, string $field, RuleInterface $rule, array $parameters): string
    {
        $message = $this->replacePlaceholder($message, $this->getDisplayableFieldName($field));

        if ($replace = $rule->replace($message, $parameters)) {
            $message = $replace;
        }

        return $message;
    }

    /**
     * @param string $message
     * @param string $field
     *
     * @return string
     */
    protected function replacePlaceholder(string $message, string $field): string
    {
        return str_replace(
            [
                '%field%',
                '%Field%',
                '%FIELD%',
            ],
            [
                $field,
                Str::upperFirst($field),
                Str::upper($field),
            ],
            $message
        );
    }

    /**
     * @param string $field
     *
     * @return string
     */
    protected function getDisplayableFieldName(string $field): string
    {
        if (isset($this->customFieldReplacers[$field])) {
            return $this->customFieldReplacers[$field];
        }

        $translationAttribute = $this->getTranslation('_fields.' . $field);

        return $translationAttribute ?? str_replace('_', ' ', (string)Str::snake($field));
    }
}
