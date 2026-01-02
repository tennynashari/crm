#!/bin/bash

echo "Starting FlowCRM..."
echo ""

echo "Starting Backend Server..."
cd backend
php artisan serve &
BACKEND_PID=$!

echo "Starting Frontend Server..."
cd ../frontend
npm run dev &
FRONTEND_PID=$!

echo ""
echo "========================================"
echo "FlowCRM is running!"
echo "========================================"
echo "Backend:  http://localhost:8000"
echo "Frontend: http://localhost:5173"
echo ""
echo "Login: admin@flowcrm.test / password"
echo "========================================"
echo ""
echo "Press Ctrl+C to stop all servers..."

# Wait for Ctrl+C
trap "kill $BACKEND_PID $FRONTEND_PID; exit" INT
wait
