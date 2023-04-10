<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

use Exception;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class ActiveUrlRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, $value, array $parameters, array $inputs): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if ($url = parse_url($value, PHP_URL_HOST)) {
            try {
                $dnsRecord = dns_get_record($url, DNS_A | DNS_AAAA);

                if (!is_array($dnsRecord)) {
                    return false;
                }

                return count($dnsRecord) > 0;
            } catch (Exception) {
                return false;
            }
        }

        return false;
    }
}
