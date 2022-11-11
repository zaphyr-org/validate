<?php

declare(strict_types=1);

namespace Zaphyr\Validate;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Validation\EmailValidation;
use Egulias\EmailValidator\Result\InvalidEmail;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
final class FilterEmailValidation implements EmailValidation
{
    /**
     * @var int|null
     */
    protected $flags;

    /**
     * @param int|null $flags
     */
    public function __construct(?int $flags = null)
    {
        $this->flags = $flags;
    }

    /**
     * @return static
     */
    public static function unicode(): self
    {
        return new FilterEmailValidation(FILTER_FLAG_EMAIL_UNICODE);
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($email, EmailLexer $emailLexer): bool
    {
        return is_null($this->flags)
            ? filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            : filter_var($email, FILTER_VALIDATE_EMAIL, $this->flags) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function getError(): ?InvalidEmail
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getWarnings(): array
    {
        return [];
    }
}
