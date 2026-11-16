<?php

namespace App\Http\Requests;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Public contact form submission.
 *
 * Authorises for everyone — this is an anonymous lead capture form, not an
 * admin action. Abuse is limited by the `public-lead` rate limiter and a
 * honeypot field rather than by authentication, because requiring a login
 * would defeat the purpose of a contact form.
 */
class StorePublicLeadRequest extends FormRequest
{
    public const HONEYPOT_FIELD = 'company_website';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * A filled honeypot means a bot. Reject before validation so the response
     * never reveals which field tripped.
     */
    public function isSpam(): bool
    {
        $value = $this->input(self::HONEYPOT_FIELD);

        return is_string($value) && trim($value) !== '';
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:255'],
            'whatsapp_number' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\s-]{8,30}$/'],
            'lead_type' => ['nullable', Rule::in(Lead::TYPES)],
            'requirements' => ['required', 'string', 'min:10', 'max:2000'],
            'consent' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_name' => 'nama',
            'whatsapp_number' => 'nomor WhatsApp',
            'lead_type' => 'jenis kebutuhan',
            'requirements' => 'kebutuhan',
            'consent' => 'persetujuan',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Nama perlu diisi agar kami tahu harus memanggil siapa.',
            'customer_name.min' => 'Nama minimal 2 karakter.',
            'whatsapp_number.required' => 'Nomor WhatsApp perlu diisi agar kami bisa menghubungi Anda.',
            'whatsapp_number.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, tanda hubung, dan tanda kurung.',
            'requirements.required' => 'Ceritakan sedikit kebutuhan Anda, misalnya jumlah kamera dan lokasi pemasangan.',
            'requirements.min' => 'Mohon tuliskan kebutuhan minimal 10 karakter agar kami bisa memberi saran yang tepat.',
            'requirements.max' => 'Kebutuhan maksimal 2000 karakter.',
            'consent.accepted' => 'Mohon setujui penggunaan data untuk keperluan menindaklanjuti permintaan Anda.',
        ];
    }
}
