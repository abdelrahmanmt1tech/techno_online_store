<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalPageController extends Controller
{
    public function privacyPolicy(): View
    {
        return $this->page('legal.privacy-policy');
    }

    public function termsOfService(): View
    {
        return $this->page('legal.terms-of-service');
    }

    public function dataDeletion(): View
    {
        return $this->page('legal.data-deletion');
    }

    protected function page(string $view): View
    {
        return view($view, [
            'supportEmail' => config('app.support_email', 'support@technowebmasr.com'),
        ]);
    }
}
