#!/bin/bash

# Cheche Learning Platform - Start Server Script
# This script starts the PHP development server with increased upload limits

echo "Starting Cheche Learning Platform server..."
echo "Upload limits: 500MB max file size"
echo "Server URL: http://localhost:8000"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

php -d upload_max_filesize=500M \
    -d post_max_size=550M \
    -d max_execution_time=1800 \
    -d max_input_time=1800 \
    -d memory_limit=512M \
    -S localhost:8000