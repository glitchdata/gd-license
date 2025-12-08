<?php

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use RuntimeException;

class PayPalClient
{
    public function __construct(
        private CacheRepository $cache,
        private HttpFactory $http
    ) {
    }

    /**
     * Create a PayPal order for the provided amount.
     */
    public function createOrder(float $amount, string $currency, string $description): array
    {
        if ($amount <= 0) {
            throw new RuntimeException('Unable to create PayPal order for zero total.');
        }

        $payload = [
            'intent' => $this->intent(),
            'purchase_units' => [{}
                'amount' => [
                    'currency_code' => strtoupper($currency),
                    'value' => number_format($amount, 2, '.', ''),
                ],
                'description' => $description,
             }],
        ];

        $response = $this->authorizedRequest()->post('/v2/checkout/orders', $payload);

        if (! $response->successful()) {
            throw new RuntimeException($this->errorFromResponse($response));
        }

        return $response->json();
    }

    /**
     * Capture a previously created PayPal order.
     */
    public function captureOrder(string $orderId): array
    {
        $response = $this->authorizedRequest()->post("/v2/checkout/orders/{$orderId}/capture");

        if (! $response->successful()) {
            throw new RuntimeException($this->errorFromResponse($response));
        }

        return $response->json();
    }

    private function authorizedRequest(): PendingRequest
    {
        return $this->http
            ->baseUrl($this->baseUrl())
            ->acceptJson()
            ->asJson()
            ->withToken($this->accessToken());
    }

    private function accessToken(): string
    {
        $environment = config('paypal.environment', 'sandbox');
        $cacheKey = 'paypal:token:'.$environment;

        if ($token = $this->cache->get($cacheKey)) {
            return $token;
        }

        $response = $this->http
            ->asForm()
            ->withBasicAuth($this->clientId(), $this->secret())
            ->post($this->baseUrl().'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->errorFromResponse($response));
        }

        $token = (string) $response->json('access_token');
        if ($token === '') {
            throw new RuntimeException('PayPal did not return an access token.');
        }

        $expiresIn = (int) $response->json('expires_in', 600);
        $this->cache->put($cacheKey, $token, now()->addSeconds(max(60, $expiresIn - 60)));

        return $token;
    }

    private function baseUrl(): string
    {
        $environment = config('paypal.environment', 'sandbox');

        return config('paypal.base_urls.'.($environment === 'live' ? 'live' : 'sandbox'));
    }

    private function clientId(): string
    {
        $clientId = (string) config('paypal.client_id');

        if ($clientId === '') {
            throw new RuntimeException('PAYPAL_CLIENT_ID is not configured.');
        }

        return $clientId;
    }

    private function secret(): string
    {
        $secret = (string) config('paypal.secret');

        if ($secret === '') {
            throw new RuntimeException('PAYPAL_SECRET is not configured.');
        }

        return $secret;
    }

    private function intent(): string
    {
        $intent = strtoupper((string) config('paypal.intent', 'CAPTURE'));

        return $intent === 'AUTHORIZE' ? 'AUTHORIZE' : 'CAPTURE';
    }

    private function errorFromResponse(Response $response): string
    {
        $json = $response->json();

        if (is_array($json)) {
            if (! empty($json['message'])) {
                return (string) $json['message'];
            }

            $details = $json['details'][0]['issue'] ?? null;
            if ($details) {
                return (string) $details;
            }
        }

        return 'PayPal API error ('.$response->status().').';
    }
}
