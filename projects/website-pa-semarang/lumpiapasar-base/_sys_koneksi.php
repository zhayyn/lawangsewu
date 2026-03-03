<?php
class Database 
{
	//edit di sini bro
    protected $host="localhost";
    protected $user="admin";
    protected $db="lumpiapasar";
    protected $pass="R4h4514@";


  protected $db_sekunder="lumpiapasar";
	//edit di sini bro
    protected $koneksi;
 
    public function __construct() 
	{
        $this->koneksi = new PDO("mysql:host=".$this->host.";dbname=".$this->db,$this->user,$this->pass);
        $this->koneksi_sekunder = new PDO("mysql:host=".$this->host.";dbname=".$this->db_sekunder,$this->user,$this->pass);
    }
}

class Tampil_sekunder extends Database 
{ 
    public function tampil_data_sekunder($sql) 
    { 
        $q = $this->koneksi_sekunder->prepare($sql);
        $q->execute();
        return $row = $q->fetchAll();
    } 
     
    public function jumlah_data_sekunder($sql) 
    { 
        $q = $this->koneksi_sekunder->prepare($sql);
        $q->execute();
        return $row = $q->rowCount();
    }
}    
 
class Tampil extends Database 
{ 
    public function tampil_data($sql) 
	{ 
        $q = $this->koneksi->prepare($sql);
        $q->execute();
        return $row = $q->fetchAll();
    } 
	 
	public function jumlah_data($sql) 
	{ 
        $q = $this->koneksi->prepare($sql);
        $q->execute();
        return $row = $q->rowCount();
    }
	
}
class Hapus extends Database 
{ 
    public function hapus_data($id, $kolom, $nama_table) 
    { 
        $sql="DELETE FROM $nama_table WHERE $kolom=$id";
        $q = $this->koneksi->prepare($sql);
        $proses=$q->execute();
        if($proses)
        {
            $pesan='Hapus data berhasil';
        }else
        {
            $pesan='Hapus data gagal'.implode(":",$this->koneksi->errorInfo());
        }
        return $pesan;
    } 
    
}
class Hapus_sekunder extends Database 
{ 
    public function hapus_data_sekunder($id, $kolom, $nama_table) 
    { 
        $sql="DELETE FROM $nama_table WHERE $kolom=$id";
        $q = $this->koneksi_sekunder->prepare($sql);
        $proses=$q->execute();
        if($proses)
        {
            $pesan='Hapus data berhasil';
        }else
        {
            $pesan='Hapus data gagal'.implode(":",$this->koneksi->errorInfo());
        }
        return $pesan;
    } 
    public function hapus_data_kolom($id, $kolom,$id1, $kolom1, $nama_table) 
	{ 
		$sql="DELETE FROM $nama_table WHERE $kolom='$id'  AND $kolom1='$id1'  ";
        $q = $this->koneksi_sekunder->prepare($sql);
        $proses=$q->execute();
		if($proses)
		{
			$pesan='Hapus data berhasil';
		}else
		{
			$pesan='Hapus data gagal'.implode(":",$this->koneksi->errorInfo());
		}
        return $pesan;
    } 
	
}
class Kosongkan extends Database 
{ 
    public function hapus($nama_table) 
	{ 
		$sql="TRUNCATE TABLE $nama_table";
        $q = $this->koneksi->prepare($sql);
        $proses=$q->execute();
		if($proses)
		{
			$pesan='Hapus data berhasil';
		}else
		{
			$pesan='Hapus data gagal'.implode(":",$this->koneksi->errorInfo());
		}
        return $pesan;
    } 
	
}
class Edit extends Database 
{ 
    public function edit_data($nama_tabel, $kolom, $id, $a) 
	{
    $fields=array_keys($a);
    $values=array_values($a);
    $fieldlist=implode(',', $fields); 
    $qs=str_repeat("?,",count($fields)-1);
    $firstfield = true;

    $sql = "UPDATE `".$nama_tabel."` SET";
    for ($i = 0; $i < count($fields); $i++) {
        if(!$firstfield) {
        $sql .= ", ";   
        }
        $sql .= " ".$fields[$i]."=?";
        $firstfield = false;
    }
    $sql .= " WHERE $kolom =?";
	//echo "---".$sql."---";
    $sth = $this->koneksi->prepare($sql);
    $values[] = $id;
    return $sth->execute($values);
	}
 
	
}
class Tambah extends Database 
{ 
    public function tambah_data($table, $array) 
	{ 
		$fields=array_keys($array);
		$values=array_values($array);
		$fieldlist=implode(',', $fields); 
		$qs=str_repeat("?,",count($fields)-1);

		$sql="INSERT INTO `".$table."` (".$fieldlist.") VALUES (${qs}?)";
       // echo $sql;
		$q = $this->koneksi->prepare($sql);
		return $q->execute($values);
    } 
	
}


class Tambah_sekunder extends Database 
{ 
    public function tambah_data_sekunder($table, $array) 
    { 
        $fields=array_keys($array);
        $values=array_values($array);
        $fieldlist=implode(',', $fields); 
        $qs=str_repeat("?,",count($fields)-1);

        $sql="INSERT INTO `".$table."` (".$fieldlist.") VALUES (${qs}?)";
        //echo $sql;
        $q = $this->koneksi_sekunder->prepare($sql);
        return $q->execute($values);
    } 
    
}
class Edit_sekunder extends Database 
{ 
    public function edit_data_sekunder($nama_tabel, $kolom, $id, $a) 
    {
    $fields=array_keys($a);
    $values=array_values($a);
    $fieldlist=implode(',', $fields); 
    $qs=str_repeat("?,",count($fields)-1);
    $firstfield = true;

    $sql = "UPDATE `".$nama_tabel."` SET";
    for ($i = 0; $i < count($fields); $i++) {
        if(!$firstfield) {
        $sql .= ", ";   
        }
        $sql .= " ".$fields[$i]."=?";
        $firstfield = false;
    }
    $sql .= " WHERE $kolom =?";
    //echo "---".$sql."---";
    $sth = $this->koneksi_sekunder->prepare($sql);
    $values[] = $id;
    return $sth->execute($values);
    }
 
    
}
?>