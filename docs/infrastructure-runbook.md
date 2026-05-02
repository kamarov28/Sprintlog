# SprintLog 3 VM Infrastructure Runbook

Dokumen ini adalah runbook persiapan deployment SprintLog dengan 3 VM. Alurnya disusun mengikuti kebutuhan infrastruktur: Ansible controller, managed nodes, remote SSH, web server, HTTPS, DNS, Docker, Dockerfile, Docker Swarm, backup/restore, dan VCS.

Gunakan dokumen ini sebagai panduan lab dan checklist teknis sebelum deployment.

## Target Topologi

| VM | Hostname | IP Lab | Role Utama | Service |
| --- | --- | --- | --- | --- |
| VM 1 | `control-manager` | `10.10.10.10` | Controller + Swarm manager | Ansible, Git, SSH key, Docker Swarm manager, optional DNS/DHCP |
| VM 2 | `sprintlog-app` | `10.10.10.20` | Managed node 1 | Docker Swarm worker, Laravel app/web service |
| VM 3 | `sprintlog-data` | `10.10.10.30` | Managed node 2 | Docker Swarm worker, database, queue worker, optional mail server |

Domain lab:

```text
sprintlog.local        -> 10.10.10.10
admin.sprintlog.local  -> 10.10.10.10
db.sprintlog.local     -> 10.10.10.30
mail.sprintlog.local   -> 10.10.10.30
```

Adapter VM yang direkomendasikan:

- Adapter 1: NAT, untuk internet dan install package.
- Adapter 2: Host-only/Internal, untuk jaringan lab antar VM.
- Adapter 3: optional, untuk simulasi network tambahan jika dibutuhkan.

## Pembagian Tugas Per VM

### VM 1 - Controller and Swarm Manager

Dipakai sebagai pusat kontrol lab dan pusat deployment Swarm.

Install:

- `ansible`
- `openssh-client`
- `git`
- Docker Engine
- optional `bind9`
- optional `isc-dhcp-server`

Tugas:

- Generate SSH key.
- Copy SSH key ke VM 2 dan VM 3.
- Menyimpan inventory Ansible.
- Menjalankan playbook setup server.
- Inisialisasi Docker Swarm.
- Deploy stack SprintLog dari manager node.
- Menjadi pusat dokumentasi dan command execution.

### VM 2 - SprintLog App Node

Dipakai sebagai node utama aplikasi.

Install:

- Docker Engine
- Docker Compose plugin
- Nginx jika web gateway ditempatkan langsung di node ini
- OpenSSH server

Tugas:

- Docker Swarm worker.
- Menjalankan service web/app SprintLog.
- Menjadi worker untuk container aplikasi, sementara domain utama bisa tetap masuk lewat Swarm manager/ingress di VM 1.
- Menjadi node tempat migration dijalankan.

### VM 3 - SprintLog Data Node and Optional Mail Server

Dipakai sebagai worker, service data, dan service tambahan seperti mail server jika dibutuhkan.

Install:

- Docker Engine
- OpenSSH server
- optional Postfix/Dovecot untuk mail server sederhana

Tugas:

- Docker Swarm worker.
- Menjalankan database container atau service worker.
- Menyimpan volume database/storage jika dipilih.
- Menjalankan Laravel queue worker sebagai service terpisah.
- Menjalankan mail server sederhana untuk domain lab jika skenario deployment membutuhkan.
- Menjadi target SMTP aplikasi SprintLog jika email notification diuji.

## Urutan Deployment

### 1. Siapkan IP dan SSH

Pastikan semua VM bisa saling ping lewat jaringan lab.

Contoh cek:

```bash
ping 10.10.10.10
ping 10.10.10.20
ping 10.10.10.30
```

Install SSH server di managed nodes:

```bash
sudo apt update
sudo apt install -y openssh-server
sudo systemctl enable --now ssh
```

Dari controller:

```bash
ssh-keygen
ssh-copy-id user@10.10.10.20
ssh-copy-id user@10.10.10.30
```

Verifikasi:

