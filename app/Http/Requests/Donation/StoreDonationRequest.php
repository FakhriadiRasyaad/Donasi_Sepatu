<?php

namespace App\Http\Requests\Donation;

use Illuminate\Foundation\Http\FormRequest;

class StoreDonationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nama_sepatu'       => ['required', 'string', 'max:150'],
            'ukuran'            => ['required', 'string', 'regex:/^\d{1,3}(\.\d)?$/'],
            'kondisi'           => ['required', 'integer', 'min:0', 'max:100'],
            'deskripsi'         => ['nullable', 'string', 'max:1000'],
            'metode_pengiriman' => ['required', 'in:antar_langsung,ekspedisi'],
            'nama_ekspedisi'    => ['required_if:metode_pengiriman,ekspedisi', 'nullable', 'string', 'max:100'],
            'foto_sepatu'       => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_sepatu.required'       => 'Nama sepatu tidak boleh kosong.',
            'ukuran.required'            => 'Ukuran sepatu tidak boleh kosong.',
            'ukuran.regex'               => 'Format ukuran tidak valid (contoh: 40, 41, 42.5).',
            'kondisi.required'           => 'Kondisi sepatu tidak boleh kosong.',
            'kondisi.min'                => 'Kondisi minimal 0.',
            'kondisi.max'                => 'Kondisi maksimal 100.',
            'metode_pengiriman.required' => 'Metode pengiriman wajib dipilih.',
            'metode_pengiriman.in'       => 'Metode pengiriman tidak valid.',
            'nama_ekspedisi.required_if' => 'Nama ekspedisi wajib diisi jika metode pengiriman adalah ekspedisi.',
            'foto_sepatu.required'       => 'Foto sepatu wajib di-upload.',
            'foto_sepatu.image'          => 'File harus berupa gambar.',
            'foto_sepatu.mimes'          => 'Format gambar harus JPG, PNG, atau WebP.',
            'foto_sepatu.max'            => 'Ukuran foto maksimal 5MB.',
        ];
    }
}
