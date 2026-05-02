# SprintLog Deployment Guide

SprintLog adalah aplikasi logistik berbasis Laravel untuk landing page, tracking paket, pickup, hub, pembayaran, dan panel operasional/admin.

Dokumen ini dibuat sebagai panduan deployment ke Linux Ubuntu, baik dengan cara manual maupun dengan Docker, Docker Swarm, DNS, DHCP, dan Ansible.

Untuk skenario terbaru 2 VM dengan VM 1 sebagai controller/manager/load balancer dan VM 2 sebagai managed worker, lihat [docs/two-vm-infrastructure-runbook.md](docs/two-vm-infrastructure-runbook.md).

Artefak Ansible dan Docker untuk skenario 2 VM tersedia di:

- `infra/ansible`
- `infra/docker/docker-stack.two-vm.yml`
- `infra/docker/haproxy.cfg`

## Stack Aplikasi

- Backend: Laravel 13
- PHP: `^8.3`
- Frontend build: Vite
- Database: MySQL/MariaDB
- Queue: database queue
- Cache/session: database
- PDF: DomPDF

## Status Readiness

Project sudah bisa masuk tahap staging/deploy, tetapi production server tetap perlu konfigurasi environment yang benar.

Yang sudah aman:

- Laravel bisa boot.
- Route/config cache bisa dibuat.
- Migration lokal sudah berjalan.
- Test dasar lulus.
- `public/storage` sudah tersedia.

Yang wajib disiapkan sebelum production:

- `.env` production.
- Database production.
- Asset Vite production build.
- Queue worker.
- Permission `storage` dan `bootstrap/cache`.
- Web server diarahkan ke folder `public`.
- Cache Laravel dibuat di server target, bukan dibawa dari Windows.

## Requirement Ubuntu

Install service utama:

```bash
sudo apt update
sudo apt install -y nginx mysql-server supervisor unzip git curl
```

Install PHP dan extension yang dibutuhkan:

```bash
sudo apt install -y php php-cli php-fpm php-mysql php-xml php-mbstring php-curl php-zip php-fileinfo php-gd php-bcmath
```

Install Composer dan Node.js sesuai standar server yang dipakai. Untuk Vite modern, gunakan Node versi LTS baru.

## Environment Production

Jangan commit file `.env` production. Buat `.env` di server dari `.env.example`, lalu sesuaikan:

```env
APP_NAME=SprintLog
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-kamu.test
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sprintlog
DB_USERNAME=sprintlog_user
DB_PASSWORD=change_this_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=no-reply@domain-kamu.test
MAIL_FROM_NAME="${APP_NAME}"
```

Catatan penting:

- `APP_DEBUG=false` wajib untuk production.
- `APP_URL` harus domain asli.
- `APP_KEY` harus dibuat di server target.
- Database credential jangan disimpan di repo.

## Deploy Manual Ubuntu

Masuk ke folder aplikasi:

```bash
cd /var/www/kilat-hitam
```

Install dependency backend:

```bash
composer install --no-dev --optimize-autoloader
```

Install dan build asset frontend:

```bash
npm install
npm run build
```

Kalau nanti sudah ada `package-lock.json`, gunakan:

```bash
npm ci
npm run build
```

Setup Laravel:

```bash
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan optimize
```

