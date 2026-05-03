# SprintLog Ansible Lab Guide

Panduan ini dipakai untuk deploy SprintLog di skenario lab/ujian dua VM menggunakan Ansible, Docker Swarm, Nginx load balancer, DNS/DHCP opsional, mail server opsional, dan HTTPS self-signed.

## 1. Status modular playbook

Playbook `site.yml` sudah dibuat modular dengan tag, sehingga tidak harus menjalankan semua task dari awal setiap kali troubleshooting.

| Modul | Tag | Fungsi |
|---|---|---|
| Base | `base` | Install paket dasar, SSH, dan fallback host record di `/etc/hosts`. |
| Support services | `support`, `dns`, `dhcp`, `mail` | Install dan konfigurasi Bind9, DHCP server, dan mail server lokal. |
| Docker | `docker` | Install Docker dan konfigurasi daemon untuk registry lokal. |
| Swarm + Registry | `swarm`, `registry` | Init Docker Swarm di VM1, buat registry lokal, dan join VM2 sebagai worker. |
| Deploy app | `deploy` | Clone/update repo, build image, push ke registry lokal, deploy stack, migrate, seed, dan start worker. |

## 2. Persiapan manual VM1 dan VM2

Beberapa hal tetap perlu dilakukan manual karena Ansible belum bisa mengelola VM sebelum IP dan SSH siap.

### Set hostname

Di VM1:

```bash
hostnamectl set-hostname vm1-controller
```

Di VM2:

```bash
hostnamectl set-hostname vm2-managed
```

### Set IP static sesuai soal

Contoh skenario:

```txt
VM1 internal/controller : 192.168.10.20
VM2 internal/managed    : 192.168.10.30
VM1 host-only/web DNS   : 10.10.10.20
```

Pastikan VM1 bisa ping VM2:

```bash
ping 192.168.10.30
```

### Setup SSH key dari VM1 ke VM2

Jalankan dari VM1:

```bash
ssh-keygen
ssh-copy-id root@192.168.10.30
ssh root@192.168.10.30 hostname
```

Kalau `ssh root@192.168.10.30 hostname` berhasil tanpa password, VM2 siap dikelola Ansible.

## 3. Edit konfigurasi utama

File utama:

```bash
nano /root/Sprintlog/infra/ansible/group_vars/all.yml
```

Bagian yang biasanya perlu disesuaikan dengan soal:

```yaml
lab_domain: sprintlog.site
controller_ip: 192.168.10.20
managed_ip: 192.168.10.30
swarm_advertise_addr: "{{ controller_ip }}"

app_repo_url: "https://github.com/kamarov28/Sprintlog.git"
app_url: "https://{{ lab_domain }}"

dns_forward_ip: "10.10.10.20"
dns_reverse_enabled: true
dns_reverse_zone: "10.168.192.in-addr.arpa"
dns_reverse_zone_file: /etc/bind/db.192.168.10
dns_controller_ptr: "20"
dns_managed_ptr: "30"
```

### Catatan DNS reverse

Untuk network `192.168.10.0/24`, reverse zone yang benar adalah:

```yaml
dns_reverse_zone: "10.168.192.in-addr.arpa"
```

PTR-nya cukup angka host:

```yaml
dns_controller_ptr: "20"
dns_managed_ptr: "30"
```

Artinya:

```txt
192.168.10.20 -> VM1/controller
192.168.10.30 -> VM2/managed
```

## 4. DHCP opsional

Kalau DHCP diminta soal:

```yaml
enable_dhcp: true
dhcp_interface: nama_interface_yang_benar
dhcp_subnet: 10.10.10.0
dhcp_netmask: 255.255.255.0
dhcp_range_start: 10.10.10.100
dhcp_range_end: 10.10.10.200
dhcp_router: 10.10.10.1
dhcp_dns_servers:
  - "10.10.10.20"
```

Kalau DHCP tidak diminta:

```yaml
enable_dhcp: false
```

## 5. Cek inventory

File inventory:

```bash
nano /root/Sprintlog/infra/ansible/inventory.ini
```

Contoh:

```ini
[controller]
vm1 ansible_host=192.168.10.20 ansible_user=root

[swarm_manager]
vm1 ansible_host=192.168.10.20 ansible_user=root

[swarm_workers]
vm2 ansible_host=192.168.10.30 ansible_user=root

[docker_hosts]
vm1 ansible_host=192.168.10.20 ansible_user=root
vm2 ansible_host=192.168.10.30 ansible_user=root
```

Tes koneksi Ansible:

```bash
cd /root/Sprintlog/infra/ansible
ansible all -i inventory.ini -m ping
```

Targetnya semua host mengembalikan `pong`.

## 6. Menjalankan playbook secara modular

Masuk folder Ansible:

```bash
cd /root/Sprintlog/infra/ansible
```

Full deploy dari awal:

```bash
ansible-playbook -i inventory.ini site.yml
```

Jalankan modul tertentu saja:

```bash
ansible-playbook -i inventory.ini site.yml --tags base
ansible-playbook -i inventory.ini site.yml --tags dns
ansible-playbook -i inventory.ini site.yml --tags dhcp
ansible-playbook -i inventory.ini site.yml --tags mail
ansible-playbook -i inventory.ini site.yml --tags docker
ansible-playbook -i inventory.ini site.yml --tags swarm
ansible-playbook -i inventory.ini site.yml --tags registry
ansible-playbook -i inventory.ini site.yml --tags deploy
```

