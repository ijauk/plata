<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\CitaDecimale;
use App\Models\Obracun;
use App\Models\Postavka;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ObracunRequest extends FormRequest
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
        $this->procitajMjesec('period');
        $this->procitajDecimale([
            ...array_keys(Obracun::SATI), 'ucinak', 'ostalo_bruto', 'ostalo_sati',
            'prethodni_bruto', 'prethodni_sati', 'stvarni_bruto', 'stvarna_isplata',
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $obracun = $this->route('obracun');

        return [
            'period' => ['required', 'date', Rule::unique('obracuni', 'period')->where('user_id', $this->user()->id)->ignore($obracun)],
            ...array_fill_keys(array_keys(Obracun::SATI), ['required', 'numeric', 'min:0', 'max:744']),
            'ucinak' => ['required', 'numeric', 'min:0', 'max:100'],
            'ostalo_bruto' => ['nullable', 'numeric'],
            'ostalo_sati' => ['nullable', 'numeric', 'min:0', 'max:744'],
            'prethodni_bruto' => ['nullable', 'numeric', 'min:0', 'required_with:prethodni_sati'],
            'prethodni_sati' => ['nullable', 'numeric', 'gt:0', 'required_with:prethodni_bruto'],
            'stvarni_bruto' => ['nullable', 'numeric', 'min:0'],
            'stvarna_isplata' => ['nullable', 'numeric', 'min:0'],
            'napomena' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $period = Carbon::parse($this->input('period'));

                if (Postavka::vazeceZa($this->user()->id, $period) === null) {
                    $validator->errors()->add('period', 'Za ovaj mjesec nema unesenih konstanti (osnovica, koeficijent…).');
                }

                $trebaPrethodni = collect(['sati_blagdan', 'sati_dopust', 'sati_godisnji', 'sati_bolovanje'])
                    ->sum(fn (string $polje): float => (float) $this->input($polje)) > 0;
                $imaPrethodni = $this->filled('prethodni_bruto')
                    || $this->user()->obracuni()->whereDate('period', $period->copy()->subMonthNoOverflow()->toDateString())->exists();

                if ($trebaPrethodni && ! $imaPrethodni) {
                    $validator->errors()->add('prethodni_bruto', 'Nema obračuna za prethodni mjesec – upiši bruto i sate prethodnog mjeseca s platne liste.');
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function podaci(): array
    {
        return [
            ...$this->validated(),
            'ostalo_bruto' => (float) $this->input('ostalo_bruto', 0),
            'ostalo_sati' => (float) $this->input('ostalo_sati', 0),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'period' => 'mjesec',
            ...array_map('mb_strtolower', Obracun::SATI),
            'ucinak' => 'učinak',
            'prethodni_bruto' => 'bruto prethodnog mjeseca',
            'prethodni_sati' => 'sati prethodnog mjeseca',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Polje „:attribute” je obavezno.',
            'numeric' => 'Polje „:attribute” mora biti broj.',
            'min' => 'Polje „:attribute” ne smije biti manje od :min.',
            'max' => 'Polje „:attribute” ne smije biti veće od :max.',
            'gt' => 'Polje „:attribute” mora biti veće od :value.',
            'required_with' => 'Upiši i bruto i sate prethodnog mjeseca.',
            'period.unique' => 'Obračun za taj mjesec već postoji.',
        ];
    }
}
