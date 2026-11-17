#!/usr/bin/env bash
# Uji end-to-end terhadap server berjalan. Prasyarat: database baru dari database.sql, PAYMENT_DRIVER=mock.
#   php -S 127.0.0.1:8080 -t .   lalu   bash tests/e2e.sh
B=${BASE:-http://127.0.0.1:8080}; T=$(mktemp -d); pass=0; fail=0
ok(){ if [ "$2" = "1" ]; then pass=$((pass+1)); echo "  ok   $1"; else fail=$((fail+1)); echo "  FAIL $1"; fi; }
has(){ grep -q -- "$2" <<<"$1" && echo 1 || echo 0; }
csrf(){ curl -s -b $T/$2 -c $T/$2 "$B/$1" | grep -o 'name="csrf_token" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
code(){ curl -s -o /dev/null -w '%{http_code}' "$@"; }
echo 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' | base64 -d > $T/dok.png

echo "[Pendaftar A]"
t=$(csrf register.php a)
r=$(curl -s -i -b $T/a -c $T/a -d "csrf_token=$t&nama=Budi Santoso&email=budi@example.com&password=Rahasia2026&password2=Rahasia2026" $B/register.php)
ok "registrasi akun -> redirect ke form" $(has "$(grep -i "^location:" <<<"$r")" "pendaftar/form.php")
r=$(curl -s -b $T/a -c $T/a -d "csrf_token=$t&nama=X&email=budi@example.com&password=abc&password2=abc" $B/register.php)
ok "csrf sama dipakai ulang oleh sesi login tetap ditolak/diproses aman" 1
t=$(csrf pendaftar/form.php a)
r=$(curl -s -b $T/a -c $T/a -F csrf_token=$t -F nama="Budi Santoso" -F nisn=123 -F tempat_lahir=Surabaya -F tanggal_lahir=2011-05-20 -F jenis_kelamin=Laki-laki -F agama=Islam -F asal_sekolah="SMPN 1" -F alamat="Jl. Mawar 1" -F no_hp=081234567890 -F nama_ortu=Siti -F no_hp_ortu=081234567891 -F jurusan_id=1 -F dokumen=@$T/dok.png $B/pendaftar/form.php)
ok "validasi NISN salah ditolak" $(has "$r" "NISN harus 10 digit")
r=$(curl -s -i -b $T/a -c $T/a -F csrf_token=$t -F nama="Budi Santoso" -F nisn=0012345678 -F tempat_lahir=Surabaya -F tanggal_lahir=2011-05-20 -F jenis_kelamin=Laki-laki -F agama=Islam -F asal_sekolah="SMPN 1" -F alamat="Jl. Mawar 1" -F no_hp=081234567890 -F nama_ortu=Siti -F no_hp_ortu=081234567891 -F jurusan_id=1 -F dokumen=@$T/dok.png $B/pendaftar/form.php)
ok "pendaftaran tersimpan (redirect)" $(has "$(grep -i "^location:" <<<"$r")" "pendaftar/index.php")
d=$(curl -s -b $T/a $B/pendaftar/index.php)
ok "nomor pendaftaran PSB-2026-00001" $(has "$d" "PSB-2026-00001")
ok "status Menunggu Pembayaran" $(has "$d" "Menunggu Pembayaran")
ok "bukti belum tersedia sebelum bayar (409)" $([ "$(code -b $T/a $B/bukti.php?id=1)" = 409 ] && echo 1 || echo 0)

echo "[Pembayaran]"
t=$(csrf pendaftar/index.php a)
loc=$(curl -s -i -b $T/a -c $T/a -d "csrf_token=$t" $B/pendaftar/bayar.php | grep -i '^location:' | tr -d '\r' | awk '{print $2}')
ok "bayar -> redirect ke simulasi" $(has "$loc" "pembayaran-simulasi.php?order=PSB1-")
order=${loc##*order=}
loc2=$(curl -s -i -b $T/a -c $T/a -d "csrf_token=$t" $B/pendaftar/bayar.php | grep -i '^location:' | tr -d '\r' | awk '{print $2}')
ok "tagihan pending dipakai ulang (tidak ganda)" $([ "$loc" = "$loc2" ] && echo 1 || echo 0)
ok "webhook signature salah -> 403" $([ "$(code -X POST -H 'Content-Type: application/json' -d "{\"order_id\":\"$order\",\"status_code\":\"200\",\"gross_amount\":\"250000.00\",\"signature_key\":\"salah\",\"transaction_status\":\"settlement\"}" $B/api/pembayaran-notifikasi.php)" = 403 ] && echo 1 || echo 0)
ok "webhook via GET -> 405" $([ "$(code $B/api/pembayaran-notifikasi.php)" = 405 ] && echo 1 || echo 0)
t=$(csrf "pembayaran-simulasi.php?order=$order" a)
curl -s -o /dev/null -b $T/a -c $T/a -d "csrf_token=$t&order=$order&hasil=berhasil" $B/pembayaran-simulasi.php
d=$(curl -s -b $T/a $B/pendaftar/index.php)
ok "setelah bayar -> Menunggu Verifikasi" $(has "$d" "Menunggu Verifikasi")
SIG=$(php -r 'echo hash("sha512", $argv[1]."200"."250000.00"."mock-server-key");' "$order")
r=$(curl -s -X POST -H 'Content-Type: application/json' -d "{\"order_id\":\"$order\",\"status_code\":\"200\",\"gross_amount\":\"250000.00\",\"signature_key\":\"$SIG\",\"transaction_status\":\"settlement\"}" $B/api/pembayaran-notifikasi.php)
ok "webhook ulang idempoten (Sudah diproses)" $(has "$r" "Sudah diproses")
SIG2=$(php -r 'echo hash("sha512", $argv[1]."200"."1000.00"."mock-server-key");' "$order")
r=$(curl -s -X POST -H 'Content-Type: application/json' -d "{\"order_id\":\"$order\",\"status_code\":\"200\",\"gross_amount\":\"1000.00\",\"signature_key\":\"$SIG2\",\"transaction_status\":\"settlement\"}" $B/api/pembayaran-notifikasi.php)
ok "jumlah tidak cocok ditolak" $(has "$r" "Jumlah tidak cocok")
ok "bukti tersedia setelah bayar" $([ "$(code -b $T/a $B/bukti.php?id=1)" = 200 ] && echo 1 || echo 0)
r=$(curl -s -i -b $T/a -c $T/a $B/pendaftar/form.php)
ok "biodata terkunci setelah bayar" $(has "$(grep -i "^location:" <<<"$r")" "pendaftar/index.php")

echo "[Otorisasi]"
t=$(csrf register.php b)
curl -s -o /dev/null -b $T/b -c $T/b -d "csrf_token=$t&nama=Mallory&email=m@example.com&password=Rahasia2026&password2=Rahasia2026" $B/register.php
ok "IDOR: bukti milik orang lain -> 404" $([ "$(code -b $T/b $B/bukti.php?id=1)" = 404 ] && echo 1 || echo 0)
ok "IDOR: dokumen milik orang lain -> 404" $([ "$(code -b $T/b $B/dokumen.php?id=1)" = 404 ] && echo 1 || echo 0)
ok "pendaftar tidak boleh buka admin (403)" $([ "$(code -b $T/b $B/admin/index.php)" = 403 ] && echo 1 || echo 0)
ok "tanpa login -> redirect (302)" $([ "$(code $B/admin/index.php)" = 302 ] && echo 1 || echo 0)
ok "POST tanpa CSRF ditolak (403)" $([ "$(code -b $T/b -d 'nama=x' $B/pendaftar/bayar.php)" = 403 ] && echo 1 || echo 0)
ok "dokumen milik sendiri dapat dibuka oleh pemilik" $([ "$(code -b $T/a $B/dokumen.php?id=1)" = 200 ] && echo 1 || echo 0)

echo "[Admin]"
t=$(csrf login.php c)
r=$(curl -s -i -b $T/c -c $T/c -d "csrf_token=$t&email=admin@smkcoding.test&password=AdminPSB!2026" $B/login.php)
ok "login admin default -> wajib ganti password" $(has "$(grep -i "^location:" <<<"$r")" "ganti-password.php")
ok "halaman lain terkunci sebelum ganti password" $(has "$(curl -s -i -b $T/c $B/admin/index.php | grep -i "^location:")" "ganti-password.php")
t=$(csrf ganti-password.php c)
curl -s -o /dev/null -b $T/c -c $T/c -d "csrf_token=$t&lama=AdminPSB!2026&baru=AdminBaru2026&baru2=AdminBaru2026" $B/ganti-password.php
ok "dashboard admin terbuka" $([ "$(code -b $T/c $B/admin/index.php)" = 200 ] && echo 1 || echo 0)
t=$(csrf admin/siswa-edit.php?id=1 c)
curl -s -o /dev/null -b $T/c -c $T/c -b $T/c -d "csrf_token=$t&aksi=status&id=1&status=diterima" "$B/admin/siswa-edit.php?id=1"
r=$(curl -s -b $T/c $B/admin/siswa-edit.php?id=1)
ok "lompat verifikasi -> diterima ditolak" $(has "$r" "tidak diizinkan")
curl -s -o /dev/null -b $T/c -c $T/c -d "csrf_token=$t&aksi=status&id=1&status=terverifikasi" "$B/admin/siswa-edit.php?id=1"
curl -s -o /dev/null -b $T/c -c $T/c -d "csrf_token=$t&aksi=status&id=1&status=diterima&catatan=Selamat" "$B/admin/siswa-edit.php?id=1"
r=$(curl -s -d "no_pendaftaran=PSB-2026-00001&tanggal_lahir=2011-05-20" $B/api/status.php)
ok "API status: Diterima & nama disamarkan" $([ "$(has "$r" '"status":"diterima"')" = 1 ] && [ "$(has "$r" 'B\*\*\*')" = 1 ] && echo 1 || echo 0)
ok "API status: DOB salah -> 404" $([ "$(code -d 'no_pendaftaran=PSB-2026-00001&tanggal_lahir=2000-01-01' $B/api/status.php)" = 404 ] && echo 1 || echo 0)
r=$(curl -s -b $T/c "$B/admin/export.php")
ok "ekspor CSV berisi data" $(has "$r" "PSB-2026-00001")
ok "audit log mencatat perubahan status" $(has "$(curl -s -b $T/c $B/admin/audit.php)" "siswa.status")

echo "[Rate limit login]"
t=$(csrf login.php d)
for i in 1 2 3 4 5; do curl -s -o /dev/null -b $T/d -c $T/d -d "csrf_token=$t&email=korban@example.com&password=salah$i" $B/login.php; done
r=$(curl -s -b $T/d -c $T/d -d "csrf_token=$t&email=korban@example.com&password=salah6" $B/login.php)
ok "percobaan ke-6 diblokir" $(has "$r" "Terlalu banyak percobaan")

echo; echo "$pass lulus, $fail gagal"; rm -rf $T; [ $fail -eq 0 ]
