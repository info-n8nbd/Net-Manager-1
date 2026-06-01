<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DigitalAgreementService
{
    public function generateAgreement(Client $client): array
    {
        $template = $this->getAgreementTemplate();
        
        $content = str_replace([
            '{{CLIENT_NAME}}',
            '{{CLIENT_ID}}',
            '{{CLIENT_ADDRESS}}',
            '{{CLIENT_PHONE}}',
            '{{PACKAGE_NAME}}',
            '{{MONTHLY_FEE}}',
            '{{CONNECTION_DATE}}',
            '{{CURRENT_DATE}}',
            '{{COMPANY_NAME}}',
        ], [
            $client->name,
            $client->client_id,
            $client->address ?? 'N/A',
            $client->phone ?? 'N/A',
            $client->package?->name ?? 'N/A',
            $client->monthly_fee ?? $client->package?->price ?? 0,
            $client->connection_date ?? now()->format('Y-m-d'),
            now()->format('d M, Y'),
            config('app.name', 'SOFTYNEX'),
        ], $template);

        return [
            'client_id' => $client->id,
            'content' => $content,
            'requires_otp' => true,
            'requires_signature' => true,
        ];
    }

    public function sendOtp(int $clientId): string
    {
        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        DB::table('client_agreements')->updateOrInsert(
            ['client_id' => $clientId],
            [
                'otp_code' => bcrypt($otp),
                'otp_verified' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return $otp;
    }

    public function verifyOtp(int $clientId, string $otp): bool
    {
        $agreement = DB::table('client_agreements')
            ->where('client_id', $clientId)
            ->first();

        if (!$agreement) {
            return false;
        }

        if (password_verify($otp, $agreement->otp_code)) {
            DB::table('client_agreements')
                ->where('client_id', $clientId)
                ->update([
                    'otp_verified' => true,
                    'updated_at' => now(),
                ]);
            return true;
        }

        return false;
    }

    public function signAgreement(int $clientId, string $content, ?string $signatureImage = null, ?string $ipAddress = null, ?string $deviceInfo = null): bool
    {
        $agreement = DB::table('client_agreements')
            ->where('client_id', $clientId)
            ->first();

        if (!$agreement || !$agreement->otp_verified) {
            return false;
        }

        DB::table('client_agreements')
            ->where('client_id', $clientId)
            ->update([
                'agreement_content' => $content,
                'signature_image' => $signatureImage,
                'ip_address' => $ipAddress,
                'device_info' => $deviceInfo,
                'signed_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('client_timeline')->insert([
            'client_id' => $clientId,
            'event_type' => 'note',
            'title' => 'Digital Agreement Signed',
            'description' => 'Customer signed the service agreement digitally via OTP verification.',
            'metadata' => json_encode(['ip' => $ipAddress]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    public function getAgreementStatus(int $clientId): ?array
    {
        $agreement = DB::table('client_agreements')
            ->where('client_id', $clientId)
            ->first();

        if (!$agreement) {
            return null;
        }

        return [
            'otp_verified' => (bool) $agreement->otp_verified,
            'signed' => !is_null($agreement->signed_at),
            'signed_at' => $agreement->signed_at,
            'ip_address' => $agreement->ip_address,
        ];
    }

    protected function getAgreementTemplate(): string
    {
        return <<<AGREEMENT
SERVICE AGREEMENT

This Internet Service Agreement ("Agreement") is entered into on {{CURRENT_DATE}} between:

SERVICE PROVIDER:
{{COMPANY_NAME}}

CUSTOMER:
Name: {{CLIENT_NAME}}
Customer ID: {{CLIENT_ID}}
Address: {{CLIENT_ADDRESS}}
Phone: {{CLIENT_PHONE}}

SERVICE DETAILS:
Package: {{PACKAGE_NAME}}
Monthly Fee: ৳{{MONTHLY_FEE}}
Connection Date: {{CONNECTION_DATE}}

TERMS AND CONDITIONS:

1. SERVICE PROVISION
The Service Provider agrees to provide internet connectivity service to the Customer as per the selected package specifications.

2. PAYMENT TERMS
- Monthly fee is due on the billing date each month
- Payment must be made within 10 days of bill generation
- Late payment may result in service disconnection after 15 days
- Reconnection fee may apply for disconnected services

3. SERVICE USAGE
- The service is for personal/business use at the registered address only
- The Customer shall not use the service for illegal activities
- Bandwidth sharing with third parties is prohibited

4. EQUIPMENT
- Any equipment provided remains the property of the Service Provider
- Customer is responsible for safekeeping of installed equipment
- Damage due to negligence will be charged to the Customer

5. TERMINATION
- Either party may terminate with 30 days written notice
- Outstanding dues must be cleared before service termination

By signing this agreement, the Customer acknowledges having read, understood, and agreed to all terms and conditions.

Customer Signature: _______________________

Date: {{CURRENT_DATE}}

--- End of Agreement ---
AGREEMENT;
    }
}
