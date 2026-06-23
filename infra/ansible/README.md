# SprintLog Ansible Lab

Folder ini menyiapkan skenario terbaru 2 VM:

- VM 1: Ansible controller, Docker Swarm manager, DNS/DHCP/mail optional, load balancer.
- VM 2: Ansible managed node, Docker Swarm worker, aplikasi replica.

## Cara Pakai Cepat

Di VM 1:

```bash
sudo apt update
sudo apt install -y git ansible openssh-client
git clone https://github.com/USERNAME/sprintlog.git
cd sprintlog/infra/ansible
cp inventory.example.ini inventory.ini
```

Edit:

- `inventory.ini`: IP dan user SSH VM.
- `group_vars/all.yml`: `app_repo_url`, IP, domain, dan password database.
- `app_key` boleh dikosongkan untuk lab. Playbook akan membuat `APP_KEY` hanya pada deploy pertama, lalu memakai ulang nilai di `infra/docker/.env` agar session/CSRF tetap valid saat redeploy.
- `database_seed_mode` default `accounts`, jadi location, hub nasional, kurir/truck hub, dan akun deploy dibuat otomatis tanpa reset data.
- `routing_osrm_enabled=true` mengaktifkan estimasi jarak jalan via OSRM. Jika OSRM tidak bisa diakses, SprintLog otomatis fallback ke estimasi lokal berbasis koordinat.

Tes koneksi:

```bash
ansible all -m ping
```

Jalankan semua setup:

```bash
ansible-playbook site.yml
```

Setelah stack deploy:

```bash
docker node ls
docker stack services sprintlog
docker stack ps sprintlog
curl -I http://sprintlog.local
```

Maintenance Laravel yang aman untuk demo:

```bash
ansible-playbook site.yml --tags laravel-maintenance
```

Task ini menjalankan `php artisan optimize:clear`, `php artisan migrate --force`, `storage:link`, `php artisan optimize`, lalu seed sesuai `database_seed_mode`. Default `accounts` hanya memastikan akun deploy tersedia; gunakan `-e database_seed_mode=demo` hanya jika memang ingin mengisi ulang data demo.

Jika sesi demo harus distabilkan cepat sebelum investigasi multi-replica selesai:

```bash
docker service scale sprintlog_app=1
```

## Catatan

- DHCP default `false`, aktifkan hanya kalau VM 1 memang satu-satunya DHCP server pada network lab.
- DNS default `true`, domain `sprintlog.local` diarahkan ke VM 1/load balancer.
- Mail server default `false`, aktifkan `enable_mail: true` hanya jika jobsheet meminta Postfix/Dovecot/Mutt.
- VM 1 otomatis menjalankan registry lokal pada `controller_ip:5000`.
- Playbook otomatis build image Apache dari repo hasil clone, push ke registry lokal, lalu deploy stack.
- Data lokal tidak otomatis ikut. Jika ingin import data sekarang, export SQL ke `infra/database/restore.sql`, set `database_restore_enabled: true`, dan ubah `database_seed_mode: none`.
