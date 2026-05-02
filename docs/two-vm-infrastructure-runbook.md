# SprintLog 2 VM Infrastructure Runbook

Runbook ini mengikuti update terbaru: hanya ada 2 VM Ubuntu.

## Topologi

| VM | Contoh IP | Role | Isi |
| --- | --- | --- | --- |
| VM 1 | `192.168.56.10` | Controller + Swarm manager + load balancer | Ansible, Git, Docker, DNS/DHCP optional, Nginx load balancer, DB volume |
| VM 2 | `192.168.56.20` | Managed node + Swarm worker | Docker, replica aplikasi |

Domain lab:

```text
sprintlog.local -> 192.168.56.10
admin.sprintlog.local -> 192.168.56.10
```

## Alur Besar

1. Push source SprintLog ke GitHub.
2. VM 1 install Git + Ansible.
3. VM 1 SSH ke VM 2 memakai key.
4. Jalankan Ansible dari VM 1.
5. Ansible install package, Docker, DNS/DHCP optional.
6. Ansible init Docker Swarm di VM 1 dan join VM 2.
7. VM 1 clone repo dari GitHub.
8. Docker stack deploy aplikasi Apache dengan Nginx load balancer.

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

```bash
docker build -t USERNAME/sprintlog-apache:latest -f Dockerfile.apache .
docker login
docker push USERNAME/sprintlog-apache:latest
```

Set `sprintlog_image` di:

```text
infra/ansible/group_vars/all.yml
```

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
- `group_vars/all.yml`: domain, IP, repo URL, image, `APP_KEY`, password DB.

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

Docker network custom:

```text
sprintlog_net
```

Docker volumes:

```text
sprintlog_db
sprintlog_storage
```

Catatan penting: `sprintlog_storage` pada Swarm default adalah local volume per node. Untuk production beneran, gunakan NFS/shared storage/object storage supaya upload file konsisten antar replica.

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

## Verifikasi

Di VM 1:

```bash
ansible all -m ping
docker node ls
docker stack services sprintlog
docker stack ps sprintlog
curl -I http://sprintlog.local
nslookup sprintlog.local 127.0.0.1
```

Jalankan migrasi setelah app hidup:

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
- [ ] `docker node ls` menampilkan 2 node.
- [ ] Stack `sprintlog` aktif.
- [ ] Service `app` punya 2 replica.
- [ ] Nginx/LB membuka `http://sprintlog.local`.
- [ ] DNS resolve ke VM 1.
- [ ] Migration Laravel sukses.
- [ ] Queue worker running.

## Yang Perlu Diganti Saat Demo

- IP VM di `inventory.ini` dan `group_vars/all.yml`.
- `app_repo_url`.
- `sprintlog_image`.
- `APP_KEY`.
- `DB_PASSWORD` dan `DB_ROOT_PASSWORD`.
- Hostname manager jika bukan `vm1`.
