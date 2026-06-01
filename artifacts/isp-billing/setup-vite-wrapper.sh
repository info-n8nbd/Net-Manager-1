#!/bin/bash
# Creates a vite wrapper that redirects npm run dev to the Laravel start script
# This is needed because the Replit workflow runs "npm run dev" which calls "vite"
mkdir -p /home/runner/workspace/node_modules/.bin
cat > /home/runner/workspace/node_modules/.bin/vite << 'EOF'
#!/bin/bash
exec bash /home/runner/workspace/start.sh
EOF
chmod +x /home/runner/workspace/node_modules/.bin/vite
