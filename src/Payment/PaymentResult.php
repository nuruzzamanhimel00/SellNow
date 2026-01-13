<?php

namespace SellNow\Payment;

/**
 * Payment Result Value Object
 * 
 * Represents the result of a payment transaction.
 * 
 * @package SellNow\Payment
 */
class PaymentResult
{
    /**
     * Success status
     * @var bool
     */
    private bool $success;

    /**
     * Transaction ID
     * @var string|null
     */
    private ?string $transactionId;

    /**
     * Error message (if failed)
     * @var string|null
     */
    private ?string $errorMessage;

    /**
     * Additional data
     * @var array
     */
    private array $data;

    /**
     * Constructor
     * 
     * @param bool $success Success status
     * @param string|null $transactionId Transaction ID
     * @param string|null $errorMessage Error message
     * @param array $data Additional data
     */
    public function __construct(
        bool $success,
        ?string $transactionId = null,
        ?string $errorMessage = null,
        array $data = []
    ) {
        $this->success = $success;
        $this->transactionId = $transactionId;
        $this->errorMessage = $errorMessage;
        $this->data = $data;
    }

    /**
     * Check if payment was successful
     * 
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Get transaction ID
     * 
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * Get error message
     * 
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Get additional data
     * 
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
