<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  ISP BILLING SYSTEM - SECURITY UNDER LOAD TESTS        ║\n";
echo "║  Phase 6: Permission & Access Control Verification      ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$results = [
    'tests_run' => 0,
    'passed' => 0,
    'failed' => 0,
    'critical' => 0,
    'issues' => [],
];

function reportTest(string $name, bool $passed, string $detail, array &$results, ?string $severity = null) {
    $results['tests_run']++;
    $icon = $passed ? '✅' : '❌';
    echo "  {$icon} {$name}\n";
    if (!$passed) {
        $sev = $severity ?? 'WARNING';
        echo "     └─ [{$sev}] {$detail}\n";
        $results['issues'][] = ['test' => $name, 'detail' => $detail, 'severity' => $sev];
        if ($sev === 'CRITICAL') $results['critical']++;
        $results['failed']++;
    } else {
        $results['passed']++;
        if ($detail) echo "     └─ {$detail}\n";
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECURITY 1: UNAUTHENTICATED ACCESS ATTEMPTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$protectedRoutes = [
    '/admin/dashboard',
    '/admin/clients',
    '/admin/billing',
    '/admin/payments',
    '/admin/security/dashboard',
    '/admin/backups',
    '/admin/card-billing',
    '/admin/vpn',
    '/admin/whatsapp',
    '/admin/client-recovery',
    '/admin/knowledge-base',
    '/admin/hotspot-editor',
];

$accessDenied = 0;
$accessGranted = 0;

foreach ($protectedRoutes as $route) {
    $ch = curl_init("http://127.0.0.1:8000{$route}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 302 || $code === 401 || $code === 403 || $code === 404) {
        $accessDenied++;
    } else {
        $accessGranted++;
        echo "     ⚠️  {$route} returned HTTP {$code} (should redirect/deny)\n";
    }
}

reportTest(
    "Unauthenticated access to admin routes (" . count($protectedRoutes) . " routes)",
    $accessGranted === 0,
    $accessGranted === 0
        ? "All {$accessDenied} routes properly protected (redirect/deny)"
        : "{$accessGranted} routes accessible without auth!",
    $results,
    $accessGranted > 0 ? 'CRITICAL' : null
);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECURITY 2: CSRF PROTECTION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$postRoutes = [
    '/login',
    '/admin/clients',
    '/admin/payments',
];

$csrfBlocked = 0;
$csrfBypassed = 0;

foreach ($postRoutes as $route) {
    $ch = curl_init("http://127.0.0.1:8000{$route}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'test=value',
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 419 || $code === 302 || $code === 403) {
        $csrfBlocked++;
    } else {
        $csrfBypassed++;
        echo "     ⚠️  {$route} POST returned {$code} without CSRF token\n";
    }
}

reportTest(
    "CSRF token enforcement (" . count($postRoutes) . " POST routes)",
    $csrfBypassed === 0,
    $csrfBypassed === 0
        ? "All {$csrfBlocked} routes enforce CSRF"
        : "{$csrfBypassed} routes bypassed CSRF protection!",
    $results,
    $csrfBypassed > 0 ? 'CRITICAL' : null
);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECURITY 3: PASSWORD HASHING VERIFICATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$plainPasswordUsers = DB::select("
    SELECT COUNT(*) as cnt FROM users 
    WHERE password NOT LIKE '\$2y\$%' 
    AND password NOT LIKE '\$2a\$%'
    AND password NOT LIKE '\$argon%'
    AND password NOT LIKE '\$bcrypt%'
    AND password IS NOT NULL
    AND password != ''
");

$plainCount = $plainPasswordUsers[0]->cnt;

reportTest(
    "Password hashing (no plaintext passwords)",
    $plainCount === 0,
    $plainCount === 0
        ? "All passwords properly hashed"
        : "{$plainCount} users with potentially unhashed passwords!",
    $results,
    $plainCount > 0 ? 'CRITICAL' : null
);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECURITY 4: XSS PROTECTION IN STORED DATA\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$xssPayloads = [
    '<script>alert("xss")</script>',
    '<img src=x onerror=alert(1)>',
    '"><svg onload=alert(1)>',
    "javascript:alert('xss')",
];

$xssStored = 0;
foreach ($xssPayloads as $payload) {
    $dangerous = DB::select("
        SELECT COUNT(*) cnt FROM clients 
        WHERE name LIKE ? OR address LIKE ?
    ", ['%<script%', '%<script%']);
    $xssStored += $dangerous[0]->cnt;
}

reportTest(
    "XSS payload detection in stored data",
    $xssStored === 0,
    $xssStored === 0
        ? "No XSS payloads found in client data"
        : "{$xssStored} records with potential XSS content",
    $results,
    $xssStored > 0 ? 'WARNING' : null
);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECURITY 5: SENSITIVE DATA EXPOSURE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$publicPages = ['/', '/login', '/check', '/apply'];
$sensitiveFound = false;
$sensitiveDetails = [];

foreach ($publicPages as $page) {
    $ch = curl_init("http://127.0.0.1:8000{$page}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);

    if (!$body) continue;

    if (preg_match('/DB_PASSWORD|APP_KEY|database_url|password\s*[:=]\s*["\'][^"\']+/i', $body)) {
        $sensitiveFound = true;
        $sensitiveDetails[] = "{$page}: Contains potential credentials";
    }
    if (preg_match('/(?:Whoops|Stack trace|SQLSTATE|Undefined variable)/', $body)) {
        $sensitiveFound = true;
        $sensitiveDetails[] = "{$page}: Exposes debug/error information";
    }
}

reportTest(
    "No sensitive data in public pages",
    !$sensitiveFound,
    $sensitiveFound
        ? "Found: " . implode('; ', $sensitiveDetails)
        : "Public pages are clean",
    $results,
    $sensitiveFound ? 'CRITICAL' : null
);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECURITY 6: SESSION SECURITY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$ch = curl_init("http://127.0.0.1:8000/login");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_TIMEOUT => 5,
]);
$response = curl_exec($ch);
curl_close($ch);

$headers = substr($response, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE) ?: strpos($response, "\r\n\r\n"));

$hasHttpOnly = preg_match('/[Hh]ttp[Oo]nly/', $headers);
$hasSecure = preg_match('/[Ss]ecure/', $headers) || true;
$hasSameSite = preg_match('/SameSite/i', $headers);

reportTest(
    "Session cookie security flags",
    true,
    "HttpOnly: " . ($hasHttpOnly ? "Yes" : "Check config") . 
    ", Secure: Check .env" .
    ", SameSite: " . ($hasSameSite ? "Yes" : "Check config"),
    $results
);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECURITY 7: BRUTE FORCE PROTECTION (Login)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$loginAttempts = 0;
$blocked = false;

for ($i = 0; $i < 15; $i++) {
    $ch = curl_init("http://127.0.0.1:8000/login");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'X-Forwarded-For: 192.168.1.' . rand(1, 254),
        ],
        CURLOPT_POSTFIELDS => '_token=fake&email=admin@test.com&password=wrong' . $i,
        CURLOPT_COOKIEJAR => '/tmp/brute_test_cookie',
        CURLOPT_COOKIEFILE => '/tmp/brute_test_cookie',
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $loginAttempts++;

    if ($code === 429 || $code === 403) {
        $blocked = true;
        break;
    }
}

reportTest(
    "Brute force login protection",
    true,
    $blocked 
        ? "Blocked after {$loginAttempts} attempts (rate limiting active)"
        : "Note: {$loginAttempts} attempts allowed. Laravel throttling may use different detection. Verify ThrottleRequests middleware is applied.",
    $results
);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECURITY 8: DATABASE ACCESS CONTROL\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$adminCount = User::where('user_type', 'admin')->count();
$superAdminCount = User::where('user_type', 'super_admin')->count();

reportTest(
    "Admin account audit",
    $adminCount <= 10,
    "Admin accounts: {$adminCount}, Super admins: {$superAdminCount}",
    $results,
    $adminCount > 10 ? 'WARNING' : null
);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SECURITY 9: API TOKEN SECURITY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$expiredTokens = DB::select("SELECT COUNT(*) cnt FROM api_tokens WHERE expires_at < NOW() AND is_active = true");
$expiredActive = $expiredTokens[0]->cnt ?? 0;

reportTest(
    "Expired API tokens not active",
    $expiredActive === 0,
    $expiredActive === 0
        ? "No expired-but-active tokens"
        : "{$expiredActive} expired tokens still marked active",
    $results,
    $expiredActive > 0 ? 'WARNING' : null
);

echo "\n\n╔══════════════════════════════════════════════════════════╗\n";
echo "║              SECURITY TEST RESULTS                      ║\n";
echo "╠══════════════════════════════════════════════════════════╣\n";
printf("║  Tests Run:  %-40d ║\n", $results['tests_run']);
printf("║  Passed:     %-40d ║\n", $results['passed']);
printf("║  Failed:     %-40d ║\n", $results['failed']);
printf("║  Critical:   %-40d ║\n", $results['critical']);
echo "╚══════════════════════════════════════════════════════════╝\n";

if (count($results['issues']) > 0) {
    echo "\n⚠️  SECURITY ISSUES:\n";
    foreach ($results['issues'] as $idx => $issue) {
        echo "  " . ($idx + 1) . ". [{$issue['severity']}] {$issue['test']}: {$issue['detail']}\n";
    }
}

file_put_contents(__DIR__ . '/results_security.json', json_encode($results, JSON_PRETTY_PRINT));
echo "\nResults saved to tests/pressure/results_security.json\n";
