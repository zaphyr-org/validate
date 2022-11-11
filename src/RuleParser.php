<?php

declare(strict_types=1);

namespace Zaphyr\Validate;

use Zaphyr\Utils\Str;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class RuleParser
{
    /**
     * @param string $rule
     *
     * @return string
     */
    public function getRuleName(string $rule): string
    {
        [$name] = $this->splitRuleParameters($rule);

        return $name;
    }

    /**
     * @param string $rule
     *
     * @return array<int, string|null>
     */
    public function getRuleParameters(string $rule): array
    {
        $parameters = [];

        if (Str::contains($rule, ':')) {
            [$name, $parameter] = $this->splitRuleParameters($rule);

            // Ensure commas are not interpreted as parameter separators for regex rules
            $parameters = in_array($name, ['regex', 'not_regex'], true) ? [$parameter] : str_getcsv($parameter);
        }

        return $parameters;
    }

    /**
     * @param string $rule
     *
     * @return string[]
     */
    protected function splitRuleParameters(string $rule): array
    {
        return explode(':', $rule, 2);
    }
}
