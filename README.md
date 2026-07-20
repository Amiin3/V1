Kita akan membuat satu perintah utuh yang langsung bisa Anda salin dan tempel ke terminal server. Perintah ini akan membuat semua file dokumentasi, script auto-installer, dan langsung push ke GitHub.

```bash
cd /var/www/file-manager && \
cat > README.md << 'ENDOFFILE'
# 🔥 ME-WEB Dashboard XL

Dashboard manajemen paket XL berbasis Laravel 11 + Vue 3 + Inertia.

## 🚀 Quick Install
```bash
cd /var/www
git clone https://github.com/Amiin3/V1.git file-manager
cd file-manager
chmod +x install.sh
./install.sh
```

📋 Fitur

· Login Admin (username/password dari .env)
· Login Reseller via OTP (CIAM XL)
· Dashboard Modern ala MyXL
· Auto-Login Admin ke Akun Reseller
· Auto-Refresh Token
· Semua menu: Paket Saya, Beli HOT, Family Plan, Circle, Store, dll.

📖 Panduan Lengkap

Lihat SETUP.md untuk migrasi server step-by-step.

🔐 Default Access

· Admin: /admin/login
· Reseller: /login
  ENDOFFILE
  cat > SETUP.md << 'ENDOFFILE'

🚀 Panduan Migrasi Server

Step 1: Clone Repository

```bash
cd /var/www
git clone https://github.com/Amiin3/V1.git file-manager
cd file-manager
```

Step 2: Jalankan Auto-Installer

```bash
chmod +x install.sh
./install.sh
```

Step 3: Konfigurasi .env

```bash
nano .env
# Isi APP_URL, ADMIN_USERNAME, ADMIN_PASSWORD
# Isi PROVIDER_BASIC_AUTH, PROVIDER_API_KEY, dll.
```

Step 4: Konfigurasi Nginx

```bash
cp nginx.conf /etc/nginx/sites-available/domain-anda
sed -i 's/xl.milastore.cloud/domain-anda.com/g' /etc/nginx/sites-available/domain-anda
ln -s /etc/nginx/sites-available/domain-anda /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

Step 5: SSL dengan Certbot

```bash
apt install certbot python3-certbot-nginx -y
certbot --nginx -d domain-anda.com
```

Step 6: Cloudflare

· DNS: A record ke IP server
· Proxy: ON (oranye)
· SSL/TLS: Full (strict)

✅ Selesai

Akses: https://domain-anda.com
ENDOFFILE
cat > .env.example << 'ENDOFFILE'
APP_NAME="ME-WEB Dashboard"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://domain-anda.com
DB_CONNECTION=sqlite
SESSION_DRIVER=file
CACHE_STORE=file
ADMIN_USERNAME=1Hendrawati
ADMIN_PASSWORD=1Hendrawati
PROVIDER_BASE_API_URL="https://api.myxl.xlaxiata.co.id"
PROVIDER_BASE_CIAM_URL="https://gede.ciam.xlaxiata.co.id"
PROVIDER_BASIC_AUTH=""
PROVIDER_API_KEY=""
PROVIDER_AX_FP_KEY=""
PROVIDER_UA=""
PROVIDER_BASE_CRYPTO_URL="https://me-crypto.mashu.lol/api/890"
PROVIDER_BASE_URL="https://api.myxl.xlaxiata.co.id"
ENDOFFILE
cat > nginx.conf << 'ENDOFFILE'
server {
listen 80;
server_name xl.milastore.cloud;
return 301 https://$host$request_uri;
}
server {
listen 443 ssl;
server_name xl.milastore.cloud;
root /var/www/file-manager/public;
index index.php index.html;
ssl_certificate /etc/nginx/ssl/xl.milastore.cloud/cert.pem;
ssl_certificate_key /etc/nginx/ssl/xl.milastore.cloud/key.pem;
location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
include snippets/fastcgi-php.conf;
fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
}
}
ENDOFFILE
cat > install.sh << 'ENDOFFILE'
#!/bin/bash
set -e
echo "============================================"
echo "   ME-WEB Dashboard - Auto Installer"
echo "============================================"
if [ "$EUID" -ne 0 ]; then echo "Jalankan sebagai root: sudo ./install.sh"; exit 1; fi
echo "[1/5] Install dependensi PHP..."
composer install --no-interaction --optimize-autoloader
echo "[2/5] Setup environment..."
if [ ! -f .env ]; then cp .env.example .env; php artisan key:generate; echo "File .env telah dibuat."; else echo "File .env sudah ada."; fi
echo "[3/5] Mengatur permission..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
echo "[4/5] Menjalankan migrasi database..."
php artisan migrate --force
echo "[5/5] Build frontend..."
npm install && npm run build
echo "============================================"
echo "   INSTALASI SELESAI!"
echo "   Edit .env untuk konfigurasi"
echo "   Setup Nginx (cp nginx.conf)"
echo "   Jalankan certbot untuk SSL"
echo "============================================"
ENDOFFILE
chmod +x install.sh && 
git add README.md SETUP.md install.sh .env.example nginx.conf && 
git commit -m "Add documentation, setup guide, auto-installer, config templates" && 
git push -u origin main && 
echo "✅ Semua selesai! https://github.com/Amiin3/V1"

```
