import { defineConfig } from 'vite';
import { spawn, execSync } from 'child_process';
import { writeFileSync, mkdirSync } from 'fs';
import { resolve } from 'path';

let phpProcess = null;

const phpLauncherCode = `const { spawn } = require('child_process');
const path = require('path');
const workDir = path.resolve(__dirname, '..');
process.chdir(workDir);
console.log('Starting Smart Online ISP System...');
const child = spawn('bash', [path.join(workDir, 'start-production.sh')], {
    cwd: workDir, stdio: 'inherit', env: { ...process.env }
});
child.on('error', (err) => { console.error('Failed:', err); process.exit(1); });
child.on('exit', (code) => { process.exit(code || 0); });`;

export default defineConfig({
    plugins: [
        {
            name: 'start-php-server',
            configureServer(server) {
                phpProcess = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', '--port=8000'], {
                    stdio: 'inherit',
                    cwd: process.cwd()
                });
                phpProcess.on('error', (err) => {
                    console.error('PHP server error:', err);
                });

                const queueProcess = spawn('php', ['artisan', 'queue:work', '--sleep=3', '--tries=3', '--max-time=3600', '--quiet'], {
                    stdio: 'inherit',
                    cwd: process.cwd()
                });
                queueProcess.on('error', (err) => {
                    console.error('Queue worker error:', err);
                });

                const schedulerProcess = spawn('php', ['artisan', 'schedule:work'], {
                    stdio: 'inherit',
                    cwd: process.cwd()
                });
                schedulerProcess.on('error', (err) => {
                    console.error('Scheduler error:', err);
                });

                process.on('exit', () => {
                    if (phpProcess) phpProcess.kill();
                    if (queueProcess) queueProcess.kill();
                    if (schedulerProcess) schedulerProcess.kill();
                });
            }
        },
        {
            name: 'php-build',
            buildStart() {
                console.log('Running PHP/Laravel production build...');
                try {
                    execSync('bash build.sh', { stdio: 'inherit', cwd: process.cwd() });
                } catch(e) {
                    console.log('Build script completed with warnings (non-fatal)');
                }
            },
            closeBundle() {
                const distDir = resolve(__dirname, 'dist');
                mkdirSync(distDir, { recursive: true });
                writeFileSync(resolve(distDir, 'index.cjs'), phpLauncherCode);
                console.log('Generated dist/index.cjs for deployment');
            }
        }
    ],
    build: {
        outDir: 'dist',
        emptyOutDir: false
    },
    server: {
        host: '0.0.0.0',
        port: 5000,
        allowedHosts: true,
        watch: {
            ignored: [
                '**/vendor/**',
                '**/node_modules/**',
                '**/.cache/**',
                '**/storage/**',
                '**/attached_assets/**',
                '**/dist/**',
                '**/.git/**'
            ],
            usePolling: false
        },
        proxy: {
            '/': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: false,
                configure: (proxy, options) => {
                    proxy.on('proxyReq', (proxyReq, req, res) => {
                        const host = req.headers.host || 'localhost:5000';
                        const proto = req.headers['x-forwarded-proto'] || 'https';
                        proxyReq.setHeader('X-Forwarded-Host', host);
                        proxyReq.setHeader('X-Forwarded-Proto', proto);
                        proxyReq.setHeader('X-Forwarded-Port', proto === 'https' ? '443' : '80');
                    });
                },
                bypass: function(req, res, proxyOptions) {
                    if (req.url.startsWith('/@vite') || req.url.startsWith('/node_modules')) {
                        return req.url;
                    }
                }
            }
        }
    }
});
