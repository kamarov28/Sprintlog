# SprintLog 2 VM Infrastructure Runbook

Runbook ini mengikuti update terbaru: hanya ada 2 VM Ubuntu.

Versi jobsheet untuk kebutuhan praktik/laporan tersedia di [jobsheet-sprintlog-2vm-deployment.md](jobsheet-sprintlog-2vm-deployment.md).

## Topologi

| VM | Contoh IP | Role | Isi |
| --- | --- | --- | --- |
| VM 1 | `192.168.56.10` | Controller + Swarm manager + load balancer | Ansible, Git, Docker, DNS/DHCP/mail optional, Nginx load balancer, DB volume |
| VM 2 | `192.168.56.20` | Managed node + Swarm worker | Docker, replica aplikasi |

Domain lab:

```text
sprintlog.local -> 192.168.56.10
admin.sprintlog.local -> 192.168.56.10
mail.sprintlog.local -> 192.168.56.10
```

## Alur Besar

1. Push source SprintLog ke GitHub.
2. VM 1 install Git + Ansible.
3. VM 1 SSH ke VM 2 memakai key.
4. Jalankan Ansible dari VM 1.
5. Ansible install package, Docker, DNS/DHCP optional.
6. Ansible init Docker Swarm di VM 1 dan join VM 2.
7. VM 1 clone repo dari GitHub.
8. VM 1 membuat registry lokal Docker pada port 5000.
9. VM 1 build image Apache dari repo hasil clone dan push ke registry lokal.
10. Docker stack deploy aplikasi Apache dengan Nginx load balancer.

## VCS

Di laptop/dev machine:

```bash
git init
git add .
git commit -m "Prepare SprintLog deployment"
git branch -M main
git remote add origin https://github.com/USERNAME/sprintlog.git
git push -u origin main
```

Di VM 1 nanti Ansible akan clone dari `app_repo_url` pada `infra/ansible/group_vars/all.yml`.

## Build dan Push Image

Pakai image Apache untuk service aplikasi karena satu container sudah membawa web server + PHP. Load balancing tetap memakai Nginx pada service `lb`.

Pada skenario terbaru, build dan push image dilakukan otomatis oleh Ansible. VM 1 akan menjalankan registry lokal:

```text
192.168.56.10:5000
```

Playbook akan melakukan:

- `git clone` atau update repo dari GitHub.
- `docker build` image Apache dari `Dockerfile.apache`.
- `docker push` image ke registry lokal VM 1.
- Deploy stack memakai image dari registry lokal tersebut.

## Setup VM 1

```bash
sudo apt update
sudo apt install -y git ansible openssh-client
ssh-keygen
ssh-copy-id ubuntu@192.168.56.20
```

Clone repo:

```bash
git clone https://github.com/USERNAME/sprintlog.git
cd sprintlog/infra/ansible
cp inventory.example.ini inventory.ini
```

Edit:

- `inventory.ini`: IP dan user SSH.
- `group_vars/all.yml`: domain, IP, repo URL, dan password DB.

`APP_KEY` boleh dikosongkan untuk lab karena playbook akan membuatnya otomatis.

Akun deploy dapat diatur di `group_vars/all.yml`:

```yaml
database_seed_mode: accounts
deploy_admin_email: "admin@sprintlog.com"
deploy_admin_password: "password"
deploy_cashier_email: "kasir@sprintlog.com"
deploy_cashier_password: "password"
deploy_courier_email: "kurir@sprintlog.com"
deploy_courier_password: "password"
```

Tes koneksi:

```bash
ansible all -m ping
```

Jalankan:

```bash
ansible-playbook site.yml
```

## Docker Stack

Stack ada di:

```text
infra/docker/docker-stack.two-vm.yml
```

Kalau deploy manual dari VM 1, jalankan dari folder stack supaya path config Nginx load balancer terbaca konsisten:

```bash
cd /opt/sprintlog/infra/docker
set -a
. ./.env
set +a
docker stack deploy -c docker-stack.two-vm.yml sprintlog
```

Service:

- `lb`: Nginx load balancer port 80 di VM 1.
- `app`: 2 replica Laravel Apache, tersebar max 1 per node.
- `worker`: Laravel queue worker.
- `db`: MySQL 8.4, ditempatkan di VM 1.
- `sprintlog-registry`: registry lokal standalone pada VM 1 untuk image hasil build.

Docker network custom:

```text
sprintlog_net
```

Docker volumes:

```text
sprintlog_db
sprintlog_storage
sprintlog_registry
```

Catatan penting: `sprintlog_storage` pada Swarm default adalah local volume per node. Untuk production beneran, gunakan NFS/shared storage/object storage supaya upload file konsisten antar replica.

