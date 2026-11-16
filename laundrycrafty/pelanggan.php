<?php require 'lib.php';auth();
if(post()){$a=$_POST['action']??'';
 if($a==='save'){$id=(int)($_POST['id']??0);$n=trim($_POST['nama']);$al=trim($_POST['alamat']);$hp=trim($_POST['no_hp']);
  if($n===''||!preg_match('/^\+?[0-9]{8,15}$/',$hp)){flash('Isi nama dan nomor HP (8–15 digit angka).');go('pelanggan.php'.($id?"?edit=$id":''));}
  if($id)q('UPDATE pelanggan SET nama=?,alamat=?,no_hp=? WHERE id_pelanggan=?',[$n,$al,$hp,$id]);else q('INSERT INTO pelanggan(nama,alamat,no_hp) VALUES(?,?,?)',[$n,$al,$hp]);
  flash('Data pelanggan disimpan.');go('pelanggan.php');}
 if($a==='del'){auth(['admin']);try{q('DELETE FROM pelanggan WHERE id_pelanggan=?',[(int)$_POST['id']]);flash('Pelanggan dihapus.');}catch(PDOException $x){flash('Pelanggan ini masih punya transaksi, jadi tidak bisa dihapus.');}go('pelanggan.php');}}
$ed=isset($_GET['edit'])?q('SELECT * FROM pelanggan WHERE id_pelanggan=?',[(int)$_GET['edit']])->fetch():null;
$s=trim($_GET['q']??'');$rows=q('SELECT * FROM pelanggan WHERE nama LIKE ? OR no_hp LIKE ? ORDER BY nama',["%$s%","%$s%"])->fetchAll();
top('Pelanggan','pelanggan');?>
<div class="grid2 side-form"><form method="post" class="card" data-validate><h3><?=$ed?'Ubah pelanggan':'Tambah pelanggan'?></h3><?=field()?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=e($ed['id_pelanggan']??0)?>">
<label>Nama<input name="nama" required maxlength="100" value="<?=e($ed['nama']??'')?>"></label><label>No. HP<input name="no_hp" required inputmode="tel" pattern="\+?[0-9]{8,15}" value="<?=e($ed['no_hp']??'')?>"></label><label>Alamat<textarea name="alamat" rows="3"><?=e($ed['alamat']??'')?></textarea></label>
<button class="btn">Simpan pelanggan</button><?php if($ed)echo ' <a class="btn ghost" href="pelanggan.php">Batal</a>'?></form>
<section class="card"><form class="search"><input name="q" placeholder="Cari nama atau no. HP" value="<?=e($s)?>"><button class="btn">Cari</button></form>
<div class="tw"><table><tr><th>Nama</th><th>No. HP</th><th>Alamat</th><th></th></tr><?php foreach($rows as $r):?><tr><td><?=e($r['nama'])?></td><td><?=e($r['no_hp'])?></td><td><?=e($r['alamat'])?></td><td class="act"><a href="?edit=<?=$r['id_pelanggan']?>">Ubah</a><?php if($_SESSION['u']['role']==='admin'):?><form method="post"><?=field()?><input type="hidden" name="action" value="del"><input type="hidden" name="id" value="<?=$r['id_pelanggan']?>"><button class="lnk danger" data-confirm="Hapus pelanggan <?=e($r['nama'])?>?">Hapus</button></form><?php endif?></td></tr><?php endforeach;if(!$rows)echo '<tr><td colspan="4" class="muted">Belum ada pelanggan. Tambahkan lewat formulir di samping.</td></tr>'?></table></div></section></div>
<?php bottom();
