# JOBSHEET SPRINTLOG 2 VM DEPLOYMENT

Mata Pelajaran: DevOps / Platform Komputasi Awan

Materi: VCS, Ansible, Docker, Dockerfile, Docker Volume, Docker Network, Docker Compose/Stack, DNS, DHCP, Mail Server, dan Load Balancing

Topik Praktik: Deploy aplikasi SprintLog menggunakan 2 VM Ubuntu

## Daftar Pembahasan

- Deskripsi praktik
- Persiapan lab
- Topologi jaringan
- Konfigurasi VCS GitHub
- Instalasi dan konfigurasi Ansible
- Instalasi Docker pada 2 VM
- Build image aplikasi Apache otomatis
- Deploy aplikasi dengan Docker Swarm
- Load balancing menggunakan Nginx
- Konfigurasi DNS dan DHCP
- Konfigurasi mail server optional
- Pengujian akhir
- Checklist bukti praktik

## Deskripsi Praktik

Pada kegiatan praktik ini, peserta didik melakukan deployment aplikasi SprintLog menggunakan 2 mesin virtual Ubuntu. VM 1 digunakan sebagai controller Ansible, Docker Swarm manager, load balancer Nginx, DNS/DHCP/mail server optional, dan tempat service database berjalan. VM 2 digunakan sebagai managed node sekaligus Docker Swarm worker untuk menjalankan replica aplikasi.

Aplikasi SprintLog disimpan pada VCS GitHub agar server tidak perlu menerima file melalui file sharing. VM 1 akan melakukan clone repository dari GitHub, menjalankan Ansible playbook, menginstal package yang dibutuhkan, menginisialisasi Docker Swarm, dan melakukan deploy stack aplikasi.

Service aplikasi menggunakan Apache di dalam container Laravel. Load balancing menggunakan Nginx pada service `lb`, lalu trafik diarahkan ke service `app` yang memiliki 2 replica.

Pada versi otomatis, VM 1 juga menjalankan registry Docker lokal pada port 5000. Image aplikasi dibuat dari `Dockerfile.apache` oleh Ansible, lalu dipush ke registry lokal agar VM 1 dan VM 2 dapat menarik image yang sama tanpa Docker Hub.

## Persiapan Lab

Siapkan 2 VM Ubuntu yang sudah selesai `update` dan `upgrade`.

| VM | Hostname | Contoh IP | Fungsi |
| --- | --- | --- | --- |
| VM 1 | `vm1` | `192.168.56.10` | Ansible controller, Swarm manager, Nginx LB, DNS/DHCP/mail optional, database |
| VM 2 | `vm2` | `192.168.56.20` | Ansible managed node, Swarm worker, replica aplikasi |

Software yang digunakan:

- Ubuntu Server
- Git
- Ansible
- Docker Engine
- Docker Swarm
- Nginx container
- Apache Laravel container
- MySQL container
- Postfix optional
- GitHub sebagai VCS

## Topologi

```text
Client/Browser
     |
     v
VM 1: Nginx Load Balancer :80
     |
     v
Docker Swarm service app
     |----------------------|
     v                      v
VM 1: Apache App replica    VM 2: Apache App replica
     |
     v
VM 1: MySQL volume

VM 1: Local Docker Registry :5000
```

Domain lab:

```text
sprintlog.local       -> 192.168.56.10
www.sprintlog.local   -> 192.168.56.10
admin.sprintlog.local -> 192.168.56.10
mail.sprintlog.local  -> 192.168.56.10
```

## Praktik 1 - Menyiapkan Repository GitHub

Pada komputer pengembangan, pastikan project sudah menjadi repository Git.

```bash
git status
git remote -v
```

Jika repository belum memiliki remote GitHub, buat repository baru di GitHub lalu hubungkan:

```bash
git remote add origin https://github.com/USERNAME/sprintlog.git
git branch -M main
git push -u origin main
```

Jika remote sudah ada, cukup commit dan push perubahan terbaru:

```bash
git add .
git commit -m "Prepare SprintLog two VM deployment"
git push origin main
```

Catatan: jangan commit file `.env`, password production, atau file inventory asli.

## Praktik 2 - Menyiapkan SSH dari VM 1 ke VM 2

Login ke VM 1, lalu install package awal:

```bash
sudo apt update
sudo apt install -y git ansible openssh-client
```

Buat SSH key pada VM 1:

```bash
ssh-keygen
```

Salin public key VM 1 ke VM 2:

