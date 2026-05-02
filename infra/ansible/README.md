# SprintLog Ansible Lab

Folder ini menyiapkan skenario terbaru 2 VM:

- VM 1: Ansible controller, Docker Swarm manager, DNS/DHCP optional, load balancer.
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
- `group_vars/all.yml`: `app_repo_url`, `sprintlog_image`, `APP_KEY`, password database, domain.

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

## Catatan

- DHCP default `false`, aktifkan hanya kalau VM 1 memang satu-satunya DHCP server pada network lab.
- DNS default `true`, domain `sprintlog.local` diarahkan ke VM 1/load balancer.
- Image aplikasi harus sudah dipush ke registry yang bisa di-pull oleh VM 1 dan VM 2.
