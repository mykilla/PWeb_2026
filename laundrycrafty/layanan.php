<?php require 'lib.php';auth(['admin']);
if(post()){$a=$_POST['action']??'';
 if($a==='save'){$id=(int)$_POST['id'];$n=trim($_POST['nama_layanan']);$h=(int)$_POST['harga_per_kg'];
  if($n===''||$h<=0){flash('Isi nama layanan dan harga per kg lebih dari 0.');go('layanan.php');}
  if($id)q('UPDATE layanan SET nama_layanan=?,harga_per_kg=? WHERE id_layanan=?',[$n,$h,$id]);else q('INSERT INTO layanan(nama_layanan,harga_per_kg) VALUES(?,?)',[$n,$h]);flash('Layanan disimpan.');go('layanan.php');}
 if($a==='del'){try{q('DELETE FROM layanan WHERE id_layanan=?',[(int)$_POST['id']]);flash('Layanan dihapus.');}catch(PDOException $x){flash('Layanan ini sudah dipakai transaksi, jadi tidak bisa dihapus.');}go('layanan.php');}}
$ed=isset($_GET['edit'])?q('SELECT * FROM layanan WHERE id_layanan=?',[(int)$_GET['edit']])->fetch():null;
$rows=q('SELECT * FROM layanan ORDER BY harga_per_kg')->fetchAll();
top('Layanan','layanan');?>
<div class="grid2 side-form"><form method="post" class="card" data-validate><h3><?=$ed?'Ubah layanan':'Tambah layanan'?></h3><?=field()?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=e($ed['id_layanan']??0)?>">
<label>Nama layanan<input name="nama_layanan" required maxlength="50" value="<?=e($ed['nama_layanan']??'')?>"></label><label>Harga per kg (Rp)<input type="number" name="harga_per_kg" required min="1" value="<?=e($ed['harga_per_kg']??'')?>"></label><button class="btn">Simpan layanan</button><?php if($ed)echo ' <a class="btn ghost" href="layanan.php">Batal</a>'?></form>
<section class="card"><div class="tw"><table><tr><th>Layanan</th><th>Harga / kg</th><th></th></tr><?php foreach($rows as $r):?><tr><td><?=e($r['nama_layanan'])?></td><td><?=rp($r['harga_per_kg'])?></td><td class="act"><a href="?edit=<?=$r['id_layanan']?>">Ubah</a><form method="post"><?=field()?><input type="hidden" name="action" value="del"><input type="hidden" name="id" value="<?=$r['id_layanan']?>"><button class="lnk danger" data-confirm="Hapus layanan <?=e($r['nama_layanan'])?>?">Hapus</button></form></td></tr><?php endforeach?></table></div></section></div>
<?php bottom();
