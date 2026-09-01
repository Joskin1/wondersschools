#!/bin/bash
set -euo pipefail

APP_DIR=/var/www/Wonder

echo "=== 1. Ensuring Firewall Rules for HTTP/HTTPS ==="
sudo iptables -I INPUT 1 -p tcp --dport 80 -j ACCEPT 2>/dev/null || true
sudo iptables -I INPUT 1 -p tcp --dport 443 -j ACCEPT 2>/dev/null || true
sudo netfilter-persistent save 2>/dev/null || true

echo "=== 2. Setting up Application Directory ==="
sudo mkdir -p "$APP_DIR"
sudo chown -R ubuntu:ubuntu "$APP_DIR"

if [ ! -d "$APP_DIR/.git" ]; then
    echo "Cloning repository..."
    git clone git@github.com:Joskin1/wondersschools.git "$APP_DIR"
fi

cd "$APP_DIR"
git pull --ff-only origin main || git pull --ff-only origin master

if [ ! -f .env ]; then
    echo "Production .env is missing at $APP_DIR/.env. Create it on the server before deploying."
    exit 1
fi

set -a
source .env
set +a

echo "=== 3. Setting up MySQL Database and User ==="
sudo mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE:-Wonder}\`;"
if [ -n "${DB_PASSWORD:-}" ]; then
    sudo mysql -e "CREATE USER IF NOT EXISTS '${DB_USERNAME:-Wonder_user}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
    sudo mysql -e "GRANT ALL PRIVILEGES ON *.* TO '${DB_USERNAME:-Wonder_user}'@'localhost' WITH GRANT OPTION;"
    sudo mysql -e "FLUSH PRIVILEGES;"
else
    echo "DB_PASSWORD is not set. Skipping database user creation."
fi

echo "=== 4. Configuring system mail sender ==="
if [ -n "${MAIL_PASSWORD:-}" ]; then
sudo tee /etc/msmtprc > /dev/null << MSMTPEOF
defaults
auth on
tls on
tls_trust_file /etc/ssl/certs/ca-certificates.crt

account gmail
host ${MAIL_HOST:-smtp.gmail.com}
port ${MAIL_PORT:-587}
from ${MAIL_FROM_ADDRESS:-demaevolutionary@gmail.com}
user ${MAIL_USERNAME:-demaevolutionary@gmail.com}
password ${MAIL_PASSWORD}

account default : gmail
MSMTPEOF
sudo chown root:www-data /etc/msmtprc
sudo chmod 640 /etc/msmtprc
else
    echo "MAIL_PASSWORD is not set. Skipping msmtp configuration."
fi

echo "=== 5. Installing Composer Dependencies ==="
CACHE_STORE=file composer install --no-dev --optimize-autoloader --ignore-platform-reqs

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi
php artisan config:clear

echo "=== 6. Running Migrations ==="
php artisan migrate --force

echo "=== 7. Setting Permissions & Storage Link ==="
php artisan storage:link || true
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

echo "=== 8. Configuring Nginx ==="
cat << 'NGINXEOF' | sudo tee /etc/nginx/sites-available/wonder > /dev/null
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
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINXEOF

sudo ln -sf /etc/nginx/sites-available/wonder /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

echo "=== 9. Requesting SSL Certificate ==="
sudo certbot --nginx -d wonderlandlord.duckdns.org -d livingsspring.duckdns.org --redirect --non-interactive --agree-tos -m admin@livingsspring.duckdns.org || echo "Certbot check complete."

echo "=== DEPLOYMENT COMPLETE! ==="
echo "Tenant URL: https://livingsspring.duckdns.org"
echo "Admin Portal: https://livingsspring.duckdns.org/admin"
