<?php require 'lib.php';auth(['admin']);
$dari=$_GET['dari']??date('Y-m-01');$sampai=$_GET['sampai']??date('Y-m-d');$p=$_GET['periode']??'harian';
$G=['harian'=>'DATE(tanggal_masuk)','mingguan'=>'YEARWEEK(tanggal_masuk,1)','bulanan'=>'DATE_FORMAT(tanggal_masuk,"%Y-%m")'];if(!isset($G[$p]))$p='harian';
$rows=q("SELECT MIN(tanggal_masuk) awal,MAX(tanggal_masuk) akhir,COUNT(*) jml,SUM(berat) kg,SUM(total_harga) total FROM transaksi WHERE tanggal_masuk BETWEEN ? AND ? GROUP BY {$G[$p]} ORDER BY awal DESC",[$dari,$sampai])->fetchAll();
$sum=array_sum(array_column($rows,'total'));
top('Laporan pendapatan','laporan');?>
<form class="card search" style="margin-bottom:18px"><label>Dari<input type="date" name="dari" value="<?=e($dari)?>"></label><label>Sampai<input type="date" name="sampai" value="<?=e($sampai)?>"></label><label>Periode<select name="periode"><?php foreach($G as $k=>$_)echo '<option'.($k===$p?' selected':'').'>'.$k.'</option>'?></select></label><button class="btn">Tampilkan</button><button type="button" class="btn ghost" onclick="print()">Cetak</button></form>
<section class="card"><div class="total">Total pendapatan <b><?=rp($sum)?></b></div><div class="tw"><table><tr><th>Periode</th><th>Transaksi</th><th>Berat</th><th>Pendapatan</th></tr>
<?php foreach($rows as $r){$lb=$p==='harian'?tgl($r['awal']):($p==='mingguan'?tgl($r['awal']).' – '.tgl($r['akhir']):date('m/Y',strtotime($r['awal'])));echo '<tr><td>'.$lb.'</td><td>'.$r['jml'].'</td><td>'.($r['kg']+0).' kg</td><td><b>'.rp($r['total']).'</b></td></tr>';}
if(!$rows)echo '<tr><td colspan="4" class="muted">Tidak ada transaksi pada rentang ini. Ubah tanggal untuk melihat periode lain.</td></tr>'?></table></div></section>
<?php bottom();
