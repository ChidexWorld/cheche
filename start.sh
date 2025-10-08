#!/bin/bash

# Configure Apache to listen on the PORT environment variable set by Render
# Replace default port 80 with the PORT value
sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf
sed -i "s/80/$PORT/g" /etc/apache2/ports.conf

# Start Apache in foreground mode
apache2-foreground
