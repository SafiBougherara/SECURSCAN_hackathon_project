<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale)
    {
        if (!in_array($locale, ['en', 'fr'])) {
            abort(404);
        }

        session(['locale' => $locale]);

        return redirect()->back()->withHeaders([
            'Cache-Control' => 'no-cache',
        ]);
    }
}
