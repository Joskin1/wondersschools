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

echo "=== 3. Setting up Database ==="
if [ "${DB_CONNECTION:-mysql}" = "mysql" ]; then
    sudo mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE:-Wonder}\`;"
    if [ -n "${DB_PASSWORD:-}" ]; then
        sudo mysql -e "CREATE USER IF NOT EXISTS '${DB_USERNAME:-Wonder_user}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
        sudo mysql -e "GRANT ALL PRIVILEGES ON *.* TO '${DB_USERNAME:-Wonder_user}'@'localhost' WITH GRANT OPTION;"
        sudo mysql -e "FLUSH PRIVILEGES;"
    else
        echo "DB_PASSWORD is not set. Skipping database user creation."
    fi
else
    echo "DB_CONNECTION=${DB_CONNECTION} — skipping MySQL setup (database managed externally)."
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

echo "=== 5b. Building Frontend Assets ==="
npm ci --production=false
npm run build

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi
php artisan config:clear

echo "=== 6. Running Migrations ==="
php artisan migrate --force

echo "=== 6b. Provisioning Tenants ==="
php artisan tinker --execute="
use App\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;

// Existing: Livingsspring School
\$t1 = Tenant::firstOrCreate(['id' => 'livingsspring'], ['name' => 'Livingsspring School']);
Domain::firstOrCreate(['domain' => 'livingsspring.duckdns.org'], ['tenant_id' => \$t1->id]);
echo \"Tenant livingsspring: {\$t1->id} (status: {\$t1->status})\n\";

// New: BETA School
\$t2 = Tenant::firstOrCreate(['id' => 'beta'], ['name' => 'BETA School']);
Domain::firstOrCreate(['domain' => 'betaschool.duckdns.org'], ['tenant_id' => \$t2->id]);
echo \"Tenant beta: {\$t2->id} (status: {\$t2->status})\n\";
"

echo "=== 7. Setting Permissions & Storage Link ==="
php artisan storage:link || true
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

echo "=== 8. Configuring Nginx ==="
cat << 'NGINXEOF' | sudo tee /etc/nginx/sites-available/wonder > /dev/null
server {
    listen 80;
    listen [::]:80;
    server_name livingsspring.duckdns.org betaschool.duckdns.org;
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
sudo certbot --nginx --expand -d livingsspring.duckdns.org -d betaschool.duckdns.org --redirect --non-interactive --agree-tos -m admin@livingsspring.duckdns.org || echo "Certbot check complete."

echo "=== DEPLOYMENT COMPLETE! ==="
echo "Tenant URL: https://livingsspring.duckdns.org"
echo "Admin Portal: https://livingsspring.duckdns.org/admin"
echo "BETA School: https://betaschool.duckdns.org"
echo "BETA Admin:  https://betaschool.duckdns.org/admin"
