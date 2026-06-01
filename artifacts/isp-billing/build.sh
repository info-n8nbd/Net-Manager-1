#!/bin/bash
PROJECT_DIR="${PROJECT_DIR:-$(cd "$(dirname "$0")" && pwd)}"
cd "$PROJECT_DIR"

echo "============================================"
echo "  Smart Online ISP - Production Build"
echo "============================================"

echo "[1/4] Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction 2>&1 || {
    echo "  -> Warning: composer install had issues, trying with dev deps..."
    composer install --optimize-autoloader --no-interaction 2>&1 || true
}

echo "[2/4] Setting up storage..."
mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views storage/app/public storage/app/backups storage/app/imports storage/app/exports bootstrap/cache 2>/dev/null
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo "[3/4] Creating deployment entry point..."
mkdir -p dist
cat > dist/index.cjs << 'NODEOF'
const { spawn } = require('child_process');
const path = require('path');
const workDir = path.resolve(__dirname, '..');
process.chdir(workDir);
console.log('Starting Smart Online ISP System...');
const child = spawn('bash', [path.join(workDir, 'start-production.sh')], {
    cwd: workDir, stdio: 'inherit', env: { ...process.env }
});
child.on('error', (err) => { console.error('Failed:', err); process.exit(1); });
child.on('exit', (code) => { process.exit(code || 0); });
NODEOF

echo "[4/4] Setting permissions..."
chmod +x start-production.sh 2>/dev/null || true

echo ""
echo "============================================"
echo "  Build Complete!"
echo "  Next: Copy .env.example to .env and configure"
echo "  Then: bash start-production.sh"
echo "============================================"
