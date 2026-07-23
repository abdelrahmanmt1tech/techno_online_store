<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\ContactRequest;
use App\Http\Resources\Tenant\ContactUsResource;
use App\Models\Setting;
use App\Models\Tenant\Contact;
use App\Traits\ApiResponse;

class ContactController extends Controller
{
    use ApiResponse;

    public function store(ContactRequest $request)
    {
        $data = $request->validated();
        Contact::create($data);

        return $this->createdResponse(null, __('messages.successfully'));
    }

    public function contactUs()
    {
        $keys = [
            'contact_us_title',
            'contact_us_description',
            'contact_us_image',
            'contact_us_email',
            'contact_us_phone',
            'contact_us_whatsapp',
        ];

        $settings = collect();

        foreach ($keys as $key) {
            $settings[$key] = Setting::where('key', $key)->value('value') ?? '';
        }

        return $this->successResponse(ContactUsResource::make($settings));
    }
}
