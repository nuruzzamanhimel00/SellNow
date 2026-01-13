<?php

namespace SellNow\Payment;

/**
 * Payment Gateway Interface
 * 
 * Contract for all payment gateway implementations.
 * Allows swapping payment providers without changing business logic.
 * 
 * @package SellNow\Payment
 */
interface PaymentGatewayInterface
{
    /**
     * Process a payment
     * 
     * @param float $amount Amount to charge
     * @param array $metadata Additional payment data (order_id, customer_info, etc.)
     * @return PaymentResult Payment result
     */
    public function charge(float $amount, array $metadata = []): PaymentResult;

    /**
     * Refund a payment
     * 
     * @param string $transactionId Transaction ID to refund
     * @param float|null $amount Amount to refund (null for full refund)
     * @return bool True on success
     */
    public function refund(string $transactionId, ?float $amount = null): bool;

    /**
     * Get payment provider name
     * 
     * @return string Provider name (e.g., "Stripe", "PayPal")
     */
    public function getProviderName(): string;

    /**
     * Verify payment webhook/callback
     * 
     * @param array $payload Webhook payload
     * @return bool True if valid
     */
    public function verifyWebhook(array $payload): bool;
}
