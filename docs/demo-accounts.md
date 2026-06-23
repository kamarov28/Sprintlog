# SprintLog — Akun Demo / Seeder

> **Password default semua akun di bawah:** `password`  
> Hanya untuk development & staging. Jangan dipakai di production.

---

## Pola akun karyawan per hub

Format email: **`{role}-{hub}@sprintlog.com`**

| Role DB | Slug di email | Contoh |
|---------|---------------|--------|
| manager | `manager` | `manager-jawabarat@sprintlog.com` |
| cashier | `kasir` | `kasir-jawabarat@sprintlog.com` |
| courier | `kurir` | `kurir-jawabarat@sprintlog.com` |

Slug hub = nama provinsi tanpa spasi, lowercase (dari `SprintLog Hub {Provinsi}`).

Login backend: **`/be/login`**

---

## Akun admin (global)

| Email | Role | Password |
|-------|------|----------|
| `admin@sprintlog.com` | admin | `password` |

---

## Akun demo Jakarta (ekstra)

Selain 3 role standar per hub, Jakarta punya kurir truk tambahan:

| Email | Role | Keterangan |
|-------|------|------------|
| `kurirtruk-dkijakarta@sprintlog.com` | courier | Kurir truk (motor: `kurir-dkijakarta@sprintlog.com`) |

---

## Akun pelanggan (frontend)

| Email | Password | Keterangan |
|-------|----------|------------|
| `andi@example.com` | `password` | Pengirim demo (Jakarta) |
| `sinta@example.com` | `password` | Penerima demo (Makassar) |

Login: `/login`

---

## Daftar email per hub

