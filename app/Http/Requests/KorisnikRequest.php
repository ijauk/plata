<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class KorisnikRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $korisnik = $this->route('korisnik');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($korisnik)],
            'password' => [$korisnik ? 'nullable' : 'required', Password::min(8)],
            'is_admin' => ['boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function podaci(): array
    {
        $podaci = [...$this->safe()->only(['name', 'email']), 'is_admin' => $this->boolean('is_admin')];

        if ($this->filled('password')) {
            $podaci['password'] = $this->input('password');
        }

        return $podaci;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Upiši ime.',
            'email.required' => 'Upiši e-mail.',
            'email.email' => 'E-mail nije ispravan.',
            'email.unique' => 'Korisnik s tim e-mailom već postoji.',
            'password.required' => 'Upiši početnu lozinku.',
            'password.min' => 'Lozinka mora imati barem 8 znakova.',
        ];
    }
}
