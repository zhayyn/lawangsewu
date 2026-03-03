
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

      setTimeout(function() {
        new SlimSelect({
          select: '#provinsi1'
        })
      }, 300)
      setTimeout(function() {
        new SlimSelect({
          select: '#kota1'
        })
      }, 300)

      setTimeout(function() {
        new SlimSelect({
          select: '#kecamatan1'
        })
      }, 300)
      setTimeout(function() {
        new SlimSelect({
          select: '#kelurahan1'
        })
      }, 300)

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
      function pilih_daftar1(isi,jenis,jenis1,kunci){
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
            document.getElementById(jenis1).innerHTML=c;
            document.getElementById("loader").style.display="none";
            document.getElementById(jenis1).focus();
          }
        }
        b.send("jenis="+jenis+"&"+kunci+"="+encodeURIComponent(isi));
      }

    function rubah(angka){
       var reverse = angka.toString().split('').reverse().join(''),
       ribuan = reverse.match(/\d{1,3}/g);
       ribuan = ribuan.join('.').split('').reverse().join('');
       return ribuan;
     }

    function  pilih_kelurahan(isi){
            var res = isi.split("^");
            document.getElementById("satker_code").value=res[1];
            document.getElementById("nilai").value=res[3];
            document.getElementById("alamat").value=res[4];
            document.getElementById("hasil_p").innerHTML="Wilayah Yurisdiksi : "+res[2]+"<br>"+"Biaya Panggilan : Rp "+rubah(res[3]); 

      }

    function  pilih_kelurahan1(isi){
            var res = isi.split("^");
            document.getElementById("satker_code1").value=res[1];
            document.getElementById("nilai1").value=res[3];
            document.getElementById("alamat1").value=res[4];
            document.getElementById("hasil_t").innerHTML="Wilayah Yurisdiksi : "+res[2]+"<br>"+"Biaya Panggilan : Rp "+rubah(res[3]); 

      }

              function pilih_ghoib(isi){ 
                if(isi == "1")
                { 
                  document.getElementById("identitas_t").style.display="block";
                  document.getElementById("provinsi1").focus()
                }else
                { 
                  document.getElementById("identitas_t").style.display="none";
                  document.getElementById("hitung").focus();
                }
                
                //console.log(kelase);

              }

function kirim_data_cerai(){

  var jenis_pendaftaran=document.getElementById("jenis_pendaftaran").value; 
  var jenis_pendaftaran_id=document.getElementById("jenis_pendaftaran_id").value; 
  
  var nama_p=document.getElementById("nama_p").value; 
  var provinsi=document.getElementById("provinsi").value; 
  var kota=document.getElementById("kota").value; 
  var kecamatan=document.getElementById("kecamatan").value; 
  var kelurahan=document.getElementById("kelurahan").value; 
  var satker_code=document.getElementById("satker_code").value; 
  var nilai=document.getElementById("nilai").value; 
  var alamat=document.getElementById("alamat").value; 
  
  var nama_t=document.getElementById("nama_t").value; 
  var provinsi1=document.getElementById("provinsi1").value; 
  var kota1=document.getElementById("kota1").value; 
  var kecamatan1=document.getElementById("kecamatan1").value; 
  var kelurahan1=document.getElementById("kelurahan1").value; 
  var satker_code1=document.getElementById("satker_code1").value; 
  var nilai1=document.getElementById("nilai1").value; 
  var alamat1=document.getElementById("alamat1").value; 
  
  var id_panjar=document.getElementById("id_panjar").value; 
  var sebutan_p=document.getElementById("sebutan_p").value; 
  var sebutan_t=document.getElementById("sebutan_t").value; 
  var ghoib=document.getElementById("ghoib").value;  

  if(nama_p==null || nama_p=="")
  {
       document.getElementById("nama_p").style.background="#f9f2f4";
       document.getElementById("nama_p").focus();
       return false;  
  }else

  if(provinsi==null || provinsi=="")
  {
       document.getElementById("provinsi").style.background="#f9f2f4";
       document.getElementById("provinsi").focus();
       return false;  
  }else
  if(kota==null || kota=="")
  {
       document.getElementById("kota").style.background="#f9f2f4";
       document.getElementById("kota").focus();
       return false;  
  }else
  if(kecamatan==null || kecamatan=="")
  {
       document.getElementById("kecamatan").style.background="#f9f2f4";
       document.getElementById("kecamatan").focus();
       return false;  
  }else
  if(kelurahan==null || kelurahan=="")
  {
       document.getElementById("kelurahan").style.background="#f9f2f4";
       document.getElementById("kelurahan").focus();
       return false;  
  }else
  if(nama_t==null || nama_t=="")
  {
       document.getElementById("nama_t").style.background="#f9f2f4";
       document.getElementById("nama_t").focus();
       return false;  
  }else
  if(ghoib==null || ghoib=="")
  {
       document.getElementById("ghoib").style.background="#f9f2f4";
       document.getElementById("ghoib").focus();
       return false;  
  }else
  if(ghoib=="1")
  {
    if(provinsi1==null || provinsi1=="")
    {
         document.getElementById("provinsi1").style.background="#f9f2f4";
         document.getElementById("provinsi1").focus();
         return false;  
    }else
    if(kota1==null || kota1=="")
    {
         document.getElementById("kota1").style.background="#f9f2f4";
         document.getElementById("kota1").focus();
         return false;  
    }else
    if(kecamatan1==null || kecamatan1=="")
    {
         document.getElementById("kecamatan1").style.background="#f9f2f4";
         document.getElementById("kecamatan1").focus();
         return false;  
    }else
    if(kelurahan1==null || kelurahan1=="")
    {
         document.getElementById("kelurahan1").style.background="#f9f2f4";
         document.getElementById("kelurahan1").focus();
         return false;  
    }
  }
  
    
  var xhr = new XMLHttpRequest();

  var url='_cerai_proses.php';
    xhr.open("POST", url, true);
    
    //Send the proper header information along with the request
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function() {//Call a function when the state changes.
      if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) {
       //var pesan=xhr.responseText ;
       //alert(xhr.responseText) ;
       document.getElementById("hasil_cerai").innerHTML=xhr.responseText;
        
      }
    }
    xhr.send("id_panjar="+encodeURIComponent(id_panjar) +"&satker_code="+encodeURIComponent(satker_code) +"&satker_code1="+encodeURIComponent(satker_code1) +"&nama_p="+encodeURIComponent(nama_p) +"&sebutan_p="+encodeURIComponent(sebutan_p) +"&sebutan_t="+encodeURIComponent(sebutan_t) +"&jenis_pendaftaran_id="+encodeURIComponent(jenis_pendaftaran_id) +"&jenis_pendaftaran="+encodeURIComponent(jenis_pendaftaran) +"&nilai="+encodeURIComponent(nilai) +"&alamat="+encodeURIComponent(alamat) +"&nama_t="+encodeURIComponent(nama_t) +"&nilai1="+encodeURIComponent(nilai1) +"&alamat1="+encodeURIComponent(alamat1) +"&ghoib="+encodeURIComponent(ghoib) ); }