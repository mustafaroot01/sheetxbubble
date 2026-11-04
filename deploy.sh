#!/bin/bash
set -e

# ============================================================
#  SheetxBubble Auto-Deploy Script for Ubuntu/Debian VPS
# ============================================================
#  Usage: bash deploy.sh
#  Run as root on a fresh Ubuntu 22.04/24.04 server
# ============================================================

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

print_step() { echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"; echo -e "${GREEN}▶ $1${NC}"; echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"; }
print_warn() { echo -e "${YELLOW}⚠ $1${NC}"; }
print_ok() { echo -e "${GREEN}✔ $1${NC}"; }
print_err() { echo -e "${RED}✘ $1${NC}"; }

# Check root
if [ "$EUID" -ne 0 ]; then
    print_err "Please run as root: sudo bash deploy.sh"
    exit 1
fi

# ─── Configuration ───────────────────────────────────────────
REPO_URL="https://github.com/mustafaroot01/sheetxbubble.git"
APP_DIR="/var/www/sheetxbubble"
DB_NAME="sheetxbubble"
DB_USER="sheetuser"
DB_PASS=$(openssl rand -base64 16 | tr -d '/+=' | head -c 20)
DOMAIN=""

echo -e "${CYAN}"
echo "╔══════════════════════════════════════════╗"
echo "║   SheetxBubble Server Setup Script       ║"
echo "║   Bubble Sheet Auto-Correction System    ║"
echo "╚══════════════════════════════════════════╝"
echo -e "${NC}"

read -p "Enter your domain or server IP (e.g. example.com or 192.168.1.1): " DOMAIN
if [ -z "$DOMAIN" ]; then
    print_err "Domain/IP is required!"
    exit 1
fi

echo ""
echo -e "Domain/IP: ${GREEN}$DOMAIN${NC}"
echo -e "Database:  ${GREEN}$DB_NAME${NC}"
echo -e "DB User:   ${GREEN}$DB_USER${NC}"
echo -e "DB Pass:   ${GREEN}$DB_PASS${NC}"
echo ""
read -p "Continue? (y/n): " CONFIRM
if [ "$CONFIRM" != "y" ]; then exit 0; fi

# ─── 1. System Update ───────────────────────────────────────
print_step "1/9 - Updating system packages..."
apt update && apt upgrade -y
print_ok "System updated"

# ─── 2. Install PHP 8.4 ─────────────────────────────────────
print_step "2/9 - Installing PHP 8.4..."
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.4 php8.4-fpm php8.4-mysql php8.4-mbstring php8.4-xml \
    php8.4-curl php8.4-zip php8.4-gd php8.4-bcmath php8.4-intl php8.4-readline \
    php8.4-tokenizer php8.4-fileinfo php8.4-dom
print_ok "PHP 8.4 installed"

# ─── 3. Install Composer ────────────────────────────────────
print_step "3/9 - Installing Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi
print_ok "Composer installed"

# ─── 4. Install Nginx + MySQL + Git + Node.js ───────────────
print_step "4/9 - Installing Nginx, MySQL, Node.js, Git..."
apt install -y nginx mysql-server git unzip curl

# Node.js 20
if ! command -v node &> /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt install -y nodejs
fi

# Python + dependencies for OpenCV
apt install -y python3 python3-pip python3-venv libgl1-mesa-glx libglib2.0-0 libzbar0
print_ok "All packages installed"

# ─── 5. Setup MySQL Database ────────────────────────────────
print_step "5/9 - Setting up MySQL database..."
mysql -u root -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -u root -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"
print_ok "Database '${DB_NAME}' created"

# ─── 6. Clone & Setup Laravel ───────────────────────────────
print_step "6/9 - Cloning project & setting up Laravel..."

if [ -d "$APP_DIR" ]; then
    print_warn "Directory exists, pulling latest..."
    cd "$APP_DIR" && git pull origin main
else
    git clone "$REPO_URL" "$APP_DIR"
fi

cd "$APP_DIR"

# Composer install
composer install --optimize-autoloader --no-dev --no-interaction

# Setup .env
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi

# Configure .env
sed -i "s|APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|" .env
sed -i "s|APP_URL=.*|APP_URL=http://${DOMAIN}|" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env

