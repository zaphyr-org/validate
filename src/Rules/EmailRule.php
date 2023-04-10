<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\Extra\SpoofCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
use Zaphyr\Validate\FilterEmailValidation;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class EmailRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, mixed $value, array $parameters, array $inputs): bool
    {
        if (!is_string($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            return false;
        }

        $validations = [new RFCValidation()];

        if (in_array('dns', $parameters, true)) {
            $validations[] = new DNSCheckValidation();
        }

        if (in_array('filter', $parameters, true)) {
            $validations[] = new FilterEmailValidation();
        }

        if (in_array('filter_unicode', $parameters, true)) {
            $validations[] = FilterEmailValidation::unicode();
        }

        if (in_array('spoof', $parameters, true)) {
            $validations[] = new SpoofCheckValidation();
        }

        if (in_array('strict', $parameters, true)) {
            $validations[] = new NoRFCWarningsValidation();
        }

        return (new EmailValidator())->isValid((string)$value, new MultipleValidationWithAnd($validations));
    }
}
