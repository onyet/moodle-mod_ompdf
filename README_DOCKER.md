# Custom Automated Moodle Testing Environment for `mod_ompdf`

Dokumen ini menjelaskan cara menjalankan lingkungan Docker Moodle 4.4+ berbasis **PHP 8.2 Apache**, **MariaDB 10.11**, dan **Automated Moodle Installer** untuk pengujian plugin `mod_ompdf`.

---

## 🛠️ Cara Menjalankan

### 1. Build & Jalankan Container
Jalankan perintah berikut di terminal:

```bash
docker compose up -d --build
```

Script entrypoint container akan secara otomatis:
1. Menghasilkan berkas Moodle **`config.php`**.
2. Menunggu koneksi MariaDB siap.
3. Menginisialisasi database Moodle secara otomatis (*CLI automated DB installer*).
4. Mengaktifkan web server Apache.

### 2. Akses Moodle Test Site
Setelah container siap (sekitar 1-2 menit pada boot pertama), buka browser:

- **URL Utama Moodle**: [http://localhost:8080](http://localhost:8080)
- **URL Plugin OMPDF**: [http://localhost:8080/mod/ompdf/index.php](http://localhost:8080/mod/ompdf/index.php)
- **Admin Username**: `admin`
- **Admin Password**: `Admin12345!`

---

## 📁 Struktur Volume Mount

Plugin `mod_ompdf` pada folder komputer Anda di-mount secara otomatis ke dalam Moodle pada path:
`/var/www/html/mod/ompdf`

Setiap editan kode pada komputer lokal akan langsung teruji di Moodle tanpa perlu rebuild container.

---

## 🛑 Cara Menghentikan

- **Menghentikan Container**:
  ```bash
  docker compose down
  ```