# Check if PYTHON_OMR_URL exists in .env, add if not
if ! grep -q "PYTHON_OMR_URL" .env; then
    echo "" >> .env
    echo "PYTHON_OMR_URL=http://127.0.0.1:5001" >> .env
fi

# Run migrations
php artisan migrate --force
php artisan db:seed --force 2>/dev/null || true
php artisan storage:link 2>/dev/null || true

# Build frontend assets
npm install
npm run build

# Optimize
php artisan optimize
php artisan view:cache
php artisan route:cache
php artisan config:cache

# Permissions
chown -R www-data:www-data "$APP_DIR"
chmod -R 775 storage bootstrap/cache
print_ok "Laravel configured"

# ─── 7. Setup Python Flask OMR Service ──────────────────────
print_step "7/9 - Setting up Python Flask OMR service..."
cd "$APP_DIR/python"

python3 -m venv venv
source venv/bin/activate
pip install --upgrade pip
pip install -r requirements.txt
deactivate

chown -R www-data:www-data "$APP_DIR/python"

# Create systemd service
cat > /etc/systemd/system/sheetxbubble-flask.service << 'FLASK_SERVICE'
[Unit]
Description=SheetxBubble Flask OMR Service
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/sheetxbubble/python
ExecStart=/var/www/sheetxbubble/python/venv/bin/python app.py
Restart=always
RestartSec=5
Environment=FLASK_ENV=production
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
FLASK_SERVICE

systemctl daemon-reload
systemctl enable sheetxbubble-flask
systemctl start sheetxbubble-flask
sleep 2

if systemctl is-active --quiet sheetxbubble-flask; then
    print_ok "Flask OMR service running"
else
    print_warn "Flask service may need manual check: systemctl status sheetxbubble-flask"
fi

# ─── 8. Configure Nginx ─────────────────────────────────────
print_step "8/9 - Configuring Nginx..."

cat > /etc/nginx/sites-available/sheetxbubble << NGINX_CONF
server {
    listen 80;
    server_name ${DOMAIN};
    root ${APP_DIR}/public;
    index index.php index.html;

    client_max_body_size 20M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX_CONF

# Enable site
ln -sf /etc/nginx/sites-available/sheetxbubble /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test & restart
nginx -t && systemctl restart nginx
print_ok "Nginx configured"

# ─── 9. Firewall ────────────────────────────────────────────
print_step "9/9 - Configuring firewall..."
if command -v ufw &> /dev/null; then
    ufw allow 22/tcp
    ufw allow 80/tcp
    ufw allow 443/tcp
    ufw --force enable
    print_ok "Firewall configured (SSH + HTTP + HTTPS)"
else
    print_warn "UFW not found, skip firewall setup"
fi

# ─── Done! ──────────────────────────────────────────────────
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║          ✅ Setup Complete!                      ║${NC}"
echo -e "${GREEN}╠══════════════════════════════════════════════════╣${NC}"
echo -e "${GREEN}║                                                  ║${NC}"
echo -e "${GREEN}║  🌐 URL:    http://${DOMAIN}${NC}"
echo -e "${GREEN}║  👤 Admin:  admin@admin.com / password           ║${NC}"
echo -e "${GREEN}║  👨‍🏫 Teacher: teacher@teacher.com / password      ║${NC}"
echo -e "${GREEN}║                                                  ║${NC}"
echo -e "${GREEN}║  📁 App:    ${APP_DIR}${NC}"
echo -e "${GREEN}║  🐍 Flask:  systemctl status sheetxbubble-flask  ║${NC}"
echo -e "${GREEN}║  🗄️  DB:     ${DB_NAME} / ${DB_USER}${NC}"
echo -e "${GREEN}║  🔑 DB Pass: ${DB_PASS}${NC}"
echo -e "${GREEN}║                                                  ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}📌 Save the database password above!${NC}"
echo -e "${YELLOW}📌 Change admin/teacher passwords after first login!${NC}"
echo ""
echo -e "${CYAN}Optional: Setup SSL with Let's Encrypt:${NC}"
echo "  apt install certbot python3-certbot-nginx -y"
echo "  certbot --nginx -d ${DOMAIN}"
echo ""
