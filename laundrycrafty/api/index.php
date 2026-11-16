<?php require __DIR__.'/../lib.php';header('Content-Type: application/json; charset=utf-8');
function out($d,$c=200){http_response_code($c);echo json_encode($d,JSON_UNESCAPED_UNICODE);exit;}
if(empty($_SESSION['u']))out(['error'=>'Login diperlukan. Masuk lewat login.php lebih dulu.'],401);
$m=$_SERVER['REQUEST_METHOD'];$r=explode('/',trim($_GET['r']??'','/'));$id=(int)($r[1]??0);
if($m!=='GET'&&stripos($_SERVER['CONTENT_TYPE']??'','application/json')!==0)out(['error'=>'Content-Type harus application/json'],415);
$b=json_decode(file_get_contents('php://input'),true)?:[];
$sel='SELECT t.*,p.nama,l.nama_layanan FROM transaksi t JOIN pelanggan p USING(id_pelanggan) JOIN layanan l USING(id_layanan)';
if($r[0]==='pelanggan'&&$m==='GET')out(q('SELECT * FROM pelanggan ORDER BY nama')->fetchAll());
if($r[0]==='transaksi'){
 if($m==='POST'){[$ok,$x]=buat_trx((int)($b['id_pelanggan']??0),(int)($b['id_layanan']??0),$b['berat']??0,$b['tanggal_masuk']??date('Y-m-d'),$b['tanggal_selesai']??date('Y-m-d',strtotime('+2 day')));$ok?out(q("$sel WHERE id_transaksi=?",[$x])->fetch(),201):out(['error'=>$x],422);}
 if($m==='GET'&&$id){$t=q("$sel WHERE id_transaksi=?",[$id])->fetch();$t?out($t):out(['error'=>'Tidak ditemukan'],404);}
 if($m==='PUT'&&$id){if(!in_array($b['status']??'',['Proses','Selesai','Sudah Diambil'],true))out(['error'=>'Status tidak valid'],422);q('UPDATE transaksi SET status=? WHERE id_transaksi=?',[$b['status'],$id]);out(q("$sel WHERE id_transaksi=?",[$id])->fetch()?:['error'=>'Tidak ditemukan']);}}
if($r[0]==='laporan'&&$m==='GET'){if($_SESSION['u']['role']!=='admin')out(['error'=>'Khusus admin'],403);
 $f=['hari'=>'DATE(tanggal_masuk)','minggu'=>'YEARWEEK(tanggal_masuk,1)','bulan'=>'DATE_FORMAT(tanggal_masuk,"%Y-%m")'][$_GET['periode']??'bulan']??null;if(!$f)out(['error'=>'periode: hari|minggu|bulan'],422);
 out(q("SELECT MIN(tanggal_masuk) awal,MAX(tanggal_masuk) akhir,COUNT(*) transaksi,SUM(berat) kg,SUM(total_harga) pendapatan FROM transaksi GROUP BY $f ORDER BY awal DESC")->fetchAll());}
out(['error'=>'Endpoint tidak ditemukan'],404);