```bash
ssh user@10.10.10.20
ssh user@10.10.10.30
```

### 2. Install Ansible di Controller

```bash
sudo apt update
sudo apt install -y software-properties-common
sudo add-apt-repository --yes --update ppa:ansible/ansible
sudo apt install -y ansible
ansible --version
```

Buat folder lab:

```bash
mkdir -p ~/sprintlog-ansible
cd ~/sprintlog-ansible
```

Buat `inventory.ini`:

```ini
[manager]
control-manager ansible_host=10.10.10.10 ansible_user=user

[app]
sprintlog-app ansible_host=10.10.10.20 ansible_user=user

[data]
sprintlog-data ansible_host=10.10.10.30 ansible_user=user

[managed:children]
app
data

[swarm:children]
manager
app
data
```

Buat `ansible.cfg`:

```ini
[defaults]
inventory = ./inventory.ini
host_key_checking = False
retry_files_enabled = False
```

Tes koneksi:

```bash
ansible managed --list-hosts
ansible managed -m ping
```

### 3. Install Docker dengan Ansible

Contoh `playbook.yml` awal:

```yaml
- hosts: managed
  become: true
  tasks:
    - name: Install base packages
      apt:
        name:
          - ca-certificates
          - curl
          - git
          - unzip
          - openssh-server
        update_cache: true
        state: present

    - name: Install Docker
      apt:
        name:
          - docker.io
          - docker-compose-v2
        state: present

    - name: Enable Docker
      service:
        name: docker
        enabled: true
        state: started
```

Jalankan:

```bash
ansible-playbook playbook.yml
ansible managed -a "docker --version" -b
```

### 4. Init Docker Swarm

Di VM 1:

```bash
docker swarm init --advertise-addr 10.10.10.10
docker swarm join-token worker
```

Di VM 2 dan VM 3, jalankan command join yang muncul dari VM 1:

```bash
docker swarm join --token TOKEN 10.10.10.10:2377
```

Verifikasi dari VM 1:

```bash
docker node ls
```

### 5. Build Image SprintLog

SprintLog menyediakan dua jalur web server berbasis Dockerfile:

- Nginx + PHP-FPM, memakai `Dockerfile` dan `Dockerfile.nginx`.
- Apache, memakai `Dockerfile.apache`.

Target image mode Nginx:

```text
username/sprintlog-app:1.0
username/sprintlog-nginx:1.0
```

Build dan push mode Nginx:

```bash
docker build -t username/sprintlog-app:1.0 -f Dockerfile .
docker build -t username/sprintlog-nginx:1.0 -f Dockerfile.nginx .
docker login
docker push username/sprintlog-app:1.0
docker push username/sprintlog-nginx:1.0
```

Target image mode Apache:

```text
username/sprintlog-apache:1.0
```

Build dan push mode Apache:

```bash
docker build -t username/sprintlog-apache:1.0 -f Dockerfile.apache .
docker login
docker push username/sprintlog-apache:1.0
```

Catatan:

- Jangan bake `.env` production ke image.
- Jalankan `composer install --no-dev --optimize-autoloader` saat build.
- Jalankan `npm run build` saat build atau sebelum build.
- Pastikan `public/build/manifest.json` tersedia di image.
- Untuk Nginx mode, image Nginx juga membawa folder `public` supaya static asset tetap tersedia walaupun container PHP-FPM berjalan terpisah.

### 6. Deploy Stack SprintLog

Pilih salah satu mode stack.

Mode Nginx:

```bash
export APP_KEY=base64:change_this_key
export DB_PASSWORD=change_this_db_password
export DB_ROOT_PASSWORD=change_this_root_password
export APP_URL=http://sprintlog.local
docker stack deploy -c docker-stack.nginx.yml sprintlog
```

Mode Apache:

```bash
export APP_KEY=base64:change_this_key
export DB_PASSWORD=change_this_db_password
export DB_ROOT_PASSWORD=change_this_root_password
export APP_URL=http://sprintlog.local
docker stack deploy -c docker-stack.apache.yml sprintlog
```

Service yang disediakan:

