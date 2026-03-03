document.addEventListener("DOMContentLoaded", function() {
    data_propinsi();
    data_satker();
});

setTimeout(function() {
  new SlimSelect({
    select: '#propinsi_filter'
  })
}, 300)
setTimeout(function() {
  new SlimSelect({
    select: '#satker_filter'
  })
}, 300)

function data_propinsi(){
    var x='<option value="all">Semua Propinsi</value>';
	var xhr = new XMLHttpRequest(); 
    xhr.open("POST", '_admin_data_isi_tabel_komponen_biaya', true); 
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() 
    { 
        if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) 
        {
			var hasil=xhr.responseText;
                var hasil=xhr.responseText;
                var dataObj=JSON.parse(hasil); 
                dataObj.data.forEach(function(post, index) 
                {
                    x+="<option value='"+post.prop+"'>"+post.prop_name+"</option>";
                });
                document.getElementById('propinsi_filter').innerHTML=x;
        }
    }
    xhr.send("jenis=propinsi");
}


function data_satker(){
    var x='<option value="all">Semua Satker</value>';
    var xhr = new XMLHttpRequest(); 
    xhr.open("POST", '_admin_data_isi_tabel_komponen_biaya', true); 
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() 
    { 
        if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) 
        {
            var hasil=xhr.responseText;
                var hasil=xhr.responseText;
                var dataObj=JSON.parse(hasil); 
                dataObj.data.forEach(function(post, index) 
                {
                    x+="<option value='"+post.satker_code+"'>"+post.satker_name+"</option>";
                });
                document.getElementById('satker_filter').innerHTML=x;
        }
    }
    xhr.send("jenis=satker");
}


function pilih_propinsi(id){
    var x='<option value="-">Pilih Satker</value><option value="all">Semua Satker</value>';
    var xhr = new XMLHttpRequest(); 
    xhr.open("POST", '_admin_data_isi_tabel_komponen_biaya', true); 
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() 
    { 
        if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) 
        {
            var hasil=xhr.responseText;
                var hasil=xhr.responseText;
                var dataObj=JSON.parse(hasil); 
                dataObj.data.forEach(function(post, index) 
                {
                    x+="<option value='"+post.satker_code+"'>"+post.satker_name+"</option>";
                });
                document.getElementById('satker_filter').innerHTML=x;
        }
    }
    xhr.send("jenis=satker&prop="+id);
}
function tampilkan_data_radius(){
    document.getElementById('isi_radius').innerHTML="";
    document.getElementById("loader").style.display="block";
    var prop=document.getElementById("propinsi_filter").value;
    var satker_code=document.getElementById("satker_filter").value;
    var xhr = new XMLHttpRequest(); 
    xhr.open("POST", '_admin_data_isi_tabel_komponen_biaya', true); 
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

                    x+="<tr> <td>"+nomor+"</td><td>"+post.satker_name+"</td> <td>"+post.prop_name+"</td> <td>"+post.kabkota+"</td> <td>"+post.kec+"</td> <td>"+post.kel+"</td> <td>"+post.nomor_radius+"</td> <td><span title='ubah nilai radius' class='pointer' onclick='update_radius("+post.id+","+post.nilai+")'>"+rubah(post.nilai)+"</span></td></tr>";
                    nomor=nomor+1;
                });
                document.getElementById('isi_radius').innerHTML=x;
                document.getElementById("loader").style.display="none";
                var table1 = new DataTable("#tabledata", {  perPage: 50,perPageSelect : [50, 100, 500, 1000, 5000] });

        }
    }
    xhr.send("jenis=radius&prop="+prop+"&satker_code="+satker_code);
}
function rubah(angka){
       var reverse = angka.toString().split('').reverse().join(''),
       ribuan = reverse.match(/\d{1,3}/g);
       ribuan = ribuan.join('.').split('').reverse().join('');
       return ribuan;
}

function update_radius(id,nilai){
    document.getElementById("id").value=id;
    document.getElementById("nilai").value=nilai;
    document.getElementById("modal_update_radius").style.display="block";
}
function edit_nilai_radius_simpan(){
    var id=document.getElementById("id").value;
    var nilai=document.getElementById("nilai").value;
    //alert(id);
    //alert(nilai);
    var b=new XMLHttpRequest();
    b.open("POST","_admin_data_edit_simpan",true);
    b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    b.onreadystatechange=function()
    {
        if(b.readyState==XMLHttpRequest.DONE&&b.status==200){
            tampilkan_data_radius();
            document.getElementById("modal_update_radius").style.display="none";
        }
    }
    b.send("kunci="+encodeURIComponent(id) +"&nilai="+encodeURIComponent(nilai)+"&kolom=id&tabel=panjar_kelurahan_komdanas");

}