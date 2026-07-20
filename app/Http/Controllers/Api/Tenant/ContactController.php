<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\ContactRequest;
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
}