- `app`: PHP-FPM Laravel.
- `web`: Nginx atau Apache.
- `db`: MySQL/MariaDB.
- `worker`: Laravel queue worker.
- optional `mail`: Postfix/Dovecot atau SMTP relay internal jika skenario mail server ikut diuji.

Setelah container app hidup:

```bash
docker exec -it CONTAINER_APP php artisan migrate --force
docker exec -it CONTAINER_APP php artisan storage:link
docker exec -it CONTAINER_APP php artisan optimize
```

### 7. DNS Server

Jika DNS diletakkan di VM 1:

```bash
sudo apt install -y bind9
```

Contoh zone di `/etc/bind/named.conf.local`:

```conf
zone "sprintlog.local" {
    type master;
    file "/etc/bind/db.sprintlog.local";
};
```

Contoh `/etc/bind/db.sprintlog.local`:

```dns
$TTL 604800
@   IN  SOA sprintlog.local. admin.sprintlog.local. (
        2
        604800
        86400
        2419200
        604800 )

@       IN  NS      sprintlog.local.
@       IN  A       10.10.10.10
admin   IN  A       10.10.10.10
db      IN  A       10.10.10.30
mail    IN  A       10.10.10.30
@       IN  MX 10   mail.sprintlog.local.
```

Restart dan cek:

```bash
sudo named-checkconf
sudo named-checkzone sprintlog.local /etc/bind/db.sprintlog.local
sudo systemctl restart bind9
nslookup sprintlog.local 10.10.10.10
```

### 8. DHCP Server

DHCP hanya perlu dipakai kalau skenario lab membutuhkan simulasi DHCP. Kalau memakai VirtualBox Host-only DHCP, jangan jalankan dua DHCP server di network yang sama.

Rekomendasi aman untuk lab:

- Server SprintLog tetap static IP.
- DHCP hanya untuk client lab.
- DNS internal diarahkan ke VM 1.

Contoh scope:

```text
Network: 10.10.10.0/24
Gateway: 10.10.10.1
DNS:     10.10.10.10
Range:   10.10.10.100 - 10.10.10.200
```

### 9. HTTPS

Untuk lab lokal, gunakan self-signed certificate.

```bash
sudo mkdir -p /etc/sprintlog/ssl
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/sprintlog/ssl/sprintlog.key \
  -out /etc/sprintlog/ssl/sprintlog.crt
```

Jika host gateway memakai Nginx:

```nginx
server {
    listen 443 ssl;
    server_name sprintlog.local admin.sprintlog.local;

    ssl_certificate /etc/sprintlog/ssl/sprintlog.crt;
    ssl_certificate_key /etc/sprintlog/ssl/sprintlog.key;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
    }
}
```

Verifikasi:

```bash
sudo nginx -t
sudo systemctl reload nginx
curl -k https://sprintlog.local
```

Jika host gateway memakai Apache:

```apache
<VirtualHost *:443>
    ServerName sprintlog.local
    ServerAlias admin.sprintlog.local

    SSLEngine on
    SSLCertificateFile /etc/sprintlog/ssl/sprintlog.crt
    SSLCertificateKeyFile /etc/sprintlog/ssl/sprintlog.key

    ProxyPreserveHost On
    ProxyPass / http://127.0.0.1:8080/
    ProxyPassReverse / http://127.0.0.1:8080/
</VirtualHost>
```

Enable module dan reload:

```bash
sudo a2enmod ssl proxy proxy_http headers
sudo apache2ctl configtest
sudo systemctl reload apache2
curl -k https://sprintlog.local
```

### 10. Mail Server Optional di VM 3

Jika mail server ikut masuk skenario, VM 3 bisa menjalankan mail server sederhana untuk domain lab.

Paket umum:

```bash
sudo apt update
sudo apt install -y postfix dovecot-imapd dovecot-pop3d mailutils
```

Konsep domain:

```text
Domain mail: sprintlog.local
Mail host:   mail.sprintlog.local
IP mail:     10.10.10.30
```

DNS record yang perlu ada:

```dns
mail    IN  A       10.10.10.30
@       IN  MX 10   mail.sprintlog.local.
```

Pengujian dasar:

```bash
systemctl status postfix
systemctl status dovecot
nslookup mail.sprintlog.local 10.10.10.10
telnet mail.sprintlog.local 25
```

Jika SprintLog diarahkan memakai mail server VM 3, set `.env` aplikasi:

```env
MAIL_MAILER=smtp
MAIL_HOST=mail.sprintlog.local
MAIL_PORT=25
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=no-reply@sprintlog.local
MAIL_FROM_NAME="${APP_NAME}"
```

Catatan:

- Untuk lab internal, port `25` tanpa TLS bisa dipakai jika memang hanya simulasi lokal.
- Untuk production beneran, gunakan SMTP provider atau mail server dengan TLS, SPF, DKIM, DMARC, dan reverse DNS.
- Kalau mail server berjalan langsung di host VM 3, jangan bentrok dengan container yang memakai port `25`, `110`, `143`, `465`, `587`, atau `993`.

### 11. Backup dan Restore

Backup file aplikasi:

```bash
tar -czvf sprintlog-app-backup.tar.gz /var/www/kilat-hitam
```

Backup database container:

```bash
docker exec CONTAINER_DB mysqldump -u root -p sprintlog > sprintlog-db.sql
```

Backup volume/storage:

```bash
rsync -av /var/www/kilat-hitam/storage/ /backup/sprintlog-storage/
```

Restore database:

```bash
docker exec -i CONTAINER_DB mysql -u root -p sprintlog < sprintlog-db.sql
```

## Bukti Uji yang Perlu Disiapkan

Ambil screenshot atau catat output command berikut:

```bash
ansible --version
ansible managed -m ping
docker node ls
docker service ls
docker stack ps sprintlog
docker logs SERVICE_OR_CONTAINER
nslookup sprintlog.local 10.10.10.10
nslookup mail.sprintlog.local 10.10.10.10
curl -I http://sprintlog.local
curl -k -I https://sprintlog.local
php artisan migrate:status
php artisan about
systemctl status postfix
systemctl status dovecot
```

Bukti browser:

- Homepage SprintLog terbuka.
- Login admin berhasil.
- Halaman Landing CMS terbuka.
- Halaman Hub Network terbuka.
- Tracking paket bisa dicari.
- HTTPS bisa diakses.

## Checklist Cepat

- [ ] Semua VM bisa ping.
- [ ] SSH key dari controller ke managed nodes berhasil.
- [ ] Ansible inventory terbaca.
- [ ] `ansible managed -m ping` sukses.
- [ ] Docker terinstall di VM 1, VM 2, dan VM 3.
- [ ] Swarm manager aktif di VM 1.
- [ ] VM 2 dan VM 3 join sebagai worker.
- [ ] Image SprintLog berhasil build.
- [ ] Stack SprintLog berhasil deploy.
- [ ] Migration Laravel berhasil.
- [ ] Queue worker berjalan.
- [ ] DNS resolve ke IP app.
- [ ] DNS resolve ke IP mail jika mail server digunakan.
- [ ] HTTPS aktif.
- [ ] Mail server aktif jika skenario mail digunakan.
- [ ] Backup database dan storage bisa dibuat.

## Catatan Strategi

Kalau waktu deployment terbatas, prioritas urutan:

1. SSH dan Ansible ping.
2. Docker install di managed nodes.
3. Swarm init dan join.
4. Deploy stack minimal.
5. DNS resolve.
6. HTTPS.
7. Mail server jika diminta.
8. Backup/restore.

Yang paling sering bikin error:

- IP adapter salah.
- SSH key belum masuk ke managed node.
- User belum punya akses `sudo`.
- Docker service belum jalan.
- Image belum di-push, sehingga worker tidak bisa pull.
- `.env` production salah.
- Database belum ready saat app start.
- Cache Laravel dibuat sebelum `.env` benar.
- Postfix/Dovecot hidup, tetapi DNS MX belum diarahkan ke VM 3.
- Port mail bentrok antara service host dan container.