Contoh penggunaan:

- DNS salah: jalankan `--tags dns`.
- DHCP salah: jalankan `--tags dhcp`.
- App berubah di GitHub: jalankan `--tags deploy`.
- Docker/Swarm bermasalah: jalankan `--tags docker` lalu `--tags swarm`.

## 7. Checklist testing

### DNS

```bash
nslookup sprintlog.site 10.10.10.20
nslookup www.sprintlog.site 10.10.10.20
nslookup 192.168.10.20 10.10.10.20
nslookup 192.168.10.30 10.10.10.20
```

Target:

```txt
sprintlog.site -> 10.10.10.20
www.sprintlog.site -> 10.10.10.20
192.168.10.20 -> record VM1/controller
192.168.10.30 -> record VM2/managed
```

Kalau VM1 sendiri tidak bisa resolve domain, tapi DNS server bisa, gunakan sementara:

```bash
curl -k --resolve sprintlog.site:443:10.10.10.20 -I https://sprintlog.site
```

### Docker Swarm

```bash
docker node ls
docker service ls
```

Target service:

```txt
sprintlog_app      2/2
sprintlog_db       1/1
sprintlog_lb       1/1
sprintlog_worker   1/1
```

### Web HTTP/HTTPS

```bash
curl -I http://sprintlog.site
curl -k -I https://sprintlog.site
```

Target:

```txt
HTTP/1.1 200 OK
```

### HTTPS session/cookie

```bash
curl -k -I https://sprintlog.site/login | grep -i set-cookie
```

Target cookie harus punya `secure`:

```txt
XSRF-TOKEN=...; secure; samesite=lax
sprintlog-session=...; secure; httponly; samesite=lax
```

Kalau cookie belum `secure`, cek container app:

```bash
docker ps --format "table {{.ID}}\t{{.Names}}" | grep sprintlog_app
docker exec -it <APP_CONTAINER_ID> sh -lc 'printenv | grep -E "SESSION|TRUSTED|APP_URL"'
```

Target:

```env
APP_URL=https://sprintlog.site
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SECURE=true
SESSION_SAME_SITE=lax
TRUSTED_PROXIES=*
```

## 8. Flow paket yang perlu dites

Untuk memastikan flow paket aman, tes urutan ini:

1. Customer membuat pickup/order.
2. Manager assign courier.
3. Courier confirm picked up.
4. Cashier atau manager receive at hub dan verify payment.
5. Manager activate shipment.
6. Manager dispatch shipment / hub scan.
7. Courier update delivery status.
8. Tracking publik menampilkan status terbaru.

Kalau semua form POST tidak 419 dan status berubah, flow paket aman.

## 9. Catatan CSRF Laravel

Laravel butuh `@csrf` untuk form yang mengubah data:

```blade
<form method="POST" action="/login">
    @csrf
</form>
```

Wajib untuk:

```txt
POST
PUT
PATCH
DELETE
```

Tidak wajib untuk GET biasa.

Jika CSRF/session bermasalah, biasanya muncul:

```txt
419 Page Expired
```

Penyebab umum di setup HTTPS:

- Form POST tidak punya `@csrf`.
- Cookie lama dari HTTP masih tersimpan.
- `SESSION_SECURE_COOKIE` belum masuk container.
- URL Laravel masih generate HTTP saat halaman dibuka lewat HTTPS.
- App container belum redeploy setelah perubahan.

## 10. Jawaban singkat untuk pengawas

### Kenapa Ansible dibuat modular?

Playbook dibuat modular dengan tag seperti `base`, `dns`, `dhcp`, `mail`, `docker`, `swarm`, `registry`, dan `deploy`. Jadi kalau soal hanya meminta DNS, cukup jalankan `--tags dns`. Kalau hanya update aplikasi, cukup jalankan `--tags deploy` tanpa mengulang konfigurasi VM dari awal. Ini juga memudahkan troubleshooting karena tiap bagian bisa diuji terpisah.

### Apa yang masih manual di VM2?

VM2 tetap perlu konfigurasi awal manual seperti hostname, IP static, dan SSH access dari VM1. Setelah VM2 bisa diakses oleh Ansible, sisanya otomatis: Docker diinstall, daemon registry diset, VM2 join ke Docker Swarm sebagai worker, lalu replica aplikasi dijalankan di VM2.

### Apa efek kalau modul dihapus?

- `base` dihapus: paket dasar dan SSH setup bisa kurang, task berikutnya berpotensi gagal.
- `dns` dihapus: domain lokal tidak bisa resolve lewat Bind9.
- `dhcp` dihapus: client harus setting IP/DNS manual.
- `mail` dihapus: fitur uji mail lokal tidak tersedia.
- `docker` dihapus: container tidak bisa jalan.
- `swarm` dihapus: VM2 tidak join cluster, deploy dua node gagal.
- `registry` dihapus: VM2 tidak bisa pull image lokal dari VM1.
- `deploy` dihapus: aplikasi Laravel, database, Nginx LB, migration, seed, dan worker tidak dideploy.
