<?php require 'lib.php';
if(!empty($_SESSION['u']))go('dashboard.php');
$err='';
if(post()){$u=q('SELECT * FROM user WHERE username=?',[trim($_POST['username']??'')])->fetch();
 if($u&&password_verify($_POST['password']??'',$u['password'])){session_regenerate_id(true);$_SESSION['u']=['id'=>$u['id_user'],'username'=>$u['username'],'role'=>$u['role']];go('dashboard.php');}
 $err='Username atau password salah. Periksa lagi lalu coba masuk.';}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Masuk – LaundryCrafty</title><link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="login"><section class="hero"><div class="porthole" aria-hidden="true"><i></i><i></i><i></i></div><h2>Cucian rapi, catatan juga.</h2><p>Kelola pelanggan, transaksi, dan pendapatan laundry dari satu tempat.</p></section>
<form method="post" class="card loginbox"><h1>Masuk</h1><?=field()?><?php if($err)echo '<div class="flash err">'.e($err).'</div>'?>
<label>Username<input name="username" required autofocus autocomplete="username"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="btn">Masuk ke LaundryCrafty</button><p class="muted">Pelanggan? <a href="cek.php">Cek status cucian</a></p></form></body></html>
