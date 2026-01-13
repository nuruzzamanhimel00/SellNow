<?php

namespace SellNow\Payment\Gateways;

use SellNow\Payment\PaymentGatewayInterface;
use SellNow\Payment\PaymentResult;

/**
 * Razorpay Payment Gateway
 * 
 * Mock implementation of Razorpay payment gateway.
 * 
 * @package SellNow\Payment\Gateways
 */
class RazorpayGateway implements PaymentGatewayInterface
{
    private string $keyId;
    private string $keySecret;

    public function __construct(?string $keyId = null, ?string $keySecret = null)
    {
        $this->keyId = $keyId ?? $_ENV['RAZORPAY_KEY_ID'] ?? '';
        $this->keySecret = $keySecret ?? $_ENV['RAZORPAY_KEY_SECRET'] ?? '';
    }

    public function charge(float $amount, array $metadata = []): PaymentResult
    {
        $transactionId = 'razorpay_' . uniqid() . '_' . time();
        $success = (rand(1, 100) <= 95);

        if ($success) {
            return new PaymentResult(
                true,
                $transactionId,
                null,
                ['provider' => 'Razorpay', 'amount' => $amount]
            );
        } else {
            return new PaymentResult(false, null, 'Payment declined by Razorpay');
        }
    }

    public function refund(string $transactionId, ?float $amount = null): bool
    {
        return true;
    }

    public function getProviderName(): string
    {
        return 'Razorpay';
    }

    public function verifyWebhook(array $payload): bool
    {
        return true;
    }
}