Set permission:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rw storage bootstrap/cache
```

## Nginx Config

Contoh server block untuk deploy manual tanpa container:

```nginx
server {
    listen 80;
    server_name domain-kamu.test;
    root /var/www/kilat-hitam/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan config:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Apache Config

Contoh virtual host untuk deploy manual tanpa container:

```apache
<VirtualHost *:80>
    ServerName domain-kamu.test
    DocumentRoot /var/www/kilat-hitam/public

    <Directory /var/www/kilat-hitam/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/sprintlog-error.log
    CustomLog ${APACHE_LOG_DIR}/sprintlog-access.log combined
</VirtualHost>
```

Aktifkan module dan config:

```bash
sudo a2enmod rewrite headers
sudo apache2ctl configtest
sudo systemctl reload apache2
```

## Queue Worker

Karena project memakai `QUEUE_CONNECTION=database`, worker harus berjalan di background.

Contoh Supervisor config:

```ini
[program:sprintlog-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/kilat-hitam/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/kilat-hitam/storage/logs/worker.log
stopwaitsecs=3600
```

Reload Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

## Docker Plan

Docker adalah jalur utama untuk deployment SprintLog. Project menyediakan dua pilihan web server:

- Nginx + PHP-FPM: default/recommended untuk production container.
- Apache + mod_php: alternatif jika server lab meminta Apache.

File yang tersedia:

- `Dockerfile`: image Laravel PHP-FPM untuk mode Nginx.
- `Dockerfile.nginx`: image Nginx yang membawa folder `public` dan asset Vite.
- `Dockerfile.apache`: image Laravel dengan Apache.
- `docker-stack.nginx.yml`: stack Swarm untuk mode Nginx.
- `docker-stack.apache.yml`: stack Swarm untuk mode Apache.

Build image mode Nginx:

```bash
docker build -t username/sprintlog-app:1.0 -f Dockerfile .
docker build -t username/sprintlog-nginx:1.0 -f Dockerfile.nginx .
```

Build image mode Apache:

```bash
docker build -t username/sprintlog-apache:1.0 -f Dockerfile.apache .
```

Push image ke registry:

```bash
docker login
docker push username/sprintlog-app:1.0
docker push username/sprintlog-nginx:1.0
docker push username/sprintlog-apache:1.0
```

Sebelum deploy stack, sesuaikan nama image di file `docker-stack.*.yml`, lalu export environment:

```bash
export APP_KEY=base64:change_this_key
export DB_PASSWORD=change_this_db_password
export DB_ROOT_PASSWORD=change_this_root_password
export APP_URL=http://sprintlog.local
```

Deploy mode Nginx:

```bash
docker stack deploy -c docker-stack.nginx.yml sprintlog
```

Deploy mode Apache:

```bash
docker stack deploy -c docker-stack.apache.yml sprintlog
```

Setelah service app hidup, jalankan migration dari container app/web:

```bash
docker ps
docker exec -it CONTAINER_ID php artisan migrate --force
docker exec -it CONTAINER_ID php artisan storage:link
docker exec -it CONTAINER_ID php artisan optimize
```

Hal yang harus dijaga di Docker setup:

- `.env` dibaca dari secret/env file, bukan baked ke image.
- `storage` memakai volume persistent.
- `public/build` harus sudah terisi dari `npm run build`.
- Permission `storage` dan `bootstrap/cache` disiapkan saat container start.

## Docker Swarm Plan

Untuk Swarm, pecah service menjadi:

- `sprintlog_web`
- `sprintlog_app`
- `sprintlog_worker`
- `sprintlog_db`

Deploy stack:

```bash
docker swarm init
docker stack deploy -c docker-stack.yml sprintlog
docker service ls
```

Rekomendasi Swarm:

- Gunakan Docker secrets untuk password database dan `APP_KEY`.
- Gunakan named volume untuk database dan storage.
- Jalankan migration satu kali dari container app.
- Worker dibuat service terpisah supaya bisa scale tanpa mengganggu web.

## DNS Plan

DNS dipakai untuk mengarahkan domain ke IP server.

Contoh record:

```text
A     sprintlog.example.test      192.168.10.20
A     admin.sprintlog.example.test 192.168.10.20
CNAME www.sprintlog.example.test  sprintlog.example.test
```

Untuk jaringan lab/internal, DNS bisa diarahkan ke IP Ubuntu server. Untuk public internet, gunakan DNS provider domain asli.

## DHCP Plan

DHCP tidak langsung menjalankan aplikasi, tetapi membantu provisioning jaringan.

Rekomendasi:

- Server aplikasi sebaiknya memakai static IP atau DHCP reservation.
- Client boleh DHCP biasa.
- DNS server internal bisa dikirim lewat DHCP option.

Contoh konsep:

```text
Server SprintLog: 192.168.10.20
Gateway:          192.168.10.1
DNS internal:     192.168.10.10
DHCP range:       192.168.10.100 - 192.168.10.200
```

## Ansible Plan

Ansible cocok untuk mengulang setup server tanpa konfigurasi manual.

Struktur sederhana:

```text
ansible/
  inventory.ini
  playbook.yml
  roles/
    php/
    nginx/
    mysql/
    docker/
    sprintlog/
```

Contoh inventory:

```ini
[web]
sprintlog-01 ansible_host=192.168.10.20 ansible_user=ubuntu
```

Contoh task utama:

```yaml
- hosts: web
  become: true
  tasks:
    - name: Install base packages
      apt:
        name:
          - nginx
          - supervisor
          - mysql-server
          - php
          - php-fpm
          - php-mysql
          - php-xml
          - php-mbstring
          - php-curl
          - php-zip
          - php-gd
        update_cache: true
        state: present
```

Ansible sebaiknya mengurus:

- Install package server.
- Copy Nginx config.
- Setup `.env` dari template.
- Set permission.
- Run Composer/NPM/build.
- Run migration.
- Restart PHP-FPM, Nginx, dan Supervisor.

## Deployment Checklist

Sebelum deploy:

- Pastikan `.env` production siap.
- Pastikan database sudah dibuat.
- Pastikan `APP_DEBUG=false`.
- Pastikan domain sudah mengarah ke server.
- Pastikan asset sudah di-build.
- Pastikan queue worker aktif.

Setelah deploy:

```bash
php artisan about
php artisan migrate:status
php artisan queue:failed
php artisan route:list
```

Cek browser:

- Homepage terbuka.
- Login admin bisa masuk.
- Landing CMS bisa dibuka.
- Hub network bisa dibuka.
- Shipment/tracking bisa dibuat dan dilacak.
- Upload/storage bisa diakses.

## Troubleshooting

Permission error:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rw storage bootstrap/cache
```

Asset tidak muncul:

```bash
npm install
npm run build
php artisan optimize:clear
```

Route atau config aneh setelah pindah server:

```bash
php artisan optimize:clear
php artisan optimize
```

Queue tidak jalan:

```bash
sudo supervisorctl status
php artisan queue:failed
```

Database belum siap:

```bash
php artisan migrate:status
php artisan migrate --force
```

## Catatan Penting

- Jangan deploy folder `vendor` dari Windows kalau server bisa menjalankan Composer sendiri.
- Jangan deploy `node_modules`.
- Jangan deploy cache lokal dari `bootstrap/cache` selain file bawaan seperti `.gitignore`, `packages.php`, dan `services.php`.
- Jangan deploy compiled Blade dari `storage/framework/views`.
- Jangan simpan secret di README, repo, atau Docker image.
- Generate cache Laravel langsung di server production setelah `.env` benar.
