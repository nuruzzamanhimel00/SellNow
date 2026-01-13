<?php

namespace SellNow\Payment\Gateways;

use SellNow\Payment\PaymentGatewayInterface;
use SellNow\Payment\PaymentResult;

/**
 * Stripe Payment Gateway
 * 
 * Mock implementation of Stripe payment gateway.
 * In production, this would integrate with Stripe API.
 * 
 * @package SellNow\Payment\Gateways
 */
class StripeGateway implements PaymentGatewayInterface
{
    /**
     * API key
     * @var string
     */
    private string $apiKey;

    /**
     * Constructor
     * 
     * @param string|null $apiKey Stripe API key (from env if not provided)
     */
    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? $_ENV['STRIPE_API_KEY'] ?? '';
    }

    /**
     * Process a payment
     * 
     * @param float $amount Amount to charge
     * @param array $metadata Additional payment data
     * @return PaymentResult Payment result
     */
    public function charge(float $amount, array $metadata = []): PaymentResult
    {
        // Mock implementation - in production, call Stripe API
        // For now, simulate successful payment
        
        $transactionId = 'stripe_' . uniqid() . '_' . time();

        // Simulate 95% success rate
        $success = (rand(1, 100) <= 95);

        if ($success) {
            return new PaymentResult(
                true,
                $transactionId,
                null,
                [
                    'provider' => 'Stripe',
                    'amount' => $amount,
                    'currency' => 'USD',
                    'metadata' => $metadata
                ]
            );
        } else {
            return new PaymentResult(
                false,
                null,
                'Payment declined by Stripe',
                ['provider' => 'Stripe']
            );
        }
    }

    /**
     * Refund a payment
     * 
     * @param string $transactionId Transaction ID to refund
     * @param float|null $amount Amount to refund
     * @return bool True on success
     */
    public function refund(string $transactionId, ?float $amount = null): bool
    {
        // Mock implementation
        return true;
    }

    /**
     * Get payment provider name
     * 
     * @return string Provider name
     */
    public function getProviderName(): string
    {
        return 'Stripe';
    }

    /**
     * Verify payment webhook
     * 
     * @param array $payload Webhook payload
     * @return bool True if valid
     */
    public function verifyWebhook(array $payload): bool
    {
        // Mock implementation - in production, verify Stripe signature
        return true;
    }
}
