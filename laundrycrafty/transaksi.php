<?php require 'lib.php';auth();
const ST=['Proses','Selesai','Sudah Diambil'];
if(post()){$a=$_POST['action']??'';
 if($a==='add'){[$ok,$r]=buat_trx((int)$_POST['id_pelanggan'],(int)$_POST['id_layanan'],$_POST['berat'],$_POST['tanggal_masuk'],$_POST['tanggal_selesai']);flash($ok?"Transaksi #$r dicatat.":$r);go('transaksi.php');}
 if($a==='status'&&in_array($_POST['status'],ST,true)){q('UPDATE transaksi SET status=? WHERE id_transaksi=?',[$_POST['status'],(int)$_POST['id']]);flash('Status cucian diperbarui.');go('transaksi.php');}
 if($a==='del'){auth(['admin']);q('DELETE FROM transaksi WHERE id_transaksi=?',[(int)$_POST['id']]);flash('Transaksi dihapus.');go('transaksi.php');}}
$s=trim($_GET['q']??'');$tg=$_GET['tgl']??'';$w='1';$ar=[];
if($s!==''){$w.=' AND p.nama LIKE ?';$ar[]="%$s%";}if($tg!==''){$w.=' AND t.tanggal_masuk=?';$ar[]=$tg;}
$rows=q("SELECT t.*,p.nama,l.nama_layanan FROM transaksi t JOIN pelanggan p USING(id_pelanggan) JOIN layanan l USING(id_layanan) WHERE $w ORDER BY t.id_transaksi DESC LIMIT 100",$ar)->fetchAll();
$pl=q('SELECT id_pelanggan,nama FROM pelanggan ORDER BY nama')->fetchAll();$ly=q('SELECT * FROM layanan ORDER BY harga_per_kg')->fetchAll();
top('Transaksi','transaksi');?>
<div class="grid2 side-form"><form method="post" class="card" data-validate><h3>Transaksi baru</h3><?=field()?><input type="hidden" name="action" value="add">
<label>Pelanggan<select name="id_pelanggan" required><option value="">Pilih pelanggan</option><?php foreach($pl as $p)echo '<option value="'.$p['id_pelanggan'].'">'.e($p['nama']).'</option>'?></select></label>
<small class="muted">Belum terdaftar? <a href="pelanggan.php">Tambah pelanggan</a></small>
<label>Layanan<select name="id_layanan" id="layanan" required><option value="">Pilih layanan</option><?php foreach($ly as $l)echo '<option value="'.$l['id_layanan'].'" data-harga="'.$l['harga_per_kg'].'">'.e($l['nama_layanan']).' – '.rp($l['harga_per_kg']).'/kg</option>'?></select></label>
<label>Berat (kg)<input type="number" name="berat" id="berat" step="0.1" min="0.1" max="100" required></label>
<div class="two"><label>Tanggal masuk<input type="date" name="tanggal_masuk" value="<?=date('Y-m-d')?>" required></label><label>Tanggal selesai<input type="date" name="tanggal_selesai" value="<?=date('Y-m-d',strtotime('+2 day'))?>" required></label></div>
<div class="total">Total <b id="total">Rp 0</b></div><button class="btn">Simpan transaksi</button></form>
<section class="card"><form class="search"><input name="q" placeholder="Nama pelanggan" value="<?=e($s)?>"><input type="date" name="tgl" value="<?=e($tg)?>"><button class="btn">Cari</button></form>
<div class="tw"><table><tr><th>#</th><th>Pelanggan</th><th>Layanan</th><th>Berat</th><th>Total</th><th>Selesai</th><th>Status</th><th></th></tr>
<?php foreach($rows as $r):?><tr><td><?=$r['id_transaksi']?></td><td><?=e($r['nama'])?><br><small class="muted"><?=tgl($r['tanggal_masuk'])?></small></td><td><?=e($r['nama_layanan'])?></td><td><?=$r['berat']+0?> kg</td><td><b><?=rp($r['total_harga'])?></b></td><td><?=tgl($r['tanggal_selesai'])?></td>
<td><form method="post"><?=field()?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?=$r['id_transaksi']?>"><select name="status" class="st s-<?=strtolower(str_replace(' ','',$r['status']))?>" data-autosubmit><?php foreach(ST as $x)echo '<option'.($x===$r['status']?' selected':'').'>'.$x.'</option>'?></select></form></td>
<td class="act"><a href="nota.php?id=<?=$r['id_transaksi']?>" target="_blank">Nota</a><?php if($_SESSION['u']['role']==='admin'):?><form method="post"><?=field()?><input type="hidden" name="action" value="del"><input type="hidden" name="id" value="<?=$r['id_transaksi']?>"><button class="lnk danger" data-confirm="Hapus transaksi #<?=$r['id_transaksi']?>?">Hapus</button></form><?php endif?></td></tr>
<?php endforeach;if(!$rows)echo '<tr><td colspan="8" class="muted">Tidak ada transaksi yang cocok.</td></tr>'?></table></div></section></div>
<?php bottom();
