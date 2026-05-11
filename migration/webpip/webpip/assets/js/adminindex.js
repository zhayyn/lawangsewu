function rubah(angka){
       var reverse = angka.toString().split('').reverse().join(''),
       ribuan = reverse.match(/\d{1,3}/g);
       ribuan = ribuan.join('.').split('').reverse().join('');
       return ribuan;
}

function update_ongkir(biaya){
    document.getElementById("biaya").value=biaya;
    document.getElementById("modal_update_ongkir").style.display="block";
}
function edit_nilai_ongkir_simpan(id){
    var biaya=document.getElementById("biaya").value;
    //alert(id);
    //alert(nilai);
    var b=new XMLHttpRequest();
    b.open("POST","_admin_data_edit_simpan",true);
    b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    b.onreadystatechange=function()
    {
        if(b.readyState==XMLHttpRequest.DONE&&b.status==200){
           location.reload();
        }
    }
    b.send("kunci="+encodeURIComponent(id) +"&biaya="+encodeURIComponent(biaya)+"&kolom=id&tabel=panjar_ongkos_kirim");
}
function cetak_permohonan(id){
    document.getElementById("loader").style.display='block';
    //alert(id);
    //alert(nilai);
    var b=new XMLHttpRequest();
    b.open("POST","_cetak_permohonan",true);
    b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    b.onreadystatechange=function()
    {
        if(b.readyState==XMLHttpRequest.DONE&&b.status==200){
          document.getElementById("isi_cetak").innerHTML="<center><a class='w3-btn w3-green' href="+b.responseText+" >Cetak Permohonan</a></center>";
          document.getElementById("loader").style.display='none';
          document.getElementById("modal_cetak").style.display="block";

           //location.reload();
        }
    }
    b.send("id="+encodeURIComponent(id));
}