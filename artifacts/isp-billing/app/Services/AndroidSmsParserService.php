<?php

namespace App\Services;

class AndroidSmsParserService
{
    public function parse(string $rawSms): array
    {
        $sms = trim($rawSms);
        $lower = strtolower($sms);
        $gateway = $this->detectGateway($lower);
        $amount = $this->extractAmount($sms, $gateway);
        $sender = $this->extractSender($sms);
        $reference = $this->extractReference($sms);
        $trxId = $this->extractTrxId($sms, $gateway);
        return [
            'gateway'   => $gateway,
            'amount'    => $amount,
            'sender'    => $sender,
            'reference' => $reference,
            'trx_id'    => $trxId,
        ];
    }

    private function detectGateway(string $lower): string
    {
        if (str_contains($lower, 'bkash') || str_contains($lower, 'bikash')) return 'bkash';
        if (str_contains($lower, 'nagad')) return 'nagad';
        if (str_contains($lower, 'rocket') || str_contains($lower, 'dbbl')) return 'rocket';

        // TxnID = Nagad, TrxID = bKash — check before currency keyword
        if (preg_match('/txnid|txn\s*id/i', $lower)) return 'nagad';
        if (preg_match('/tnxid|tnx\s*id/i', $lower)) return 'nagad';
        if (preg_match('/trxid|trx\s*id/i', $lower)) return 'bkash';

        if (preg_match('/\bbdt\s*[\d,]+/i', $lower)) return 'nagad';
        if (preg_match('/\btk\s*[\d,]+/i', $lower)) return 'bkash';

        return 'unknown';
    }

    private function extractAmount(string $sms, string $gateway): ?float
    {
        $patterns = [
            '/(?:received|got|payment of|amount[:\s]+)\s*(?:Tk\.?|BDT\.?|৳)?\s*([\d,]+\.?\d*)/i',
            '/(?:Tk\.?|BDT\.?|৳)\s*([\d,]+\.?\d*)/i',
            '/([\d,]+\.?\d*)\s*(?:Tk\.?|BDT\.?|৳|taka)/i',
            '/amount[:\s]+([\d,]+\.?\d*)/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $sms, $m)) {
                $amount = (float) str_replace(',', '', $m[1]);
                if ($amount > 0 && $amount < 1000000) return $amount;
            }
        }
        return null;
    }

    private function extractSender(string $sms): ?string
    {
        if (preg_match('/(?:from|sender)[:\s]+([0-9+\s]{10,15})/i', $sms, $m))
            return preg_replace('/\s+/', '', $m[1]);
        if (preg_match('/\b(01[3-9]\d{8})\b/', $sms, $m)) return $m[1];
        if (preg_match('/\+88(01[3-9]\d{8})\b/', $sms, $m)) return $m[1];
        return null;
    }

    // Values that mean "no reference was given" — treat as null
    private const EMPTY_REFS = ['n/a', 'na', 'none', 'null', 'nill', 'nil', '-', '--', 'no', 'not', 'n.a', 'n.a.'];

    private function extractReference(string $sms): ?string
    {
        $patterns = [
            '/(?:Ref(?:erence)?|ref)[:\s\.]+([A-Za-z0-9_\-\.\/]+)/i',
            '/(?:A\/C|Account)[:\s]+([A-Za-z0-9_\-]+)/i',
            '/(?:Note|Remark)[:\s]+([A-Za-z0-9_\-]+)/i',
            '/PPPoE[:\s]+([A-Za-z0-9_\-]+)/i',
            '/Username[:\s]+([A-Za-z0-9_\-]+)/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $sms, $m)) {
                $ref = trim($m[1]);
                // Skip placeholder/empty reference values (e.g. "Ref: N/A" in Nagad)
                if (in_array(strtolower($ref), self::EMPTY_REFS)) continue;
                // Skip Bangladesh phone numbers (01XXXXXXXXX) — these are account/sender numbers, not client refs
                if (preg_match('/^01[3-9]\d{8}$/', $ref)) continue;
                // Strip trailing punctuation (dots, commas, etc.)
                $ref = rtrim($ref, '.,:;!?');
                if (strlen($ref) >= 2 && strlen($ref) <= 50) return $ref;
            }
        }
        return null;
    }

    private function extractTrxId(string $sms, string $gateway): ?string
    {
        $patterns = [
            '/(?:TxnID|Txn\s*ID|TrxID|Trx\s*ID|TnxID|Tnx\s*ID|Transaction\s*ID|TID)[:\s]+([A-Z0-9]+)/i',
            '/\b([A-Z]{2,4}[0-9]{8,})\b/',
            '/\b([0-9A-Z]{10,20})\b/',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $sms, $m)) return $m[1];
        }
        return null;
    }

    public function parseReference(string $reference): array
    {
        $parts = preg_split('/[_\-\s]+/', $reference, 2);
        $lookups = [$reference];
        if (count($parts) === 2) {
            $lookups[] = $parts[0];
            if (is_numeric($parts[1])) $lookups[] = $parts[1];
        }
        if (is_numeric($reference)) $lookups[] = $reference;
        return array_unique($lookups);
    }
}
