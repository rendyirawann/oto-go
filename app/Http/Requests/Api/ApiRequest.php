<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base request for the JSON API: Indonesian validation messages regardless of
 * which language files are installed, and authorization left to policies.
 */
abstract class ApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,string> */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'email' => ':attribute harus berupa alamat email yang valid.',
            'max' => ':attribute maksimal :max karakter.',
            'min' => ':attribute minimal :min karakter.',
            'confirmed' => 'Konfirmasi :attribute tidak cocok.',
            'unique' => ':attribute sudah terdaftar.',
            'integer' => ':attribute harus berupa angka bulat.',
            'exists' => ':attribute yang dipilih tidak valid.',
            'regex' => 'Format :attribute tidak valid.',
            'between' => ':attribute harus di antara :min dan :max.',
            'in' => ':attribute yang dipilih tidak valid.',
            'current_password' => 'Kata sandi saat ini salah.',
            'password.letters' => ':attribute harus mengandung minimal satu huruf.',
            'password.mixed' => ':attribute harus mengandung huruf besar dan huruf kecil.',
            'password.numbers' => ':attribute harus mengandung minimal satu angka.',
            'password.symbols' => ':attribute harus mengandung minimal satu simbol.',
            'password.uncompromised' => ':attribute ini pernah bocor di internet. Pilih kata sandi lain.',
            'different' => ':attribute harus berbeda dari kata sandi saat ini.',
        ];
    }

    /** @return array<string,string> */
    public function attributes(): array
    {
        return [
            'name' => 'Nama',
            'email' => 'Email',
            'phone' => 'Nomor HP',
            'password' => 'Kata sandi',
            'current_password' => 'Kata sandi saat ini',
            'device_name' => 'Nama perangkat',
            'vehicle_category_id' => 'Kategori kendaraan',
            'vehicle_model_id' => 'Model kendaraan',
            'plate_number' => 'Nomor polisi',
            'year' => 'Tahun',
            'color' => 'Warna',
            'nickname' => 'Nama panggilan kendaraan',
        ];
    }

    /** Indonesian mobile number: 08xx / +628xx / 628xx, 9-15 digits. */
    protected function phoneRule(): string
    {
        return 'regex:/^(\+62|62|0)8[0-9]{7,12}$/';
    }
}
