document.addEventListener("DOMContentLoaded", function() {
  //	data('panjar_jenis_pendaftaran');
});
function pilih_jenis_pendaftaran(id){	
	var xhr = new XMLHttpRequest(); 
    xhr.open("POST", '_admin_data_isi_tabel_komponen_biaya', true); 
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() 
    { 
        if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) 
        {
			document.getElementById("combo_jenis_pendaftaran").innerHTML="<option value=''>Pilih Jenis Perkara</option>"+xhr.responseText;

        }
    }
    xhr.send("tabel="+tabel+"&kunci=id&isi="+id);
}
function buka_modal_jenis_biaya(jenis_pendaftaran_id)
{
  document.getElementById("modal_jenis_biaya").style.display="block";
  document.getElementById("jenis_pendaftaran_id").value=jenis_pendaftaran_id;
  document.getElementById("urutan").value="";
  document.getElementById("nama_biaya").value="";
  document.getElementById("biaya").value="";
  document.getElementById("jumlah_dikalikan").value=1;
  document.getElementById("pihak").value=0;
  document.getElementById("ghoib").value=0;
  document.getElementById("aktif").value="Y";
  document.getElementById("btn_jenisbiaya_edit").style.display="none";
  document.getElementById("btn_jenisbiaya_simpan").style.display="inline";
  document.getElementById("urutan").focus();
}

function tambah_jenis_biaya(){
    var urutan=document.getElementById('urutan').value;
    var jenis_pendaftaran_id=document.getElementById('jenis_pendaftaran_id').value;
    var nama_biaya=document.getElementById('nama_biaya').value;
    var biaya=document.getElementById('biaya').value;
    var jumlah_dikalikan=document.getElementById('jumlah_dikalikan').value;
    var ghoib=document.getElementById('ghoib').value;
    var pihak=document.getElementById('pihak').value;
    var aktif=document.getElementById('aktif').value;


    var b=new XMLHttpRequest();
    b.open("POST","_admin_data_tambah",true);
    b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    b.onreadystatechange=function()
    {
        if(b.readyState==XMLHttpRequest.DONE&&b.status==200){
            location.reload();
        }
    }
    b.send("urutan="+encodeURIComponent(urutan) +"&jenis_pendaftaran_id="+encodeURIComponent(jenis_pendaftaran_id) +"&nama_biaya="+encodeURIComponent(nama_biaya) +"&biaya="+encodeURIComponent(biaya) +"&jumlah_dikalikan="+encodeURIComponent(jumlah_dikalikan) +"&ghoib="+encodeURIComponent(ghoib) +"&pihak="+encodeURIComponent(pihak) +"&aktif="+encodeURIComponent(aktif) +"&tabel=panjar_jenis_biaya"); }

function edit_jenis_biaya(id){ 
    var b=new XMLHttpRequest();
    b.open("POST","_admin_data_edit",true);
    b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    b.onreadystatechange=function()
    {
        if(b.readyState==XMLHttpRequest.DONE&&b.status==200){
            var c=b.responseText;
            var obj = JSON.parse(c);
            document.getElementById('id').value=obj.id;
            document.getElementById('urutan').value=obj.urutan;
            document.getElementById('jenis_pendaftaran_id').value=obj.jenis_pendaftaran_id;
            document.getElementById('nama_biaya').value=obj.nama_biaya;
            document.getElementById('biaya').value=obj.biaya;
            document.getElementById('jumlah_dikalikan').value=obj.jumlah_dikalikan;
            document.getElementById('ghoib').value=obj.ghoib;
            document.getElementById('pihak').value=obj.pihak;
            document.getElementById('aktif').value=obj.aktif; 
            document.getElementById("modal_jenis_biaya").style.display="block";
             
            document.getElementById("btn_jenisbiaya_simpan").style.display="none";
            document.getElementById("btn_jenisbiaya_edit").style.display="inline";
        }
    };
    b.send("isi="+encodeURIComponent(id)+"&tabel=panjar_jenis_biaya&kunci=id");

}

function edit_jenis_biaya_simpan(){
    var id=document.getElementById('id').value;
    var urutan=document.getElementById('urutan').value;
    var nama_biaya=document.getElementById('nama_biaya').value;
    var biaya=document.getElementById('biaya').value;
    var jumlah_dikalikan=document.getElementById('jumlah_dikalikan').value;
    var ghoib=document.getElementById('ghoib').value;
    var pihak=document.getElementById('pihak').value;
    var aktif=document.getElementById('aktif').value;


    var b=new XMLHttpRequest();
    b.open("POST","_admin_data_edit_simpan",true);
    b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    b.onreadystatechange=function()
    {
        if(b.readyState==XMLHttpRequest.DONE&&b.status==200){
            location.reload();
        }
    }
    b.send("kunci="+encodeURIComponent(id) +"&kolom=id"+'&urutan='+encodeURIComponent(urutan) +'&nama_biaya='+encodeURIComponent(nama_biaya) +'&biaya='+encodeURIComponent(biaya) +'&jumlah_dikalikan='+encodeURIComponent(jumlah_dikalikan) +'&ghoib='+encodeURIComponent(ghoib) +'&pihak='+encodeURIComponent(pihak) +'&aktif='+encodeURIComponent(aktif) +"&tabel=panjar_jenis_biaya"); 
}

function hapus_jenis_biaya(id){
  var r = confirm("Yakin akan menghapus Jenis Biaya ini?");
  if (r == true) {
    var b=new XMLHttpRequest();
    b.open("POST","_admin_data_hapus",true);
    b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    b.onreadystatechange=function()
    {
        if(b.readyState==XMLHttpRequest.DONE&&b.status==200){
            location.reload();
        }
    }
    b.send("isi="+encodeURIComponent(id)+"&tabel=panjar_jenis_biaya&kunci=id");
  }
}