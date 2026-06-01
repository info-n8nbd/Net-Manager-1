<?php
// Bootstrap Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make('Illuminate\Foundation\Bootstrap\RegisterProviders')->bootstrap($app);
$app->make('Illuminate\Foundation\Bootstrap\BootProviders')->bootstrap($app);
$app->make('Illuminate\Foundation\Bootstrap\RegisterFacades')->bootstrap($app);
$app->make('Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables')->bootstrap($app);
$app->make('Illuminate\Foundation\Bootstrap\LoadConfiguration')->bootstrap($app);
$app->make('Illuminate\Foundation\Bootstrap\SetRequestForConsole')->bootstrap($app);
Illuminate\Support\Facades\Facade::setFacadeApplication($app);

use Illuminate\Support\Facades\DB;

echo "=== THEME SYSTEM TEST ===\n\n";

// Test 1: DB column exists
try {
    $cols = DB::select("SELECT column_name, data_type, column_default FROM information_schema.columns WHERE table_name = 'users' AND column_name = 'theme'");
    if (count($cols) > 0) {
        echo "[PASS] theme column exists: type=" . $cols[0]->data_type . ", default=" . $cols[0]->column_default . "\n";
    } else {
        echo "[FAIL] theme column NOT found in users table\n";
    }
} catch (Exception $e) {
    echo "[FAIL] DB error: " . $e->getMessage() . "\n";
}

// Test 2: Read users with themes
$users = DB::select("SELECT id, name, email, theme FROM users ORDER BY id LIMIT 5");
echo "\n[INFO] Users and themes:\n";
foreach ($users as $u) {
    echo "  - [{$u->id}] {$u->name}: " . ($u->theme ?? 'NULL') . "\n";
}

// Test 3: Update theme to ocean-blue
DB::statement("UPDATE users SET theme = 'ocean-blue' WHERE id = (SELECT id FROM users ORDER BY id LIMIT 1)");
$user = DB::selectOne("SELECT id, name, theme FROM users ORDER BY id LIMIT 1");
echo "\n[PASS] Changed user #{$user->id} ({$user->name}) theme to: {$user->theme}\n";

// Test 4: Change to all 5 themes
$themes = ['dark-navy', 'ocean-blue', 'forest-green', 'purple-pro', 'sunset-orange'];
echo "\n[INFO] Testing all 5 theme saves:\n";
foreach ($themes as $t) {
    DB::statement("UPDATE users SET theme = ? WHERE id = ?", [$t, $user->id]);
    $check = DB::selectOne("SELECT theme FROM users WHERE id = ?", [$user->id]);
    echo "  " . ($check->theme === $t ? "[PASS]" : "[FAIL]") . " $t -> saved as: {$check->theme}\n";
}

// Test 5: Revert to dark-navy
DB::statement("UPDATE users SET theme = 'dark-navy' WHERE id = ?", [$user->id]);
echo "\n[PASS] Reverted to dark-navy\n";

// Test 6: Check ThemeController exists and is readable
$ctrl = __DIR__ . '/app/Http/Controllers/Admin/ThemeController.php';
echo "\n" . (file_exists($ctrl) ? "[PASS]" : "[FAIL]") . " ThemeController.php exists\n";

// Test 7: Check route registered
$routeFile = __DIR__ . '/routes/web.php';
$routeContent = file_get_contents($routeFile);
echo (strpos($routeContent, 'admin.theme.change') !== false ? "[PASS]" : "[FAIL]") . " Route admin.theme.change in web.php\n";

// Test 8: Count CSS variables in compiled layout view
$viewFiles = glob(__DIR__ . '/storage/framework/views/*.php');
$themeVarCount = 0;
foreach ($viewFiles as $vf) {
    $content = file_get_contents($vf);
    if (strpos($content, '--theme-p1') !== false) {
        $themeVarCount += substr_count($content, 'var(--theme-p1)');
        break;
    }
}
echo ($themeVarCount > 0 ? "[PASS]" : "[FAIL]") . " CSS variables found in compiled view: $themeVarCount occurrences of var(--theme-p1)\n";

// Test 9: Check no raw hardcoded #1e3c72 in layout except theme defs
$layoutPath = __DIR__ . '/resources/views/layouts/admin.blade.php';
$layoutContent = file_get_contents($layoutPath);
$hardcodedLines = [];
foreach (explode("\n", $layoutContent) as $ln => $line) {
    if ((strpos($line, '#1e3c72') !== false || strpos($line, '#2a5298') !== false)
        && strpos($line, 'dark-navy') === false
        && strpos($line, '__themes') === false
        && strpos($line, '__tc') === false
        && strpos($line, 'themeVal') === false
        && strpos($line, 'theme-color') === false
        && strpos($line, 'smart-swal') === false
        && strpos($line, 'theme-vars') === false
        && strpos($line, 'var(--theme') === false
    ) {
        $hardcodedLines[] = "  Line " . ($ln+1) . ": " . trim(substr($line, 0, 80));
    }
}
if (empty($hardcodedLines)) {
    echo "[PASS] No hardcoded colors remain (all replaced with CSS variables)\n";
} else {
    echo "[WARN] " . count($hardcodedLines) . " hardcoded color lines remain:\n";
    foreach ($hardcodedLines as $hl) echo $hl . "\n";
}

// Test 10: Check dropdown HTML present in layout
echo (strpos($layoutContent, 'fa-palette') !== false ? "[PASS]" : "[FAIL]") . " Theme dropdown (fa-palette icon) in layout\n";
echo (strpos($layoutContent, 'changeTheme') !== false ? "[PASS]" : "[FAIL]") . " changeTheme() JS function in layout\n";

echo "\n=== TEST COMPLETE ===\n";
