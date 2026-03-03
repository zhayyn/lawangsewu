document.addEventListener("DOMContentLoaded", function() {
    tampilkan_data_panjar(0,100);
});

function tampilkan_data_panjar(mulai, limit){
    document.getElementById('isi_panjar').innerHTML="";
    document.getElementById("loader").style.display="block";
    var xhr = new XMLHttpRequest(); 
    xhr.open("POST", '_admin_data_isi_tabel_pengguna_panjar', true); 
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() 
    { 
        if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) 
        {
            var hasil=xhr.responseText;
                var hasil=xhr.responseText;
                //alert(hasil);return false;
                var dataObj=JSON.parse(hasil); 
                var nomor=1;
                var x="";
                dataObj.data.forEach(function(post, index) 
                {

                    x+="<tr id='id_"+post.id_panjar+"'> <td>"+post.nomor+"</td><td>"+post.jenis_pendaftaran+"</td> <td>"+post.pihak
+"</td> <td style='text-align:right'><a href='#id_"+post.id_panjar+"' onclick='detail_taksiran("+post.id_panjar+")'><b>"+rubah(post.total_panjar)+"</b></a></td> <td>"+post.diinput_tanggal+"</td></tr>";
                    nomor=nomor+1;
                });
                document.getElementById('isi_panjar').innerHTML=x;
                tampilkan_pagination(mulai,limit);
                document.getElementById("loader").style.display="none";
              
        }
    }
    xhr.send("mulai="+mulai+"&limit="+limit);
}
function tampilkan_pagination(mulai, limit){
    document.getElementById('pagination').innerHTML="";
    document.getElementById("loader").style.display="block";
    var xhr = new XMLHttpRequest(); 
    xhr.open("POST", '_admin_data_isi_tabel_pengguna_panjar', true); 
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() 
    { 
        if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) 
        {
            var hasil=xhr.responseText;
            document.getElementById('pagination').innerHTML=hasil;
           document.getElementById("loader").style.display="none";
              
        }
    }
    xhr.send("pagination=ya&mulai="+mulai+"&limit="+limit);
}
function detail_taksiran(id_panjar){
    document.getElementById("loader").style.display="block";
    var xhr = new XMLHttpRequest(); 
    xhr.open("POST", '_admin_data_isi_tabel_pengguna_panjar', true); 
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() 
    { 
        if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) 
        {
            var hasil=xhr.responseText;
            document.getElementById('modal_detail_isi').innerHTML=hasil;
            document.getElementById('modal_detail').style.display="block";
           document.getElementById("loader").style.display="none";
              
        }
    }
    xhr.send("detail=ya&id_panjar="+id_panjar);
}
function rubah(angka){
       var reverse = angka.toString().split('').reverse().join(''),
       ribuan = reverse.match(/\d{1,3}/g);
       ribuan = ribuan.join('.').split('').reverse().join('');
       return ribuan;
}
 