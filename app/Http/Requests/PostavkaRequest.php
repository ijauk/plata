<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\CitaDecimale;
use App\Models\Postavka;
use App\Services\OsobniOdbitak;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostavkaRequest extends FormRequest
{
    use CitaDecimale;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->procitajMjesec('vrijedi_od');
        $this->procitajDecimale(Postavka::PARAMETRI_KLJUCEVI);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vrijedi_od' => ['required', 'date', Rule::unique('postavke', 'vrijedi_od')->where('user_id', $this->user()->id)->ignore($this->route('postavka'))],
            ...array_fill_keys(Postavka::PARAMETRI_KLJUCEVI, ['required', 'numeric', 'min:0']),
            'fond_sati' => ['required', 'numeric', 'gt:0'],
            'broj_djece' => ['required', 'integer', 'min:0', 'max:'.OsobniOdbitak::NAJVISE_DJECE],
            'udio_djeca' => ['required', 'numeric', 'min:0', 'max:100'],
            'broj_uzdrzavanih' => ['required', 'integer', 'min:0', 'max:20'],
            'broj_invalidnosti' => ['required', 'integer', 'min:0', 'max:20'],
            'napomena' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['vrijedi_od' => 'vrijedi od', ...Postavka::PARAMETRI];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Polje „:attribute” je obavezno.',
            'numeric' => 'Polje „:attribute” mora biti broj.',
            'min' => 'Polje „:attribute” ne smije biti negativno.',
            'max' => 'Polje „:attribute” ne smije biti veće od :max.',
            'integer' => 'Polje „:attribute” mora biti cijeli broj.',
            'gt' => 'Polje „:attribute” mora biti veće od :value.',
            'vrijedi_od.unique' => 'Za taj mjesec već postoji zapis konstanti – uredi postojeći.',
        ];
    }
}