```bash
ssh-copy-id ubuntu@192.168.56.20
```

Verifikasi akses tanpa password:

```bash
ssh ubuntu@192.168.56.20
exit
```

## Praktik 3 - Menyiapkan Inventory Ansible

Clone repository SprintLog pada VM 1:

```bash
git clone https://github.com/USERNAME/sprintlog.git
cd sprintlog/infra/ansible
```

Buat inventory dari contoh:

```bash
cp inventory.example.ini inventory.ini
```

Edit file inventory:

```bash
nano inventory.ini
```

Contoh isi inventory:

```ini
[controller]
vm1 ansible_host=192.168.56.10 ansible_user=ubuntu

[managed]
vm2 ansible_host=192.168.56.20 ansible_user=ubuntu

[docker_hosts:children]
controller
managed

[swarm_manager]
vm1

[swarm_workers]
vm2
```

Verifikasi host yang dikelola:

```bash
ansible all --list-hosts
ansible docker_hosts --list-hosts
```

Uji koneksi:

```bash
ansible all -m ping
```

## Praktik 4 - Mengatur Variable Deployment

Edit variable utama:

```bash
nano group_vars/all.yml
```

Bagian yang wajib disesuaikan:

```yaml
lab_domain: sprintlog.local
controller_ip: 192.168.56.10
managed_ip: 192.168.56.20
app_repo_url: "https://github.com/USERNAME/sprintlog.git"
docker_registry_host: "192.168.56.10:5000"
app_key: ""
db_password: "ganti_password_database"
db_root_password: "ganti_password_root"
enable_mail: false
database_seed_mode: accounts
deploy_admin_email: "admin@sprintlog.com"
deploy_admin_password: "password"
```

Jika `app_key` dikosongkan, Ansible akan membuat `APP_KEY` otomatis untuk kebutuhan lab.

Mode data awal:

```yaml
database_seed_mode: accounts # hanya akun login
database_seed_mode: demo     # data sample lengkap dari DatabaseSeeder
database_seed_mode: none     # tidak seed data
```

## Praktik 5 - Build dan Push Docker Image Apache Otomatis

Pada versi sebelumnya, build dan push image dapat dilakukan manual. Pada ketentuan 2 VM ini, proses dibuat otomatis oleh playbook.

Ansible akan melakukan:

- Membuat registry lokal di VM 1 dengan alamat `controller_ip:5000`.
- Clone atau update repository dari GitHub.
- Build image Apache menggunakan `Dockerfile.apache`.
- Push image ke registry lokal.
- Menggunakan image tersebut pada Docker stack.

Nama image otomatis:

```text
192.168.56.10:5000/sprintlog-apache:latest
```

## Praktik 6 - Menjalankan Ansible Playbook

Dari folder `infra/ansible` di VM 1, jalankan:

```bash
ansible-playbook site.yml
```

Playbook akan melakukan:

- Install base package pada VM 1 dan VM 2.
- Install Ansible support pada VM 1.
- Install dan konfigurasi DNS jika `enable_dns: true`.
- Install dan konfigurasi DHCP jika `enable_dhcp: true`.
- Install dan konfigurasi mail server jika `enable_mail: true`.
- Install Docker pada VM 1 dan VM 2.
- Konfigurasi Docker agar dapat pull dari registry lokal VM 1.
- Inisialisasi Docker Swarm pada VM 1.
- Join VM 2 sebagai Swarm worker.
- Membuat registry lokal di VM 1.
- Clone/update repository SprintLog dari GitHub.
- Build dan push image Apache secara otomatis.
- Generate `APP_KEY` jika masih kosong.
- Render file environment stack.
- Deploy Docker stack SprintLog.
- Menjalankan migrasi Laravel, `storage:link`, dan `optimize`.

## Praktik 7 - Memeriksa Docker Swarm

Jalankan pada VM 1:

```bash
docker node ls
docker stack services sprintlog
docker stack ps sprintlog
```

Pastikan service berikut berjalan:

- `sprintlog_lb`
- `sprintlog_app`
- `sprintlog_worker`
- `sprintlog_db`

Pastikan `sprintlog_app` memiliki 2 replica.

## Praktik 8 - Load Balancing Menggunakan Nginx

File stack berada pada:

```text
infra/docker/docker-stack.two-vm.yml
```

File konfigurasi Nginx load balancer:

```text
infra/docker/nginx-lb.conf
```

Service `lb` menggunakan image:

```yaml
image: nginx:1.27-alpine
```

