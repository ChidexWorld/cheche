FROM php:8.2-apache

# Install PHP extensions required for MySQL support
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . .

# Create necessary directories for file-based storage and uploads
RUN mkdir -p uploads/videos uploads/subtitles uploads/merged_videos data

# Set proper permissions for web server user
RUN chown -R www-data:www-data uploads data

# Copy startup script
COPY start.sh .

# Make startup script executable
RUN chmod +x start.sh

# Expose the port (Render will set PORT environment variable)
EXPOSE $PORT

# Start the application
CMD ["./start.sh"]
