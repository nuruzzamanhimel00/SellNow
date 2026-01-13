<?php

namespace SellNow\Payment\Gateways;

use SellNow\Payment\PaymentGatewayInterface;
use SellNow\Payment\PaymentResult;

/**
 * PayPal Payment Gateway
 * 
 * Mock implementation of PayPal payment gateway.
 * 
 * @package SellNow\Payment\Gateways
 */
class PayPalGateway implements PaymentGatewayInterface
{
    private string $clientId;
    private string $secret;

    public function __construct(?string $clientId = null, ?string $secret = null)
    {
        $this->clientId = $clientId ?? $_ENV['PAYPAL_CLIENT_ID'] ?? '';
        $this->secret = $secret ?? $_ENV['PAYPAL_SECRET'] ?? '';
    }

    public function charge(float $amount, array $metadata = []): PaymentResult
    {
        $transactionId = 'paypal_' . uniqid() . '_' . time();
        $success = (rand(1, 100) <= 95);

        if ($success) {
            return new PaymentResult(
                true,
                $transactionId,
                null,
                ['provider' => 'PayPal', 'amount' => $amount]
            );
        } else {
            return new PaymentResult(false, null, 'Payment declined by PayPal');
        }
    }

    public function refund(string $transactionId, ?float $amount = null): bool
    {
        return true;
    }

    public function getProviderName(): string
    {
        return 'PayPal';
    }

    public function verifyWebhook(array $payload): bool
    {
        return true;
    }
}
