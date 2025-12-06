<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseLicenseRequest;
use App\Models\License;
use App\Models\Product;
use App\Services\FakePaymentGateway;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserLicenseController extends Controller
{
    public function __construct(private FakePaymentGateway $payments)
    {
    }

    public function show(License $license): View
    {
        abort_unless($license->user_id === Auth::id(), 404);

        return view('licenses.show', [
            'license' => $license->load(['product', 'user']),
        ]);
    }

    public function store(PurchaseLicenseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $product = Product::findOrFail($data['product_id']);
        $seats = (int) $data['seats_total'];
        $total = round((float) $product->price * $seats, 2);

        $cardNumber = preg_replace('/\D/', '', $data['card_number']);

        try {
            $transactionId = $this->payments->charge([
                'name' => $data['card_name'],
                'number' => $cardNumber,
                'exp_month' => $data['card_exp_month'],
                'exp_year' => $data['card_exp_year'],
                'cvc' => $data['card_cvc'],
            ], (int) round($total * 100));
        } catch (Exception $e) {
            return back()
                ->withErrors(['payment' => $e->getMessage()])
                ->withInput();
        }

        $duration = max(1, (int) ($product->duration_months ?? 12));

        $license = License::create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'seats_total' => $seats,
            'seats_used' => 0,
            'expires_at' => now()->addMonths($duration),
        ]);

        if ($domain = $data['domain'] ?? null) {
            $normalized = strtolower(trim($domain));
            if ($normalized !== '') {
                $license->domains()->create(['domain' => $normalized]);
            }
        }

        return redirect()
            ->route('licenses.show', $license)
            ->with('status', 'License purchased successfully. Transaction '.$transactionId.' · Total $'.number_format($total, 2));
    }
}
