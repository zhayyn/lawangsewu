document.addEventListener("DOMContentLoaded", function() {
    //tampilkan_data_panjar(0,100);
});

function tampilkan_data_pengguna(){
    var bulan=document.getElementById('bulan').value;
    var tahun=document.getElementById('tahun').value;
    document.getElementById('isi_pengguna_informasi').innerHTML="";
    document.getElementById("loader").style.display="block";
    var xhr = new XMLHttpRequest(); 
    xhr.open("POST", '_admin_data_isi_tabel_pengguna_informasi', true); 
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() 
    { 
        if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) 
        {
            var hasil=xhr.responseText;
                var hasil=xhr.responseText;
                document.getElementById('isi_pengguna_informasi').innerHTML=hasil;
                document.getElementById("loader").style.display="none";
              
        }
    }
    xhr.send("bulan="+bulan+"&tahun="+tahun);
}
function rubah(angka){
       var reverse = angka.toString().split('').reverse().join(''),
       ribuan = reverse.match(/\d{1,3}/g);
       ribuan = ribuan.join('.').split('').reverse().join('');
       return ribuan;
}
 