| Hub | Manager | Kasir | Kurir |
|-----|---------|-------|-------|
| Aceh | `manager-aceh@sprintlog.com` | `kasir-aceh@sprintlog.com` | `kurir-aceh@sprintlog.com` |
| Bali | `manager-bali@sprintlog.com` | `kasir-bali@sprintlog.com` | `kurir-bali@sprintlog.com` |
| Banten | `manager-banten@sprintlog.com` | `kasir-banten@sprintlog.com` | `kurir-banten@sprintlog.com` |
| Bengkulu | `manager-bengkulu@sprintlog.com` | `kasir-bengkulu@sprintlog.com` | `kurir-bengkulu@sprintlog.com` |
| DI Yogyakarta | `manager-diyogyakarta@sprintlog.com` | `kasir-diyogyakarta@sprintlog.com` | `kurir-diyogyakarta@sprintlog.com` |
| DKI Jakarta | `manager-dkijakarta@sprintlog.com` | `kasir-dkijakarta@sprintlog.com` | `kurir-dkijakarta@sprintlog.com` |
| Gorontalo | `manager-gorontalo@sprintlog.com` | `kasir-gorontalo@sprintlog.com` | `kurir-gorontalo@sprintlog.com` |
| Jambi | `manager-jambi@sprintlog.com` | `kasir-jambi@sprintlog.com` | `kurir-jambi@sprintlog.com` |
| Jawa Barat | `manager-jawabarat@sprintlog.com` | `kasir-jawabarat@sprintlog.com` | `kurir-jawabarat@sprintlog.com` |
| Jawa Tengah | `manager-jawatengah@sprintlog.com` | `kasir-jawatengah@sprintlog.com` | `kurir-jawatengah@sprintlog.com` |
| Jawa Timur | `manager-jawatimur@sprintlog.com` | `kasir-jawatimur@sprintlog.com` | `kurir-jawatimur@sprintlog.com` |
| Kalimantan Barat | `manager-kalimantanbarat@sprintlog.com` | `kasir-kalimantanbarat@sprintlog.com` | `kurir-kalimantanbarat@sprintlog.com` |
| Kalimantan Selatan | `manager-kalimantanselatan@sprintlog.com` | `kasir-kalimantanselatan@sprintlog.com` | `kurir-kalimantanselatan@sprintlog.com` |
| Kalimantan Tengah | `manager-kalimantantengah@sprintlog.com` | `kasir-kalimantantengah@sprintlog.com` | `kurir-kalimantantengah@sprintlog.com` |
| Kalimantan Timur | `manager-kalimantantimur@sprintlog.com` | `kasir-kalimantantimur@sprintlog.com` | `kurir-kalimantantimur@sprintlog.com` |
| Kalimantan Utara | `manager-kalimantanutara@sprintlog.com` | `kasir-kalimantanutara@sprintlog.com` | `kurir-kalimantanutara@sprintlog.com` |
| Kepulauan Bangka Belitung | `manager-kepulauanbangkabelitung@sprintlog.com` | `kasir-kepulauanbangkabelitung@sprintlog.com` | `kurir-kepulauanbangkabelitung@sprintlog.com` |
| Kepulauan Riau | `manager-kepulauanriau@sprintlog.com` | `kasir-kepulauanriau@sprintlog.com` | `kurir-kepulauanriau@sprintlog.com` |
| Lampung | `manager-lampung@sprintlog.com` | `kasir-lampung@sprintlog.com` | `kurir-lampung@sprintlog.com` |
| Maluku | `manager-maluku@sprintlog.com` | `kasir-maluku@sprintlog.com` | `kurir-maluku@sprintlog.com` |
| Maluku Utara | `manager-malukuutara@sprintlog.com` | `kasir-malukuutara@sprintlog.com` | `kurir-malukuutara@sprintlog.com` |
| Nusa Tenggara Barat | `manager-nusatenggarabarat@sprintlog.com` | `kasir-nusatenggarabarat@sprintlog.com` | `kurir-nusatenggarabarat@sprintlog.com` |
| Nusa Tenggara Timur | `manager-nusatenggaratimur@sprintlog.com` | `kasir-nusatenggaratimur@sprintlog.com` | `kurir-nusatenggaratimur@sprintlog.com` |
| Papua | `manager-papua@sprintlog.com` | `kasir-papua@sprintlog.com` | `kurir-papua@sprintlog.com` |
| Papua Barat | `manager-papuabarat@sprintlog.com` | `kasir-papuabarat@sprintlog.com` | `kurir-papuabarat@sprintlog.com` |
| Papua Barat Daya | `manager-papuabaratdaya@sprintlog.com` | `kasir-papuabaratdaya@sprintlog.com` | `kurir-papuabaratdaya@sprintlog.com` |
| Papua Pegunungan | `manager-papuapegunungan@sprintlog.com` | `kasir-papuapegunungan@sprintlog.com` | `kurir-papuapegunungan@sprintlog.com` |
| Papua Selatan | `manager-papuaselatan@sprintlog.com` | `kasir-papuaselatan@sprintlog.com` | `kurir-papuaselatan@sprintlog.com` |
| Papua Tengah | `manager-papuatengah@sprintlog.com` | `kasir-papuatengah@sprintlog.com` | `kurir-papuatengah@sprintlog.com` |
| Riau | `manager-riau@sprintlog.com` | `kasir-riau@sprintlog.com` | `kurir-riau@sprintlog.com` |
| Sulawesi Barat | `manager-sulawesibarat@sprintlog.com` | `kasir-sulawesibarat@sprintlog.com` | `kurir-sulawesibarat@sprintlog.com` |
| Sulawesi Selatan | `manager-sulawesiselatan@sprintlog.com` | `kasir-sulawesiselatan@sprintlog.com` | `kurir-sulawesiselatan@sprintlog.com` |
| Sulawesi Tengah | `manager-sulawesitengah@sprintlog.com` | `kasir-sulawesitengah@sprintlog.com` | `kurir-sulawesitengah@sprintlog.com` |
| Sulawesi Tenggara | `manager-sulawesitenggara@sprintlog.com` | `kasir-sulawesitenggara@sprintlog.com` | `kurir-sulawesitenggara@sprintlog.com` |
| Sulawesi Utara | `manager-sulawesiutara@sprintlog.com` | `kasir-sulawesiutara@sprintlog.com` | `kurir-sulawesiutara@sprintlog.com` |
| Sumatera Barat | `manager-sumaterabarat@sprintlog.com` | `kasir-sumaterabarat@sprintlog.com` | `kurir-sumaterabarat@sprintlog.com` |
| Sumatera Selatan | `manager-sumateraselatan@sprintlog.com` | `kasir-sumateraselatan@sprintlog.com` | `kurir-sumateraselatan@sprintlog.com` |
| Sumatera Utara | `manager-sumaterautara@sprintlog.com` | `kasir-sumaterautara@sprintlog.com` | `kurir-sumaterautara@sprintlog.com` |

**Password semua:** `password`

---

## Terapkan ke database yang sudah ada

```bash
php artisan db:seed --class=HubCrewSeeder
```

Atau seed penuh:

```bash
php artisan migrate:fresh --seed
```

Logic email ada di `App\Support\HubCrewIdentity`.
