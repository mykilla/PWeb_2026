<?php
session_start();
const DBC=['localhost','laundrycrafty','root','']; // host, nama db, user, password (XAMPP default)
function db(){static $p;return $p??=new PDO('mysql:host='.DBC[0].';dbname='.DBC[1].';charset=utf8mb4',DBC[2],DBC[3],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}
function q($s,$a=[]){$st=db()->prepare($s);$st->execute($a);return $st;}
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function rp($n){return 'Rp '.number_format((float)$n,0,',','.');}
function tgl($d){return $d?date('d/m/Y',strtotime($d)):'-';}
function go($u){header("Location: $u");exit;}
function csrf(){return $_SESSION['t']??=bin2hex(random_bytes(16));}
function field(){return '<input type="hidden" name="_t" value="'.csrf().'">';}
function post(){if($_SERVER['REQUEST_METHOD']!=='POST')return false;if(!hash_equals(csrf(),$_POST['_t']??''))die('Token tidak valid');return true;}
function auth($roles=null){if(empty($_SESSION['u']))go('login.php');if($roles&&!in_array($_SESSION['u']['role'],$roles)){http_response_code(403);die('Akses ditolak untuk peran Anda.');}}
function flash($m=null){if($m!==null){$_SESSION['f']=$m;return;}$f=$_SESSION['f']??'';unset($_SESSION['f']);return $f;}
function badge($s){return '<span class="badge s-'.strtolower(str_replace(' ','',$s)).'">'.e($s).'</span>';}
function buat_trx($p,$l,$b,$m,$s){
 $b=(float)$b;$svc=q('SELECT harga_per_kg FROM layanan WHERE id_layanan=?',[$l])->fetch();
 if(!$svc||!q('SELECT 1 FROM pelanggan WHERE id_pelanggan=?',[$p])->fetch())return [false,'Pelanggan atau layanan tidak valid.'];
 if($b<=0||$b>100)return [false,'Berat harus antara 0,1 dan 100 kg.'];
 if(!strtotime($m)||!strtotime($s)||$s<$m)return [false,'Tanggal selesai tidak boleh sebelum tanggal masuk.'];
 q('INSERT INTO transaksi(id_pelanggan,id_layanan,tanggal_masuk,tanggal_selesai,berat,total_harga) VALUES(?,?,?,?,?,?)',[$p,$l,$m,$s,$b,round($b*$svc['harga_per_kg'])]);
 return [true,db()->lastInsertId()];
}
function top($t,$act=''){
 $u=$_SESSION['u'];$f=flash();
 $m=['dashboard'=>['Dashboard',['admin','kasir']],'transaksi'=>['Transaksi',['admin','kasir']],'pelanggan'=>['Pelanggan',['admin','kasir']],'layanan'=>['Layanan',['admin']],'laporan'=>['Laporan',['admin']]];
 echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($t).' – LaundryCrafty</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/css/style.css"></head><body><div class="app"><aside class="side" id="side"><a class="brand" href="dashboard.php"><span class="bub"></span>LaundryCrafty</a><nav>';
 foreach($m as $k=>[$l,$r])if(in_array($u['role'],$r))echo '<a href="'.$k.'.php" class="'.($act===$k?'on':'').'">'.$l.'</a>';
 echo '<a href="cek.php" target="_blank">Cek status pelanggan</a></nav><div class="who"><b>'.e($u['username']).'</b><small>'.e($u['role']).'</small><a href="logout.php">Keluar</a></div></aside><main><header class="bar"><button class="burger" id="burger" aria-label="Buka menu">☰</button><h1>'.e($t).'</h1></header>';
 if($f)echo '<div class="flash" role="status">'.e($f).'</div>';
}
function bottom(){echo '</main></div><script src="assets/js/app.js"></script></body></html>';}
