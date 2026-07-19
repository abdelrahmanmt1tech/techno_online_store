<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PlatformLandingController extends Controller
{
    public function __invoke(): View
    {
        return view('platform.index', [
            'supportEmail' => config('app.support_email', 'support@technowebmasr.com'),
            'companyUrl' => 'https://technomasr.com',
            'contactUrl' => 'https://technomasr.com/en/contact/product-1773666026-noxmd',
            'platformUrl' => 'https://online-store.technomasrsystems.com',
            'canonicalUrl' => 'https://online-store.technomasrsystems.com/platform',
            'privacyUrl' => 'https://online-store.technomasrsystems.com/privacy-policy',
            'termsUrl' => 'https://online-store.technomasrsystems.com/terms-of-service',
            'deletionUrl' => 'https://online-store.technomasrsystems.com/data-deletion',
            'companyProductUrl' => 'https://technomasr.com/techno-online-store.html',
        ]);
    }
}
