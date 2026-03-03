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

function update_antri(jam){
    document.getElementById("waktu_mulai_antri").value=jam;
    document.getElementById("modal_update_jam_antri_sidang").style.display="block";
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
function edit_nilai_antri_simpan_simpan(id){
    var waktu_mulai_antri=document.getElementById("waktu_mulai_antri").value;
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
    b.send("kunci="+encodeURIComponent(id) +"&waktu_mulai_antri="+encodeURIComponent(waktu_mulai_antri)+"&kolom=id&tabel=antrian_sidang_config");

}