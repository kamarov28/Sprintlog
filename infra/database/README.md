# Database Restore

Folder ini sengaja tidak menyimpan file `.sql` ke Git.

Jika ingin membawa data lokal ke server:

1. Export database lokal menjadi `restore.sql`.
2. Upload/copy file tersebut ke VM 1 pada:

```text
/opt/sprintlog/infra/database/restore.sql
```

3. Set variable Ansible:

```yaml
database_restore_enabled: true
database_restore_file: "/opt/sprintlog/infra/database/restore.sql"
database_seed_mode: none
```

Gunakan `database_seed_mode: none` saat restore dump penuh supaya data dari seeder tidak menimpa/menambah data restore.
