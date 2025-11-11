<?php

declare(strict_types=1);

/**
 * Email Value Object
 *
 * @package RIILSA\Domain\ValueObjects
 * @since 3.1.0
 */

namespace RIILSA\Domain\ValueObjects;

use InvalidArgumentException;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;

/**
 * Email value object with validation
 * 
 * Pattern: Value Object Pattern
 * This immutable object represents an email address with built-in validation
 */
final class Email
{
    /**
     * The email address
     *
     * @var string
     */
    private readonly string $email;

    /**
     * Create a new Email instance
     *
     * @param string $email The email address
     * @throws InvalidArgumentException If the email is invalid
     */
    public function __construct(string $email)
    {
        $email = trim(strtolower($email));
        
        if (!$this->isValid($email)) {
            throw new InvalidArgumentException("Invalid email address: {$email}");
        }
        
        $this->email = $email;
    }

    /**
     * Create from string
     *
     * @param string $email
     * @return self
     * @throws InvalidArgumentException
     */
    public static function fromString(string $email): self
    {
        return new self($email);
    }

    /**
     * Create from nullable string
     *
     * @param string|null $email
     * @return self|null
     * @throws InvalidArgumentException
     */
    public static function fromNullableString(?string $email): ?self
    {
        if ($email === null || $email === '') {
            return null;
        }
        
        return new self($email);
    }

    /**
     * Validate an email address
     *
     * @param string $email
     * @return bool
     */
    private function isValid(string $email): bool
    {
        $validator = new EmailValidator();
        $validations = new MultipleValidationWithAnd([
            new RFCValidation(),
            // DNS validation can be enabled for stricter validation
            // new DNSCheckValidation(),
        ]);
        
        return $validator->isValid($email, $validations);
    }

    /**
     * Get the email address
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->email;
    }

    /**
     * Get the domain part of the email
     *
     * @return string
     */
    public function getDomain(): string
    {
        return substr($this->email, strpos($this->email, '@') + 1);
    }

    /**
     * Get the local part of the email
     *
     * @return string
     */
    public function getLocalPart(): string
    {
        return substr($this->email, 0, strpos($this->email, '@'));
    }

    /**
     * Check if two emails are equal
     *
     * @param Email $other
     * @return bool
     */
    public function equals(Email $other): bool
    {
        return $this->email === $other->email;
    }

    /**
     * String representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->email;
    }

    /**
     * Serialize to JSON
     *
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->email;
    }
}
