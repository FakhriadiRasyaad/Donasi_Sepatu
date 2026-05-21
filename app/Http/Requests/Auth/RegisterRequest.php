<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nama'     => ['required', 'string', 'min:2', 'max:100'],
            'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[0-9]/'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required'     => 'Nama tidak boleh kosong.',
            'nama.min'          => 'Nama minimal 2 karakter.',
            'email.required'    => 'Email tidak boleh kosong.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email sudah terdaftar. Gunakan email lain atau login.',
            'password.required' => 'Password tidak boleh kosong.',
            'password.min'      => 'Password minimal 8 karakter.',
            'password.regex'    => 'Password harus mengandung minimal 1 huruf kapital dan 1 angka.',
        ];
    }
}
