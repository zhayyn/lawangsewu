document.getElementById("loader").style.display="none";

setTimeout(function() {
  new SlimSelect({
    select: '#provinsi'
  })
}, 300)
setTimeout(function() {
  new SlimSelect({
    select: '#kota'
  })
}, 300)

setTimeout(function() {
  new SlimSelect({
    select: '#kecamatan'
  })
}, 300)
setTimeout(function() {
  new SlimSelect({
    select: '#kelurahan'
  })
}, 300)

function rubah(angka){
var reverse = angka.toString().split('').reverse().join(''),
ribuan = reverse.match(/\d{1,3}/g);
ribuan = ribuan.join('.').split('').reverse().join('');
return ribuan;
}


function isi_tambah_pihak(){
  document.getElementById("tambahpemohonModal").style.display="block";
  document.getElementById("nama").value="";
  document.getElementById("provinsi").value="";
  document.getElementById("kota").innerHTML="";
  document.getElementById("kecamatan").innerHTML="";
  document.getElementById("kelurahan").innerHTML="";
  document.getElementById("sebutan_id").value="";
  document.getElementById("hasil_p").innerHTML="";
  document.getElementById("ghoib").value="";
  document.getElementById("alamat_pihak").style.display="none";
  document.getElementById("nama").style.background="#fff";
  pilih_daftar('0100','kota','id_provinces');
  pilih_daftar('Kota Jakarta Barat','kecamatan','id_regencies');
  ///

  ///
  document.getElementById("provinsi").value='0100';
  document.getElementById("kota").value='Kota Jakarta Barat';
  document.getElementById("nama").focus();
}

function pilih_ghoib(isi){
  if(isi==0)
  {
    document.getElementById("alamat_pihak").style.display="none";;
    document.getElementById("btn_kirim_pemohon").focus();
  }else{
    document.getElementById("alamat_pihak").style.display="block";
    document.getElementById("provinsi").focus();
  }
}

function pilih_daftar(isi,jenis,kunci){
  if(isi=="")
  {
      return false;
  }
  document.getElementById("loader").style.display="block";
  var b=new XMLHttpRequest();
  b.open("POST","_panjar_data_wilayah.php",true);
  b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
  b.onreadystatechange=function(){
    if(b.readyState==XMLHttpRequest.DONE&&b.status==200)
    {
      var c=b.responseText;
      document.getElementById(jenis).innerHTML=c;
      document.getElementById("loader").style.display="none";
      document.getElementById(jenis).focus();
    }
  }
  b.send("jenis="+jenis+"&"+kunci+"="+encodeURIComponent(isi));
}
function  pilih_kelurahan(isi){
  var res = isi.split("^");
  document.getElementById("satker_code").value=res[1];
  document.getElementById("nilai").value=res[3];
  document.getElementById("alamat").value=res[4];
  document.getElementById("hasil_p").innerHTML="Wilayah Yurisdiksi : "+res[2]+"<br>"+"Biaya Panggilan : Rp "+rubah(res[3]);
}


function kirim_pemohon(){
  var id_panjar=document.getElementById("id_panjar").value; 
  var satker_code=document.getElementById("satker_code").value;   
  var nama=document.getElementById("nama").value; 
  var sebutan_id=document.getElementById("sebutan_id").value; 
  var sebutan=document.getElementById("sebutan").value; 
  var jenis_pendaftaran_id=document.getElementById("jenis_pendaftaran_id").value; 
  var jenis_pendaftaran=document.getElementById("jenis_pendaftaran").value; 
  var nilai=document.getElementById("nilai").value; 
  var alamat=document.getElementById("alamat").value;
  var ghoib=document.getElementById("ghoib").value;
  
  var provinsi=document.getElementById("provinsi");
  var kota=document.getElementById("kota");
  var kecamatan=document.getElementById("kecamatan");
  var kelurahan=document.getElementById("kelurahan");
  //alert(ghoib);  
  
  if(nama==null || nama=="")
  {
       document.getElementById("nama").style.background="#f9f2f4";
       document.getElementById("nama").focus();
       return false;  
  }else
  if(sebutan_id==null || sebutan_id=="")
  {
       document.getElementById("sebutan_id").style.background="#f9f2f4";
       document.getElementById("sebutan_id").focus();
       return false;  
  }else
  if(ghoib==null || ghoib=="")
  {
       document.getElementById("ghoib").style.background="#f9f2f4";
       document.getElementById("ghoib").focus();
       return false;  
  }else
  if(ghoib==1)
  {
    if(provinsi.value==null || provinsi.value=="")
    {
         provinsi.style.background="#f9f2f4";
         provinsi.focus();
         return false;  
    }
    if(kota.value==null || kota.value=="")
    {
         kota.style.background="#f9f2f4";
         kota.focus();
         return false;  
    }
    if(kecamatan.value==null || kecamatan.value=="")
    {
         kecamatan.style.background="#f9f2f4";
         kecamatan.focus();
         return false;  
    }
    if(kelurahan.value==null || kelurahan.value=="")
    {
         kelurahan.style.background="#f9f2f4";
         kelurahan.focus();
         return false;  
    }
  }  
  var xhr = new XMLHttpRequest();

  var url='_hitung_panjar.php';
    xhr.open("POST", url, true);
    
    //Send the proper header information along with the request
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function() {//Call a function when the state changes.
      if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) {
       //var pesan=xhr.responseText ;
       //alert(xhr.responseText) ;
       document.getElementById("data_pihak").innerHTML=xhr.responseText;
       document.getElementById("tambahpemohonModal").style.display="none";

      }
    }
    xhr.send("id_panjar="+encodeURIComponent(id_panjar)+"&satker_code="+encodeURIComponent(satker_code)+"&nama_pihak="+encodeURIComponent(nama)+"&sebutan_id="+encodeURIComponent(sebutan_id)+"&sebutan="+encodeURIComponent(sebutan)+"&jenis_pendaftaran_id="+encodeURIComponent(jenis_pendaftaran_id)+"&jenis_pendaftaran="+encodeURIComponent(jenis_pendaftaran)+"&nilai="+encodeURIComponent(nilai)+"&alamat="+encodeURIComponent(alamat)+"&ghoib="+encodeURIComponent(ghoib)); 
}
function hapus_pihak(id_panjar,nama_pihak){
  var xhr = new XMLHttpRequest();
  var url='_hapus_pihak.php';
    xhr.open("POST", url, true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function(){
    if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200){
      document.getElementById("data_pihak").innerHTML=xhr.responseText;
      }
    }
    xhr.send("id_panjar="+encodeURIComponent(id_panjar)+"&nama_pihak="+encodeURIComponent(nama_pihak)); 
}