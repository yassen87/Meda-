#!/bin/bash

echo "=========================================="
echo "    Meda E-commerce Website Starter"
echo "=========================================="
echo

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "ERROR: PHP not found!"
    echo "Please install PHP or use XAMPP"
    exit 1
fi

# Check if we're in the right directory
if [ ! -f "index.php" ]; then
    echo "ERROR: Please run this script from the Meda directory"
    echo "Current directory: $(pwd)"
    exit 1
fi

echo "Step 1: Starting PHP development server..."
echo "Server will run on http://localhost:8000"
echo

# Start PHP server in background
php -S localhost:8000 &
SERVER_PID=$!

# Wait for server to start
sleep 2

echo "Step 2: Running database migration..."
if [ -f "database/migrate.php" ]; then
    php database/migrate.php
    echo "Database migration completed!"
else
    echo "Migration file not found, skipping..."
fi

echo
echo "Step 3: Opening website in browser..."
echo "Website URL: http://localhost:8000"
echo "Admin Panel: http://localhost:8000/admin/"
echo

# Try to open browser (works on most systems)
if command -v xdg-open &> /dev/null; then
    xdg-open http://localhost:8000
elif command -v open &> /dev/null; then
    open http://localhost:8000
elif command -v start &> /dev/null; then
    start http://localhost:8000
else
    echo "Please open your browser and go to: http://localhost:8000"
fi

echo
echo "=========================================="
echo "Website is now running!"
echo "Press Ctrl+C to stop the server"
echo "=========================================="
echo

# Wait for the server process
wait $SERVER_PID