Nginx menerima request dari port 80 dan meneruskan request ke service `app`.

Uji akses:

```bash
curl -I http://sprintlog.local
curl http://192.168.56.10
```

## Praktik 9 - Konfigurasi DNS

DNS dikelola oleh template Ansible:

```text
infra/ansible/templates/named.conf.local.j2
infra/ansible/templates/db.sprintlog.local.j2
```

Variable DNS:

```yaml
enable_dns: true
dns_zone_file: /etc/bind/db.sprintlog.local
```

Verifikasi DNS pada VM 1:

```bash
systemctl status bind9
nslookup sprintlog.local 127.0.0.1
nslookup admin.sprintlog.local 127.0.0.1
nslookup mail.sprintlog.local 127.0.0.1
```

Jika client belum memakai DNS VM 1, tambahkan sementara ke file hosts client:

```text
192.168.56.10 sprintlog.local www.sprintlog.local admin.sprintlog.local
```

## Praktik 10 - Konfigurasi DHCP Optional

DHCP default dimatikan untuk menghindari konflik dengan DHCP server lain.

Variable DHCP:

```yaml
enable_dhcp: false
dhcp_interface: enp0s8
dhcp_subnet: 192.168.56.0
dhcp_netmask: 255.255.255.0
dhcp_range_start: 192.168.56.100
dhcp_range_end: 192.168.56.200
dhcp_router: 192.168.56.1
```

Aktifkan DHCP hanya jika VM 1 memang menjadi satu-satunya DHCP server pada network lab:

```yaml
enable_dhcp: true
```

Verifikasi:

```bash
systemctl status isc-dhcp-server
journalctl -u isc-dhcp-server -n 50
```

## Praktik 11 - Konfigurasi Mail Server Optional

Mail server disediakan pada VM 1 mengikuti jobsheet Mail Server Sederhana. Komponen yang dipakai:

- Postfix sebagai MTA.
- Dovecot sebagai MDA/IMAP.
- Mutt sebagai MUA terminal.
- Maildir sebagai format penyimpanan email user.

Default dimatikan agar SMTP/IMAP tidak aktif tanpa kebutuhan.

Variable mail server:

```yaml
enable_mail: false
mail_hostname: "mail.{{ lab_domain }}"
mail_domain: "{{ lab_domain }}"
mail_test_users:
  - name: user1
    password: password
  - name: user2
    password: password
```

Jika jobsheet meminta mail server, ubah:

```yaml
enable_mail: true
```

Saat aktif, Ansible akan:

- Install `postfix` dan `mailutils`.
- Install `dovecot-core`, `dovecot-imapd`, dan `mutt`.
- Konfigurasi `/etc/mailname`.
- Konfigurasi `/etc/postfix/main.cf`.
- Konfigurasi Dovecot `mail_location = maildir:~/Maildir`.
- Konfigurasi Mutt pada `/etc/skel/.muttrc`.
- Membuat user lab `user1` dan `user2`.
- Membuat folder `~/Maildir/{cur,new,tmp,Sent,Drafts}` untuk user mail.
- Menambahkan record `mail.sprintlog.local`.
- Menambahkan MX record untuk domain `sprintlog.local`.
- Mengarahkan Laravel mail host ke `mail.sprintlog.local` port `25`.

Verifikasi pada VM 1:

```bash
systemctl status postfix
systemctl status dovecot
nslookup mail.sprintlog.local 127.0.0.1
dig MX sprintlog.local @127.0.0.1
```

Uji kirim email lokal:

```bash
su - user2
echo "Halo, email ini dikirim dari user2" | mail -s "Uji Coba" user1
exit
su - user1
mutt
```

## Praktik 12 - Migrasi Database Laravel

Migrasi database Laravel dijalankan otomatis oleh Ansible setelah service `db` dan `app` aktif. Jika ingin menjalankan ulang secara manual, gunakan:

```bash
APP_CONTAINER=$(docker ps --filter "name=sprintlog_app" --format "{{.ID}}" | head -n 1)
docker exec -it "$APP_CONTAINER" php artisan migrate --force
docker exec -it "$APP_CONTAINER" php artisan storage:link
docker exec -it "$APP_CONTAINER" php artisan optimize
```

## Praktik 13 - Membawa Data Lokal ke Server Optional

Data yang sedang ada di MySQL Laragon tidak otomatis ikut ke server karena database lokal tidak masuk Git dan tidak masuk Docker image.

Jika ingin membawa data sekarang, export database lokal:

