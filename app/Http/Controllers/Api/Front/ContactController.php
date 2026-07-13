<?php

namespace App\Http\Controllers\Api\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Models\Contact;
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
