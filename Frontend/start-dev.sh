#!/bin/bash

echo "================================"
echo "Career Connect - Development Setup"
echo "================================"
echo ""

echo "Step 1: Start Apache + MySQL in XAMPP"
echo "Backend API should be available at: http://localhost/careerconnect/Backend/api"

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
