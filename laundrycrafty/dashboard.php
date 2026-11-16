<?php require 'lib.php';auth();
$n=fn($s)=>q($s)->fetchColumn();
$st=['pel'=>$n('SELECT COUNT(*) FROM pelanggan'),'trx'=>$n('SELECT COUNT(*) FROM transaksi'),'hari'=>$n('SELECT COALESCE(SUM(total_harga),0) FROM transaksi WHERE tanggal_masuk=CURDATE()'),'bulan'=>$n('SELECT COALESCE(SUM(total_harga),0) FROM transaksi WHERE DATE_FORMAT(tanggal_masuk,"%Y-%m")=DATE_FORMAT(CURDATE(),"%Y-%m")')];
$sc=q('SELECT status,COUNT(*) c FROM transaksi GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
$d=q('SELECT tanggal_masuk d,SUM(total_harga) s FROM transaksi WHERE tanggal_masuk>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY d')->fetchAll(PDO::FETCH_KEY_PAIR);
$lab=$rev=[];for($i=6;$i>=0;$i--){$k=date('Y-m-d',strtotime("-$i day"));$lab[]=date('d M',strtotime($k));$rev[]=(int)($d[$k]??0);}
$rec=q('SELECT t.*,p.nama FROM transaksi t JOIN pelanggan p USING(id_pelanggan) ORDER BY id_transaksi DESC LIMIT 5')->fetchAll();
top('Dashboard','dashboard');?>
<div class="stats"><div class="stat hl"><small>Pendapatan hari ini</small><b><?=rp($st['hari'])?></b></div><div class="stat"><small>Pendapatan bulan ini</small><b><?=rp($st['bulan'])?></b></div><div class="stat"><small>Pelanggan</small><b><?=$st['pel']?></b></div><div class="stat"><small>Transaksi</small><b><?=$st['trx']?></b></div></div>
<div class="grid2"><section class="card"><h3>Pendapatan 7 hari terakhir</h3><canvas id="ch" height="130"></canvas></section>
<section class="card"><h3>Status cucian</h3><div class="pills"><?php foreach(['Proses','Selesai','Sudah Diambil'] as $s)echo '<div>'.badge($s).'<b>'.($sc[$s]??0).'</b></div>'?></div><h3>Transaksi terbaru</h3><ul class="list"><?php foreach($rec as $r)echo '<li><span>#'.$r['id_transaksi'].' '.e($r['nama']).'</span><b>'.rp($r['total_harga']).'</b></li>'?></ul><a class="btn" href="transaksi.php">Catat transaksi baru</a></section></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>new Chart(document.getElementById('ch'),{type:'bar',data:{labels:<?=json_encode($lab)?>,datasets:[{data:<?=json_encode($rev)?>,backgroundColor:'#22B8C7',borderRadius:8}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{callback:v=>'Rp '+v.toLocaleString('id-ID')}}}}});</script>
<?php bottom();
