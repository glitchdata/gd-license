<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Stripe\StripeClient as BaseStripeClient;
use Stripe\PaymentIntent;

class StripeClient
{
    public function __construct(private BaseStripeClient $client)
    {
    }

    public static function make(): self
    {
        $secret = config('stripe.secret');
        if (! $secret) {
            throw new RuntimeException('STRIPE_SECRET is not configured.');
        }

        return new self(new BaseStripeClient($secret));
    }

    public function createPaymentIntent(int $amountCents, string $currency, array $metadata = []): PaymentIntent
    {
        try {
            return $this->client->paymentIntents->create([
                'amount' => $amountCents,
                'currency' => strtolower($currency),
                'metadata' => $metadata,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Stripe createPaymentIntent failed', [
                'amount_cents' => $amountCents,
                'currency' => $currency,
                'metadata' => $metadata,
                'message' => $e->getMessage(),
            ]);
            throw new RuntimeException('Unable to create a Stripe payment: '.$e->getMessage());
        }
    }

    public function retrievePaymentIntent(string $id): PaymentIntent
    {
        try {
            return $this->client->paymentIntents->retrieve($id);
        } catch (\Throwable $e) {
            Log::warning('Stripe retrievePaymentIntent failed', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);
            throw new RuntimeException('Unable to verify Stripe payment: '.$e->getMessage());
        }
    }
}
