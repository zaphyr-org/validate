<?php

declare(strict_types=1);

namespace Zaphyr\Validate;

use Zaphyr\Utils\Arr;
use Zaphyr\Validate\Contracts\RuleInterface;
use Zaphyr\Validate\Traits\MessageTrait;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class MessageBag
{
    use MessageTrait;

    /**
     * @var array<string, string[]>
     */
    protected array $messages = [];

    /**
     * @param string|null $locale
     * @param string|null $translationMassagesDirectory
     * @param string|null $translationMassagesNamespace
     */
    public function __construct(
        string|null $locale = null,
        string|null $translationMassagesDirectory = null,
        string|null $translationMassagesNamespace = null
    ) {
        $this->initTranslatorInstance($locale, $translationMassagesDirectory, $translationMassagesNamespace);
    }

    /**
     * @param string                  $field
     * @param RuleInterface           $rule
     * @param array<int, string|null> $parameters
     *
     * @return void
     */
    public function add(string $field, RuleInterface $rule, array $parameters): void
    {
        $message = $this->getMessage($field, $rule, $parameters);

        if (!isset($this->messages[$field]) || !in_array($message, $this->messages[$field], true)) {
            $this->messages[$field][] = $message;
        }
    }

    /**
     * @param string|null $field
     *
     * @return string|null
     */
    public function first(string|null $field = null): string|null
    {
        $messages = $field === null ? $this->all() : $this->get($field);
        $firstMessage = is_array($messages) ? Arr::first($messages) : null;

        return is_array($firstMessage) ? Arr::first($firstMessage) : $firstMessage;
    }

    /**
     * @param string|null $field
     *
     * @return string[]|null
     */
    public function get(string|null $field = null): array|null
    {
        if ($field !== null && $this->has($field)) {
            return $this->messages[$field];
        }

        return null;
    }

    /**
     * @return array<string, string[]>
     */
    public function all(): array
    {
        return $this->messages;
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    public function has(string $field): bool
    {
        return array_key_exists($field, $this->messages);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->messages);
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        $this->messages = [];
    }
}
