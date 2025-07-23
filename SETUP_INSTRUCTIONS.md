# 🚀 Predictive Dialer Setup Instructions

## 📋 Prerequisites
- Ubuntu 20.04+ atau 22.04+
- PHP 8.2+
- Node.js 18+
- Composer
- Laravel 12
- Asterisk PBX

## 🔧 Step 1: Setup Asterisk PBX

### 1.1 Install Asterisk (jika belum terinstall)
```bash
sudo apt update
sudo apt install asterisk
```

### 1.2 Setup Production Configuration
```bash
# Jalankan script setup yang sudah disediakan
sudo ./setup-asterisk-production.sh
```

### 1.3 Update IP Public (PENTING!)
Edit file `/etc/asterisk/pjsip.conf` dan ganti `YOUR_PUBLIC_IP` dengan IP public server Anda:
```bash
sudo nano /etc/asterisk/pjsip.conf
```

Cari baris:
```
external_media_address=YOUR_PUBLIC_IP
external_signaling_address=YOUR_PUBLIC_IP
```

Ganti dengan IP public server Anda.

### 1.4 Restart Asterisk
```bash
sudo systemctl restart asterisk
sudo systemctl status asterisk
```

### 1.5 Verifikasi SIP Registration
```bash
sudo asterisk -r
pjsip show registrations
pjsip show endpoints
```

## 🔧 Step 2: Setup Laravel Application

### 2.1 Install Dependencies
```bash
composer install
npm install
```

### 2.2 Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` file dengan konfigurasi yang sesuai (sudah ada di .env.example).

### 2.3 Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 2.4 Storage Link
```bash
php artisan storage:link
```

### 2.5 Build Assets
```bash
npm run build
```

## 🔧 Step 3: Setup Queue Worker

### 3.1 Install Supervisor (untuk production)
```bash
sudo apt install supervisor
```

### 3.2 Create Queue Worker Configuration
```bash
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

Isi dengan:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
stopwaitsecs=3600
```

### 3.3 Start Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

## 🔧 Step 4: Setup Web Server (Nginx)

### 4.1 Install Nginx
```bash
sudo apt install nginx
```

### 4.2 Create Site Configuration
```bash
sudo nano /etc/nginx/sites-available/predictive-dialer
```

Isi dengan:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/your/project/public;

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
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 4.3 Enable Site
```bash
sudo ln -s /etc/nginx/sites-available/predictive-dialer /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## 🔧 Step 5: Setup SIP Clients untuk Agents

### 5.1 Konfigurasi SIP Client
Gunakan aplikasi seperti:
- **Desktop**: X-Lite, Zoiper, MicroSIP
- **Mobile**: Zoiper, Linphone

### 5.2 Settings SIP Client
```
Server/Domain: IP_SERVER_ANDA
Port: 5060
Username: agent01 (sampai agent10)
Password: agent01pass (sampai agent10pass)
Transport: UDP
```

## 🔧 Step 6: Testing

### 6.1 Test AMI Connection
```bash
telnet localhost 5038
```

### 6.2 Test SIP Registration
```bash
sudo asterisk -r
pjsip show endpoints
```

### 6.3 Test Web Application
1. Buka browser ke `http://your-domain.com`
2. Login dengan:
   - **Super Admin**: haydarSuperAdmin@example.com / haydar123
   - **Admin**: haydarAdmin@example.com / haydar123

### 6.4 Test Predictive Dialer
1. Upload campaign dengan template yang sesuai
2. Start campaign dari dashboard
3. Monitor calls di real-time

## 📱 Fitur Baru yang Ditambahkan

### 1. Download Template Campaign
- Template otomatis sesuai product type
- Akses via tombol "Download Template" di halaman upload

### 2. Dashboard Admin Enhanced
- Total users by role
- Campaign statistics by product type
- Real-time performance metrics
- Top performing agents

### 3. User Management
- CRUD operations untuk users
- Role management (SuperAdmin, Admin, Agent)
- Extension management untuk agents

### 4. Campaign Management Enhanced
- Tombol destroy campaign
- View customer data (nasbahs)
- Export customer data
- Delete individual customer records

### 5. Real-time Updates
- Campaign status changes
- Import progress notifications
- Live dashboard updates

## 🔍 Troubleshooting

### Asterisk Issues
```bash
# Check Asterisk status
sudo systemctl status asterisk

# View Asterisk logs
sudo tail -f /var/log/asterisk/messages

# Check SIP registration
sudo asterisk -r
pjsip show registrations
```

### Laravel Issues
```bash
# Check queue worker
sudo supervisorctl status laravel-worker:*

# View Laravel logs
tail -f storage/logs/laravel.log

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Network Issues
```bash
# Check ports
sudo netstat -tulpn | grep :5038
sudo netstat -tulpn | grep :5060

# Test connectivity
telnet 49.128.184.138 5060
```

## 📞 Default Login Credentials

- **Super Admin**: haydarSuperAdmin@example.com / haydar123
- **Admin**: haydarAdmin@example.com / haydar123

## 🎯 Next Steps

1. Setup SSL certificate untuk production
2. Configure firewall rules
3. Setup monitoring dan alerting
4. Backup strategy
5. Performance tuning

## 📧 Support

Jika ada masalah, check:
1. Asterisk logs: `/var/log/asterisk/messages`
2. Laravel logs: `storage/logs/laravel.log`
3. Nginx logs: `/var/log/nginx/error.log`
4. Queue worker logs: `storage/logs/worker.log`