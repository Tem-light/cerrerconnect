#!/bin/bash

echo "================================"
echo "Career Connect - Development Setup"
echo "================================"
echo ""

echo "Step 1: Starting Backend Server (PHP, Port 5000)..."
php -S localhost:5000 -t server-php/public server-php/router.php &
BACKEND_PID=$!

sleep 1

echo ""
echo "Step 2: Starting Frontend Server (Vite, Port 5173)..."
npm run dev &
FRONTEND_PID=$!

echo ""
echo "================================"
echo "✓ Setup Complete!"
echo "================================"
echo ""
echo "Backend: http://localhost:5000"
echo "Frontend: http://localhost:5173"
echo ""
echo "Press Ctrl+C to stop all servers"
echo ""

wait
