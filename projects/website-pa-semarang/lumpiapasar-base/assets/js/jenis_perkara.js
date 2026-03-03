var table1 = new DataTable("#tabledata", {  perPage: 30,perPageSelect : [10, 25, 50, 100, 500] });
function buka_modal_jenis_perkara()
{
  document.getElementById("modal_jenis_perkara").style.display="block";
  document.getElementById("jenis_pendaftaran").value="";
  document.getElementById("keterangan").innerHTML="";
  document.getElementById("sebutan_p").value="";
  document.getElementById("sebutan_t").value="";
  document.getElementById("ikon").value="";
  document.getElementById("aktif").value="Y";
  document.getElementById("btn_jenisperkara_edit").style.display="none";
  document.getElementById("btn_jenisperkara_simpan").style.display="inline";
  document.getElementById("jenis_pendaftaran").focus();
}
function edit_jenis_perkara(id){ 
    var b=new XMLHttpRequest();
    b.open("POST","_admin_data_edit",true);
    b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    b.onreadystatechange=function()
    {
        if(b.readyState==XMLHttpRequest.DONE&&b.status==200){
            var c=b.responseText;
            var obj = JSON.parse(c);
            document.getElementById("id").value=obj.id;
            document.getElementById("jenis_pendaftaran").value=obj.jenis_pendaftaran;
            document.getElementById("keterangan").innerHTML=obj.keterangan;
            document.getElementById("sebutan_p").value=obj.sebutan_p;
            document.getElementById("sebutan_t").value=obj.sebutan_t;
            document.getElementById("ikon").value=obj.ikon;
            document.getElementById("aktif").value=obj.aktif;
            document.getElementById("modal_jenis_perkara").style.display="block";
            document.getElementById("jenis_pendaftaran").focus();
            document.getElementById("btn_jenisperkara_simpan").style.display="none";
            document.getElementById("btn_jenisperkara_edit").style.display="inline";
            pilih_gambar(obj.ikon);
        }
    };
    b.send("isi="+encodeURIComponent(id)+"&tabel=panjar_jenis_pendaftaran&kunci=id");

}
function hapus_jenis_perkara(id){
  var r = confirm("Yakin akan menghapus Jenis Perkara ini?");
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
    b.send("isi="+encodeURIComponent(id)+"&tabel=panjar_jenis_pendaftaran&kunci=id");
  }
}
function tambah_jenis_perkara(){
    var jenis_pendaftaran=document.getElementById("jenis_pendaftaran").value;
    var keterangan=document.getElementById("keterangan").value;
    var sebutan_p=document.getElementById("sebutan_p").value;
    var sebutan_t=document.getElementById("sebutan_t").value;
    var ikon=document.getElementById("ikon").value;
    var aktif=document.getElementById("aktif").value;

    var b=new XMLHttpRequest();
    b.open("POST","_admin_data_tambah",true);
    b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    b.onreadystatechange=function()
    {
        if(b.readyState==XMLHttpRequest.DONE&&b.status==200){
            location.reload();
        }
    }
    b.send("jenis_pendaftaran="+encodeURIComponent(jenis_pendaftaran) +"&keterangan="+encodeURIComponent(keterangan) +"&sebutan_p="+encodeURIComponent(sebutan_p) +"&sebutan_t="+encodeURIComponent(sebutan_t) +"&ikon="+encodeURIComponent(ikon) +"&aktif="+encodeURIComponent(aktif) +"&tabel=panjar_jenis_pendaftaran");
}
function edit_jenis_perkara_simpan(){
    var id=document.getElementById("id").value;
    var jenis_pendaftaran=document.getElementById("jenis_pendaftaran").value;
    var keterangan=document.getElementById("keterangan").value;
    var sebutan_p=document.getElementById("sebutan_p").value;
    var sebutan_t=document.getElementById("sebutan_t").value;
    var ikon=document.getElementById("ikon").value;
    var aktif=document.getElementById("aktif").value;

    var b=new XMLHttpRequest();
    b.open("POST","_admin_data_edit_simpan",true);
    b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    b.onreadystatechange=function()
    {
        if(b.readyState==XMLHttpRequest.DONE&&b.status==200){
            location.reload();
        }
    }
    b.send("kunci="+encodeURIComponent(id) +"&kolom=id&jenis_pendaftaran="+encodeURIComponent(jenis_pendaftaran) +"&keterangan="+encodeURIComponent(keterangan) +"&sebutan_p="+encodeURIComponent(sebutan_p) +"&sebutan_t="+encodeURIComponent(sebutan_t) +"&ikon="+encodeURIComponent(ikon) +"&aktif="+encodeURIComponent(aktif) +"&tabel=panjar_jenis_pendaftaran");
}
function pilih_gambar(nama_file)
{
  document.getElementById("tampilan_ikon").innerHTML="<img style='max-width:128px' src='assets/images/"+nama_file+"'>";
}
