<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;

class RelationshipHealthCheck extends Command
{
    protected $signature = 'health:relationships {--fix-report : Show detailed fix suggestions}';
    protected $description = 'Scan controllers for missing model relationships and report issues';

    private array $issues = [];
    private array $warnings = [];
    private int $checked = 0;

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║   Relationship Health Check — Smart ISP          ║');
        $this->info('╚══════════════════════════════════════════════════╝');
        $this->info('');

        $this->info('Building model relationship map...');
        $modelMap = $this->buildModelMap();
        $this->info('Found ' . count($modelMap) . ' models.');
        $this->info('');

        $this->info('Scanning controllers, services and listeners...');
        foreach ([app_path('Http/Controllers'), app_path('Services'), app_path('Listeners')] as $dir) {
            if (!File::exists($dir)) {
                continue;
            }
            foreach (File::allFiles($dir) as $file) {
                if ($file->getExtension() === 'php') {
                    $this->scanFile($file->getPathname(), $modelMap);
                }
            }
        }

        $this->info('Scanned ' . $this->checked . ' files.');
        $this->info('');

        if (empty($this->issues)) {
            $this->info('✅ All relationship calls look healthy! No issues found.');
        } else {
            $this->error('❌ Found ' . count($this->issues) . ' issue(s):');
            $this->info('');

            $rows = array_map(fn($i) => [
                $i['file'], $i['line'], $i['call'], $i['relationship'], $i['issue'],
            ], $this->issues);

            $this->table(['File', 'Line', 'Call', 'Relationship', 'Issue'], $rows);

            if ($this->option('fix-report')) {
                $this->info('');
                $this->info('Fix Suggestions:');
                foreach ($this->issues as $issue) {
                    $this->info('');
                    $this->comment('  [' . $issue['file'] . ' line ' . $issue['line'] . ']');
                    if (!empty($issue['similar'])) {
                        $this->info('  → Did you mean: ' . implode(', ', $issue['similar']) . '?');
                    }
                    $this->info('  → Add: public function ' . $issue['relationship'] . '() { return $this->hasMany(...); }');
                }
            }
        }

        if (!empty($this->warnings)) {
            $this->info('');
            $this->warn('⚠️  ' . count($this->warnings) . ' warning(s) — model could not be resolved:');
            foreach (array_slice($this->warnings, 0, 8) as $w) {
                $this->warn('   ' . $w);
            }
            if (count($this->warnings) > 8) {
                $this->warn('   ... and ' . (count($this->warnings) - 8) . ' more.');
            }
        }

        $logPath = storage_path('logs/relationship-health-' . date('Y-m-d') . '.log');
        $logLines = [date('Y-m-d H:i:s') . ' | Files: ' . $this->checked . ' | Issues: ' . count($this->issues) . ' | Warnings: ' . count($this->warnings)];
        foreach ($this->issues as $i) {
            $logLines[] = '  ISSUE [' . $i['file'] . ':' . $i['line'] . '] ' . $i['call'] . "('" . $i['relationship'] . "') — " . $i['issue'];
        }
        File::put($logPath, implode("\n", $logLines) . "\n");

        $this->info('');
        $this->info('Log saved to: ' . $logPath);
        $this->info('Tip: Run with --fix-report for detailed fix suggestions.');
        $this->info('');

        return count($this->issues) > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function buildModelMap(): array
    {
        $map = [];
        foreach (File::allFiles(app_path('Models')) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $baseName  = $file->getBasename('.php');
            $className = 'App\\Models\\' . $baseName;
            try {
                if (!class_exists($className)) {
                    continue;
                }
                $ref = new ReflectionClass($className);
                if ($ref->isAbstract() || $ref->isInterface()) {
                    continue;
                }
                $methods = array_map(
                    fn(ReflectionMethod $m) => $m->getName(),
                    $ref->getMethods(ReflectionMethod::IS_PUBLIC)
                );
                $map[strtolower($baseName)] = ['class' => $className, 'methods' => $methods];
            } catch (\Throwable $e) {
                // skip unloadable models
            }
        }
        return $map;
    }

    private function scanFile(string $filePath, array $modelMap): void
    {
        $this->checked++;
        $shortFile = str_replace(base_path() . '/', '', $filePath);
        $lines     = explode("\n", File::get($filePath));

        foreach ($lines as $idx => $line) {
            $lineNo = $idx + 1;

            // Match: whereHas / whereDoesntHave / orWhereHas
            if (preg_match('/\b(whereHas|whereDoesntHave|orWhereHas)\s*\(\s*[\'"]([^\'"\.]+)[\'"]/', $line, $m)) {
                $call  = $m[1];
                $rel   = $m[2];
                $model = $this->resolveModelFromContext($lines, $idx, $modelMap);
                if ($model) {
                    $this->checkRelationship($shortFile, $lineNo, $call, $rel, $model);
                } else {
                    $this->warnings[] = $shortFile . ':' . $lineNo . ' — Could not resolve model for ' . $call . "('" . $rel . "')";
                }
            }
        }
    }

    private function resolveModelFromContext(array $lines, int $currentIdx, array $modelMap): ?array
    {
        $start = max(0, $currentIdx - 20);
        for ($i = $currentIdx; $i >= $start; $i--) {
            $line = $lines[$i];

            // Match \App\Models\Foo:: or App\Models\Foo::
            if (preg_match('/\\\\?App\\\\Models\\\\(\w+)::/', $line, $m)) {
                $key = strtolower($m[1]);
                if (isset($modelMap[$key])) {
                    return $modelMap[$key];
                }
            }

            // Match Foo:: where Foo is a known model (capital letter start)
            if (preg_match('/\b([A-Z][a-zA-Z]+)::/', $line, $m)) {
                $key = strtolower($m[1]);
                if (isset($modelMap[$key])) {
                    return $modelMap[$key];
                }
            }
        }
        return null;
    }

    private function checkRelationship(string $file, int $line, string $call, string $rel, array $modelInfo): void
    {
        if (in_array($rel, $modelInfo['methods'])) {
            return;
        }

        $similar = [];
        foreach ($modelInfo['methods'] as $method) {
            if (strlen($method) > 3 && levenshtein(strtolower($rel), strtolower($method)) <= 3) {
                $similar[] = $method;
            }
        }

        $className = class_basename($modelInfo['class']);
        $similarStr = !empty($similar) ? ' (similar: ' . implode(', ', $similar) . ')' : '';

        $this->issues[] = [
            'file'         => $file,
            'line'         => $line,
            'call'         => $call,
            'relationship' => $rel,
            'issue'        => "'" . $rel . "()' not found on {$className}",
            'similar'      => $similar,
        ];

        $this->warn('  ❌ ' . $file . ':' . $line . " — '" . $rel . "()' missing on {$className}{$similarStr}");
    }
}