## Database dan Data Existing

Database production di server dibuat oleh Docker stack melalui service `db` (`mysql:8.4`) dan volume `sprintlog_db`.

Playbook default akan:

- Menunggu database aktif.
- Menjalankan migration.
- Membuat akun deploy jika `database_seed_mode: accounts`.

Mode seed:

```yaml
database_seed_mode: accounts # buat akun login saja
database_seed_mode: demo     # jalankan DatabaseSeeder penuh
database_seed_mode: none     # tidak seed data
```

Data lokal dari Laragon tidak otomatis ikut ke server. Jika ingin membawa data sekarang, export database lokal:

```bash
mysqldump -u root kilat_hitam > restore.sql
```

Upload/copy `restore.sql` ke VM 1:

```text
/opt/sprintlog/infra/database/restore.sql
```

Lalu set:

```yaml
database_restore_enabled: true
database_restore_file: "/opt/sprintlog/infra/database/restore.sql"
database_seed_mode: none
```

Catatan: gunakan dump dari database `kilat_hitam` tanpa opsi `--databases`, supaya isi dump masuk ke database container `sprintlog`.

## DNS dan DHCP

Ansible menyiapkan template:

```text
infra/ansible/templates/db.sprintlog.local.j2
infra/ansible/templates/dhcpd.conf.j2
```

DNS default aktif:

```yaml
enable_dns: true
```

DHCP default mati:

```yaml
enable_dhcp: false
```

Aktifkan DHCP hanya jika VM 1 adalah satu-satunya DHCP server pada network lab.

## Mail Server Optional

Mail server disiapkan sebagai service optional di VM 1 mengikuti jobsheet Mail Server Sederhana: Postfix sebagai MTA, Dovecot sebagai MDA/IMAP, dan Mutt sebagai MUA terminal. Default mati supaya SMTP/IMAP tidak aktif tanpa kebutuhan.

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

Jika jobsheet/demo meminta mail server:

```yaml
enable_mail: true
```

Ansible akan:

- Install `postfix` dan `mailutils`.
- Install `dovecot-core`, `dovecot-imapd`, dan `mutt`.
- Mengatur `/etc/mailname`.
- Mengatur `/etc/postfix/main.cf`.
- Mengatur Dovecot `mail_location = maildir:~/Maildir`.
- Mengatur Mutt pada `/etc/skel/.muttrc`.
- Membuat user lab `user1` dan `user2`.
- Membuat folder `~/Maildir/{cur,new,tmp,Sent,Drafts}`.
- Menambahkan DNS `mail.sprintlog.local` dan MX record.
- Mengarahkan Laravel mail host ke `mail.sprintlog.local:25`.

Verifikasi:

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

## Verifikasi

Di VM 1:

```bash
ansible all -m ping
docker node ls
docker stack services sprintlog
docker stack ps sprintlog
curl -I http://sprintlog.local
nslookup sprintlog.local 127.0.0.1
nslookup mail.sprintlog.local 127.0.0.1
```

Migrasi dijalankan otomatis oleh Ansible setelah service `db` dan `app` hidup. Jika ingin menjalankan ulang secara manual:

```bash
APP_CONTAINER=$(docker ps --filter "name=sprintlog_app" --format "{{.ID}}" | head -n 1)
docker exec -it "$APP_CONTAINER" php artisan migrate --force
docker exec -it "$APP_CONTAINER" php artisan storage:link
docker exec -it "$APP_CONTAINER" php artisan optimize
```

## Checklist Bukti

- [ ] GitHub repo bisa di-clone dari VM 1.
- [ ] VM 1 bisa SSH ke VM 2.
- [ ] `ansible all -m ping` sukses.
- [ ] Docker terinstall di VM 1 dan VM 2.
- [ ] Registry lokal `192.168.56.10:5000` aktif.
- [ ] Image Apache berhasil dibuild otomatis dari VM 1.
- [ ] Akun deploy dibuat sesuai `group_vars/all.yml`.
- [ ] Data existing diimport jika `database_restore_enabled: true`.
- [ ] `docker node ls` menampilkan 2 node.
- [ ] Stack `sprintlog` aktif.
- [ ] Service `app` punya 2 replica.
- [ ] Nginx/LB membuka `http://sprintlog.local`.
- [ ] DNS resolve ke VM 1.
- [ ] Mail server optional aktif jika `enable_mail: true`.
- [ ] Migration Laravel sukses.
- [ ] Queue worker running.

## Yang Perlu Diganti Saat Demo

- IP VM di `inventory.ini` dan `group_vars/all.yml`.
- `app_repo_url`.
- `DB_PASSWORD` dan `DB_ROOT_PASSWORD`.
- Hostname manager jika bukan `vm1`.
