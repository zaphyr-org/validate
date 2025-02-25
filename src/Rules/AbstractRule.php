<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

use DateTime;
use DateTimeInterface;
use Exception;
use ReflectionClass;
use Zaphyr\Utils\Arr;
use Zaphyr\Utils\Str;
use Zaphyr\Validate\Contracts\RuleInterface;
use Zaphyr\Validate\Exceptions\ValidatorException;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
abstract class AbstractRule implements RuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        $name = (new ReflectionClass($this))->getShortName();
        $name = str_replace('Rule', '', $name);

        return (string)Str::snake($name, '.');
    }

    /**
     * {@inheritdoc}
     */
    public function replace(string $message, array $parameters): ?string
    {
        return null;
    }

    /**
     * @param int               $count
     * @param array<int, mixed> $parameters
     * @param string            $rule
     *
     * @throws ValidatorException If the number of parameters is invalid
     */
    protected function countRequiredParams(int $count, array $parameters, string $rule): void
    {
        if (count($parameters) < $count) {
            throw new ValidatorException(
                'The validation rule "' . $rule . '" requires at least ' .
                $count . ' parameter' . ($count > 1 ? 's' : null)
            );
        }
    }

    /**
     * @param mixed                $value
     * @param array<int, mixed>    $parameters
     * @param array<string, mixed> $inputs
     * @param string               $operator
     *
     * @throws ValidatorException If operator is not valid
     * @return bool
     */
    protected function compareDates(mixed $value, array $parameters, array $inputs, string $operator): bool
    {
        if (!is_string($value) && !is_numeric($value) && !$value instanceof DateTimeInterface) {
            return false;
        }

        $date = Arr::has($inputs, $parameters[0]) ? $inputs[$parameters[0]] : $parameters[0];

        try {
            $beforeDate = $value instanceof DateTimeInterface ? $value : new DateTime((string)$value);
            $afterDate = new DateTime($date);
        } catch (Exception) {
            return false;
        }

        return $this->compare($beforeDate->getTimestamp(), $afterDate->getTimestamp(), $operator);
    }

    /**
     * @param mixed  $first
     * @param mixed  $second
     * @param string $operator
     *
     * @throws ValidatorException If operator is not valid
     * @return bool
     */
    protected function compare(mixed $first, mixed $second, string $operator): bool
    {
        return match ($operator) {
            '<' => $first < $second,
            '>' => $first > $second,
            '<=' => $first <= $second,
            '>=' => $first >= $second,
            '=' => $first === $second,
            default => throw new ValidatorException('The "' . $operator . '" is not a valid operator'),
        };
    }
}
