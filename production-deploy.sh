#!/bin/bash
set -e

echo "=== 1. Ensuring Firewall Rules for HTTP/HTTPS ==="
sudo iptables -I INPUT 1 -p tcp --dport 80 -j ACCEPT 2>/dev/null || true
sudo iptables -I INPUT 1 -p tcp --dport 443 -j ACCEPT 2>/dev/null || true
sudo netfilter-persistent save 2>/dev/null || true

echo "=== 2. Setting up MySQL Database and User ==="
sudo mysql -e "CREATE DATABASE IF NOT EXISTS Wonder;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'Wonder_user'@'localhost' IDENTIFIED BY 'Akinbomi1#';"
sudo mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'Wonder_user'@'localhost' WITH GRANT OPTION;"
sudo mysql -e "FLUSH PRIVILEGES;"

echo "=== 3. Setting up Application Directory ==="
sudo mkdir -p /var/www/Wonder
sudo chown -R ubuntu:ubuntu /var/www/Wonder

if [ ! -d "/var/www/Wonder/.git" ]; then
    echo "Cloning repository..."
    git clone git@github.com:Joskin1/wondersschools.git /var/www/Wonder
fi

cd /var/www/Wonder
git pull origin main || git pull origin master || true

echo "=== 4. Installing Composer Dependencies ==="
composer install --no-dev --optimize-autoloader --ignore-platform-reqs

echo "=== 5. Configuring .env ==="
cat << 'EOF' > .env
APP_NAME="Livingsspring School"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://livingsspring.duckdns.org

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=Wonder
DB_USERNAME=Wonder_user
DB_PASSWORD=Akinbomi1#

LANDLORD_DB_CONNECTION=mysql
LANDLORD_DB_HOST=127.0.0.1
LANDLORD_DB_PORT=3306
LANDLORD_DB_DATABASE=Wonder
LANDLORD_DB_USERNAME=Wonder_user
LANDLORD_DB_PASSWORD=Akinbomi1#

TENANT_DB_HOST=127.0.0.1
TENANT_DB_PORT=3306
TENANT_ADMIN_USERNAME=Wonder_user
TENANT_ADMIN_PASSWORD=Akinbomi1#
TENANT_DB_PREFIX=tenant_

SESSION_DRIVER=database
SESSION_LIFETIME=120

CACHE_STORE=database
QUEUE_CONNECTION=database

CENTRAL_DOMAINS="wonderlandlord.duckdns.org,livingsspring.duckdns.org"
SINGLE_TENANT_ID=livingsspring
TENANT_NAME="Livingsspring School"
DEV_TENANT_DOMAIN="livingsspring.duckdns.org"
EOF

php artisan key:generate --force
php artisan config:clear

echo "=== 6. Running Fresh Migrations and Seeding ==="
php artisan migrate:fresh --seed --force

echo "=== 7. Setting Permissions & Storage Link ==="
php artisan storage:link || true
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

echo "=== 8. Configuring Nginx ==="
cat << 'EOF' | sudo tee /etc/nginx/sites-available/wonder > /dev/null
server {
    listen 80;
    listen [::]:80;
    server_name wonderlandlord.duckdns.org livingsspring.duckdns.org;
    root /var/www/Wonder/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

sudo ln -sf /etc/nginx/sites-available/wonder /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

echo "=== 9. Requesting SSL Certificate ==="
sudo certbot --nginx -d wonderlandlord.duckdns.org -d livingsspring.duckdns.org --redirect --non-interactive --agree-tos -m admin@livingsspring.duckdns.org || echo "Certbot check complete."

echo "=== DEPLOYMENT COMPLETE! ==="
echo "Tenant URL: https://livingsspring.duckdns.org"
echo "Admin Portal: https://livingsspring.duckdns.org/admin"
