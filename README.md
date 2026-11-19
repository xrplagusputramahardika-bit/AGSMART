# AGSmart POS - Sistem Kasir Toko Modern & Lengkap (Dengan Sistem Tap Kartu Member)

Aplikasi kasir berbasis web (*Point of Sale*) modern, responsif, dan siap pakai menggunakan **Bootstrap 5.3** dan **Bootstrap Icons** yang dirancang untuk toko, minimarket, atau retail dengan basis data MySQL `db_tokokita`.

---

## 🚀 Fitur Unggulan

### 1. 💳 Sistem Tap Kartu Member (RFID / NFC / Barcode) - BARU!
- **Dukungan Pembaca Kartu (Hardware Reader)**:
  - Kompatibel dengan alat pembaca kartu RFID USB, NFC Reader, atau Scanner Barcode kartu (bekerja layaknya input keyboard otomatis + Enter).
- **Animasi Gelombang Contactless**:
  - Modal tap kartu dilengkapi animasi radar / gelombang nirkabel interaktif saat kartu ditempelkan.
- **Efek Suara Chime Khusus**:
  - Suara nada elektronik *chime tri-tone* yang berbunyi saat kartu member berhasil di-tap.
- **Pintasan Keyboard**:
  - Tekan tombol <kbd>F3</kbd> di kasir untuk langsung membuka jendela *Tap Kartu Member*.
- **Level / Tier Loyalitas Member**:
  - **Platinum**: Diskon otomatis 10%
  - **Gold**: Diskon otomatis 5%
  - **Silver**: Diskon otomatis 2%
  - **Regular**: Diskon kustom / akumulasi poin
- **Pemberian Poin Otomatis**:
  - Setiap pembelanjaan kelipatan Rp 10.000 otomatis mendapatkan 1 poin member.
- **Dompet Digital / Saldo Kartu Member**:
  - Pelanggan dapat mengisi saldo kartu (*Top-up*).
  - Kasir dapat memilih metode pembayaran **"Saldo Member"** untuk memotong saldo kartu secara instan saat berbelanja.
- **Cetak Kartu Member Fisik/Digital**:
  - Halaman manajemen pelanggan menyediakan pratinjau kartu member ukuran standar kartu ATM/PVC dengan chip emas dan barcode yang dapat dicetak.
- **Tombol Simulasi Demo**:
  - Disediakan tombol coba cepat untuk menguji kartu member contoh (Andi - Platinum, Budi - Gold, Citra - Silver) tanpa memerlukan alat fisik.

---

### 2. 🛒 Kasir Toko Modern (POS Interaktif)
- **Desain Modern Bootstrap 5**:
  - Menggunakan Bootstrap 5.3 + Bootstrap Icons dengan antarmuka *Dark Glassmorphism* yang elegan, bersih, dan nyaman di mata kasir.
- **Katalog Produk Dinamis**:
  - Menampilkan produk langsung dari database lengkap dengan nama, kategori, harga, dan badge stok real-time (*Aman*, *Menipis*, atau *Habis*).
- **Pencarian Kilat & Scanner Barcode**:
  - Pencarian instan dan pemindaian barcode barang dengan suara *beep* kasir.
- **Pintasan Keyboard (*Keyboard Shortcuts*)**:
  - <kbd>F2</kbd>: Fokus ke kolom cari barang / scan barcode
  - <kbd>F3</kbd>: Buka modal *Tap Kartu Member*
  - <kbd>F4</kbd>: Buka modal pembayaran (*Checkout*)
  - <kbd>F8</kbd>: Kosongkan keranjang belanja
  - <kbd>Esc</kbd>: Menutup modal
- **Keranjang Belanja Cerdas**:
  - Penyesuaian kuantitas (+/-), validasi batas stok, diskon member otomatis, dan opsi PPN 11%.
- **Metode Pembayaran**:
  - Tunai (*Cash*), Saldo Member (Tap), QRIS (QR dinamis), Transfer Bank, dan Debit.
- **Kalkulator Kembalian**:
  - Tombol nominal cepat (*Uang Pas*, *20.000*, *50.000*, *100.000*, *200.000*) dengan kalkulasi kembalian otomatis.

---

### 3. 🧾 Cetak Struk Pembayaran (Thermal Receipt)
- **Pratinjau Kertas Thermal Realistis**:
  - Tampilan struk kertas lengkap dengan logo toko, kontak, nomor faktur, tanggal/jam, kasir, rincian barang, total, bayar, kembalian, **informasi kartu member & poin yang didapat**, serta barcode transaksi.
- **Ukuran Kertas 58mm & 80mm**:
  - Saklar ukuran kertas untuk printer thermal mini maupun standar.
