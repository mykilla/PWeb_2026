# Dokumentasi API

Basis URL: `https://domain-anda/api/`. Respons JSON UTF-8. Tidak ada CORS (same-origin).

| Endpoint | Metode | Akses | Fungsi |
|---|---|---|---|
| `jurusan.php` | GET | Publik | Daftar jurusan, kuota, sisa kursi, gelombang aktif |
| `status.php` | POST | Publik, rate limit 15/10 menit/IP | Cek status pendaftaran |
| `pembayaran-buat.php` | POST | Sesi pendaftar + header `X-CSRF-Token` | Buat/ambil tagihan |
| `pembayaran-notifikasi.php` | POST | Webhook gateway (signature) | Terima notifikasi pembayaran |

## GET /api/jurusan.php
```json
{"gelombang_aktif":{"nama":"Gelombang 1","tahun_ajaran":"2026/2027","tanggal_selesai":"2027-02-15","biaya_pendaftaran":250000},
 "data":[{"id":1,"kode":"RPL","nama":"Rekayasa Perangkat Lunak","kuota":72,"sisa":72}]}
```

## POST /api/status.php
Body JSON atau form: `{"no_pendaftaran":"PSB-2026-00001","tanggal_lahir":"2011-05-20"}`

| Kode | Arti |
|---|---|
| 200 | `{"no_pendaftaran","nama"(disamarkan),"jurusan","status","status_label","catatan"}` |
| 404 | Data tidak ditemukan |
| 422 | Parameter kurang |
| 429 | Terlalu banyak permintaan |

Nilai `status`: `menunggu_pembayaran`, `menunggu_verifikasi`, `terverifikasi`, `diterima`, `ditolak`.

## POST /api/pembayaran-buat.php
Header: `X-CSRF-Token: <token sesi>`. Respons `201 {"redirect_url":"...","driver":"midtrans"}`.
Tagihan pending < 24 jam dipakai ulang. Error: 401 (belum login), 403 (CSRF), 409 (biodata kosong), 422 (tidak perlu bayar / gateway gagal).

## POST /api/pembayaran-notifikasi.php (webhook)
Format Midtrans HTTP Notification. Pemrosesan:
1. Verifikasi `signature_key = SHA512(order_id + status_code + gross_amount + ServerKey)` — gagal → `403`.
2. Cari `order_id` — tidak ada → `404`.
3. Cocokkan `gross_amount` dengan tagihan — beda → `400`.
4. Idempoten: tagihan yang sudah `paid` dijawab `200 Sudah diproses`.
5. `settlement` / `capture`(fraud `accept`) → `paid` dan status pendaftar menjadi `menunggu_verifikasi`.

Pemetaan status gateway: `settlement`,`capture` → paid; `pending` → pending; `expire` → expired; `cancel` → cancelled; `deny`,`failure` → failed.

Contoh uji lokal (driver mock, kunci `mock-server-key`):
```bash
SIG=$(php -r 'echo hash("sha512","ORDER-ID"."200"."250000.00"."mock-server-key");')
curl -X POST -H 'Content-Type: application/json' \
  -d '{"order_id":"ORDER-ID","status_code":"200","gross_amount":"250000.00","transaction_status":"settlement","signature_key":"'$SIG'"}' \
  http://localhost/pendaftaran-siswa/api/pembayaran-notifikasi.php
```
