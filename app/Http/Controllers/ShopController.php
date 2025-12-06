<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function __invoke(): View
    {
        return view('shop.index', [
            'products' => Product::orderBy('name')->get(),
        ]);
    }
}