```bash
mysqldump -u root kilat_hitam > restore.sql
```

Upload/copy file ke VM 1:

```text
/opt/sprintlog/infra/database/restore.sql
```

Set variable:

```yaml
database_restore_enabled: true
database_restore_file: "/opt/sprintlog/infra/database/restore.sql"
database_seed_mode: none
```

Catatan: gunakan `database_seed_mode: none` saat restore dump penuh agar data dari seeder tidak menambah data restore.

## Pengujian Akhir

Lakukan pengujian berikut:

```bash
ansible all -m ping
docker node ls
docker stack services sprintlog
docker stack ps sprintlog
curl -I http://sprintlog.local
nslookup sprintlog.local 127.0.0.1
nslookup mail.sprintlog.local 127.0.0.1
```

Uji melalui browser:

```text
http://sprintlog.local
```

## Troubleshooting

Jika `ansible all -m ping` gagal:

- Periksa IP pada `inventory.ini`.
- Periksa SSH key VM 1 ke VM 2.
- Periksa user SSH, misalnya `ubuntu`.

Jika image gagal di-pull:

- Periksa registry lokal VM 1 pada `controller_ip:5000`.
- Periksa konfigurasi `/etc/docker/daemon.json` pada VM 1 dan VM 2.
- Periksa apakah image sudah dipush ke registry lokal.

Jika data lama tidak muncul:

- Pastikan `database_restore_enabled: true`.
- Pastikan file `/opt/sprintlog/infra/database/restore.sql` ada di VM 1.
- Pastikan dump dibuat dari database lokal `kilat_hitam`.
- Pastikan `database_seed_mode: none` jika memakai dump penuh.

Jika DNS tidak resolve:

- Periksa status `bind9`.
- Periksa file zone pada `/etc/bind/db.sprintlog.local`.
- Jalankan `nslookup sprintlog.local 127.0.0.1`.

Jika mail server gagal:

- Pastikan `enable_mail: true`.
- Periksa status `postfix`.
- Periksa status `dovecot`.
- Periksa DNS record `mail.sprintlog.local`.
- Pastikan user `user1` dan `user2` sudah punya folder `~/Maildir`.
- Periksa log mail pada `/var/log/mail.log`.

Jika app belum bisa diakses:

- Periksa service `sprintlog_lb`.
- Periksa replica `sprintlog_app`.
- Periksa log Nginx dan app container.

```bash
docker service logs sprintlog_lb
docker service logs sprintlog_app
```

## Checklist Bukti Praktik

- [ ] Repository SprintLog sudah tersedia di GitHub.
- [ ] VM 1 dapat SSH ke VM 2 tanpa password.
- [ ] Inventory Ansible sudah sesuai IP VM.
- [ ] `ansible all -m ping` berhasil.
- [ ] Docker terinstall pada VM 1 dan VM 2.
- [ ] Registry lokal VM 1 aktif pada port 5000.
- [ ] Docker Swarm terdiri dari 1 manager dan 1 worker.
- [ ] Image Apache SprintLog berhasil dibuild dan dipush otomatis.
- [ ] Stack SprintLog berhasil di-deploy.
- [ ] Service `app` memiliki 2 replica.
- [ ] Akun login deploy dibuat sesuai variable.
- [ ] Data lokal diimport jika memakai `database_restore_enabled: true`.
- [ ] Nginx load balancer dapat diakses dari browser.
- [ ] DNS `sprintlog.local` resolve ke VM 1.
- [ ] Mail server optional aktif jika `enable_mail: true`.
- [ ] Migrasi Laravel berhasil.
- [ ] Aplikasi SprintLog dapat dibuka melalui domain lab.

## Latihan

1. Jelaskan fungsi VM 1 sebagai controller dan Swarm manager.
2. Jelaskan perbedaan service `lb`, `app`, `worker`, dan `db`.
3. Mengapa aplikasi disimpan di GitHub, bukan dipindahkan menggunakan file sharing?
4. Mengapa service aplikasi menggunakan Apache, sedangkan load balancer menggunakan Nginx?
5. Apa fungsi Docker volume `sprintlog_db` dan `sprintlog_storage`?
6. Mengapa registry lokal diperlukan pada skenario tanpa Docker Hub?
7. Apa risiko memakai local volume untuk upload file pada Docker Swarm?
8. Apa fungsi MX record pada DNS mail server?
9. Apa yang terjadi jika `enable_dhcp` diaktifkan pada jaringan yang sudah memiliki DHCP server lain?
