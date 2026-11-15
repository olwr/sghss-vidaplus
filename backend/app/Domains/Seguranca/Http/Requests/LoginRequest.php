<?php

namespace App\Domains\Seguranca\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // rota pública (protegida por throttle:login)
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:150'],
            'password' => ['required', 'string', 'max:255']
        ];
    }
}