- **Cetak Langsung (@media print)**:
  - Diformat khusus agar hanya mencetak struk bersih tanpa header/footer browser.
- **Cetak Ulang Transaksi**:
  - Menu *Riwayat Transaksi* memungkinkan cetak ulang struk lama kapan saja.

---

### 4. 💾 Backup & Restore Database
- **1-Klik Download Backup (.sql)**:
  - Mengunduh seluruh struktur dan data database menjadi file `.sql` bertanggal (`db_tokokita_backup_YYYY-MM-DD.sql`).
- **Simpan Backup ke Server**:
  - Menyimpan file cadangan langsung di folder server `backups/`.
- **Restore Database**:
  - Unggah file `.sql` untuk memulihkan seluruh database secara instan tanpa membuka phpMyAdmin.
- **Reset Pabrik**:
  - Mengembalikan database ke konfigurasi awal lengkap (26 produk contoh & 10 pelanggan bawaan).

---

### 5. 📦 Manajemen Produk & Pemantauan Stok
- Tambah, Edit, Hapus produk dengan kode barcode/SKU unik.
- Penyesuaian stok kilat (*Quick Stock*) langsung dari tabel.
- Peringatan stok menipis (*Low Stock Alert*) dan stok habis (*Out of Stock*).
- Filter berdasarkan kategori dan status stok.

---

### 6. 👥 Manajemen Pelanggan & Member
- Terhubung langsung dengan view `v_total_belanja_pelanggan`.
- Melihat akumulasi belanja, total order, poin, dan saldo member.
- Form top-up saldo kartu member.
- Desain kartu member digital yang dapat dicetak.

---

### 7. 📊 Laporan & Analitik Penjualan
- Statistik omset, estimasi profit kotor, dan nilai rata-rata belanja (*AOV*).
- Top 7 produk paling laris (*Best Sellers*).
- Analisis kontribusi metode pembayaran (Tunai, QRIS, Saldo Member, Transfer, Debit).
- Ekspor laporan transaksi ke format **Excel / CSV**.

---

### 8. ⚙️ Pengaturan Toko & Kustomisasi Struk
- Mengubah nama toko, slogan, alamat, nomor telepon, dan email.
- Mengatur ucapan terima kasih dan catatan kaki struk dengan fitur **Live Preview**.

---

## 🗄️ Pembaruan Database (`db_tokokita`)

Tabel `tbl_pelanggan` telah ditingkatkan dengan kolom sistem kartu member:
- `no_kartu` (Nomor kartu RFID/Barcode unik, contoh: `MBR-88012301`)
- `tipe_member` (`Silver`, `Gold`, `Platinum`, `Regular`)
- `diskon_member` (Besaran diskon otomatis dalam persen)
- `poin` (Total poin akumulasi belanja)
- `saldo` (Saldo dompet kartu yang dapat digunakan untuk belanja)
- `status_kartu` (`aktif`, `nonaktif`, `blokir`)

Tabel `tbl_transaksi` telah ditambahkan kolom `poin_didapat` untuk mencatat poin yang dihasilkan dari setiap struk transaksi.

---

## 💻 Cara Menjalankan Aplikasi

1. **Pastikan Apache & MySQL di XAMPP sudah Aktif** (`Start` Apache & MySQL di XAMPP Control Panel).
2. Buka browser dan jalankan migrasi database (1-Klik):

## Pembayaran Online Duitku

Konfigurasi lokal disimpan di `.env` (diabaikan Git dan diblokir dari akses HTTP). Salin `.env.example` sebagai `.env` jika file konfigurasi belum tersedia, lalu isi Merchant Code dan API Key yang berasal dari proyek Duitku yang sama.

- Gunakan `DUITKU_ENV=sandbox` untuk pengujian. Ganti menjadi `production` hanya setelah Merchant Code dan API Key produksi terverifikasi.
- `DUITKU_PUBLIC_BASE_URL` harus memakai HTTPS dan dapat diakses dari internet. Untuk XAMPP dengan ngrok yang meneruskan port 80, URL aplikasi biasanya berakhiran `/agsmart`; pastikan URL callback `.../agsmart/api/duitku_callback.php` dapat dibuka dari internet.
- Jika alamat ngrok berubah, perbarui `DUITKU_PUBLIC_BASE_URL` di `.env` dan mulai ulang checkout.
- Metode pembayaran yang ditampilkan mengikuti kanal aktif di akun Duitku. Pembayaran diverifikasi dengan callback bertanda tangan dan pengecekan status ke Duitku; stok yang dipesan dilepas setelah status kedaluwarsa atau pembatalan dikonfirmasi.
- Jangan memasukkan `.env` ke Git atau membagikan API Key. API Key yang pernah dibagikan di percakapan sebaiknya dirotasi sebelum memakai mode produksi.