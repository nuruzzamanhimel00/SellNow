<?php

namespace SellNow\Services;

use SellNow\Payment\PaymentGatewayInterface;
use SellNow\Payment\Gateways\StripeGateway;
use SellNow\Payment\Gateways\PayPalGateway;
use SellNow\Payment\Gateways\RazorpayGateway;
use SellNow\Repositories\OrderRepository;
use Exception;

/**
 * Payment Service
 * 
 * Orchestrates payment processing using strategy pattern.
 * Manages order creation and payment gateway selection.
 * 
 * @package SellNow\Services
 */
class PaymentService
{
    /**
     * Order repository
     * @var OrderRepository
     */
    private OrderRepository $orderRepository;

    /**
     * Available payment gateways
     * @var array<string, PaymentGatewayInterface>
     */
    private array $gateways = [];

    /**
     * Constructor
     * 
     * @param OrderRepository $orderRepository Order repository
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
        
        // Register available payment gateways
        $this->registerGateway('stripe', new StripeGateway());
        $this->registerGateway('paypal', new PayPalGateway());
        $this->registerGateway('razorpay', new RazorpayGateway());
    }

    /**
     * Register a payment gateway
     * 
     * @param string $name Gateway name
     * @param PaymentGatewayInterface $gateway Gateway instance
     * @return void
     */
    public function registerGateway(string $name, PaymentGatewayInterface $gateway): void
    {
        $this->gateways[strtolower($name)] = $gateway;
    }

    /**
     * Get available payment providers
     * 
     * @return array Provider names
     */
    public function getAvailableProviders(): array
    {
        return array_keys($this->gateways);
    }

    /**
     * Process payment for cart
     * 
     * @param int $userId User ID
     * @param float $amount Total amount
     * @param string $provider Payment provider name
     * @param array $cartItems Cart items
     * @return array ['success' => bool, 'order_id' => int|null, 'error' => string|null]
     */
    public function processPayment(int $userId, float $amount, string $provider, array $cartItems): array
    {
        // Validate provider
        $gateway = $this->getGateway($provider);
        
        if (!$gateway) {
            return [
                'success' => false,
                'order_id' => null,
                'error' => 'Invalid payment provider.'
            ];
        }

        // Create order first (pending status)
        $orderId = $this->orderRepository->create([
            'user_id' => $userId,
            'total_amount' => $amount,
            'payment_provider' => $gateway->getProviderName(),
            'payment_status' => 'pending'
        ]);

        if (!$orderId) {
            return [
                'success' => false,
                'order_id' => null,
                'error' => 'Failed to create order.'
            ];
        }

        // Process payment through gateway
        try {
            $result = $gateway->charge($amount, [
                'order_id' => $orderId,
                'user_id' => $userId,
                'items' => $cartItems
            ]);

            if ($result->isSuccessful()) {
                // Update order with transaction ID and completed status
                $this->orderRepository->updatePaymentStatus(
                    (int)$orderId,
                    'completed',
                    $result->getTransactionId()
                );

                return [
                    'success' => true,
                    'order_id' => (int)$orderId,
                    'transaction_id' => $result->getTransactionId(),
                    'error' => null
                ];
            } else {
                // Update order to failed status
                $this->orderRepository->updatePaymentStatus((int)$orderId, 'failed');

                return [
                    'success' => false,
                    'order_id' => (int)$orderId,
                    'error' => $result->getErrorMessage() ?? 'Payment failed.'
                ];
            }

        } catch (Exception $e) {
            // Update order to failed status
            $this->orderRepository->updatePaymentStatus((int)$orderId, 'failed');

            return [
                'success' => false,
                'order_id' => (int)$orderId,
                'error' => 'Payment processing error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get payment gateway by name
     * 
     * @param string $name Gateway name
     * @return PaymentGatewayInterface|null Gateway instance or null
     */
    private function getGateway(string $name): ?PaymentGatewayInterface
    {
        return $this->gateways[strtolower($name)] ?? null;
    }

    /**
     * Get order by ID
     * 
     * @param int $orderId Order ID
     * @return array|null Order data or null
     */
    public function getOrder(int $orderId): ?array
    {
        return $this->orderRepository->find($orderId);
    }

    /**
     * Get user orders
     * 
     * @param int $userId User ID
     * @return array Array of orders
     */
    public function getUserOrders(int $userId): array
    {
        return $this->orderRepository->findByUserId($userId);
    }
}
