
function Edit_Isi(isi,table,field,kunci,isi_kunci, jenis)
{
  var xhr = new XMLHttpRequest();
      var url='_admin_data_edit_simpan';
        xhr.open("POST", url, true);
        
        //Send the proper header information along with the request
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {//Call a function when the state changes.
          if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) {
            
          }
        }
        xhr.send("isi="+encodeURIComponent(isi)+"&tabel="+encodeURIComponent(table)+"&field="+encodeURIComponent(field)+"&jenis="+encodeURIComponent(jenis)+"&kunci="+encodeURIComponent(kunci)+"&isi_kunci="+encodeURIComponent(isi_kunci)); 
}

  function tampil_modal_profil()
  {
    document.getElementById('modal_profil').style.display='block';
    document.getElementById('kata_sandi').value='';
    document.getElementById('kata_sandi_lagi').value='';
    document.getElementById('kata_sandi').focus(); 
  }
  function ubah_sandi()
  {
    var id=document.getElementById('ID').value;
    var kata_sandi=document.getElementById('kata_sandi').value;
    var kata_sandi_lagi=document.getElementById('kata_sandi_lagi').value;
    var user_activation_key=document.getElementById('user_activation_key').value;
    if(kata_sandi==null || kata_sandi=="")
      {

        document.getElementById('kata_sandi_error').innerHTML="Kata Sandi Harus diisi"; 
        document.getElementById('kata_sandi').focus(); 
        return false;
      }
    if(kata_sandi_lagi==null || kata_sandi_lagi=="")
      {

        document.getElementById('kata_sandi_lagi_error').innerHTML="Kata Sandi Harus diisi"; 
        document.getElementById('kata_sandi_lagi').focus(); 
        return false;
      }
    if(kata_sandi == kata_sandi_lagi)
    {
     // alert("proses");
            var xhr = new XMLHttpRequest();
            var url='_admin_data_edit_simpan';
              xhr.open("POST", url, true);
              
              //Send the proper header information along with the request
              xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

              xhr.onreadystatechange = function() {//Call a function when the state changes.
                if(xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) {
                  //alert(xhr.responseText) ;    
                  document.getElementById('modal_profil').style.display='none';
                }
              }
              xhr.send("tabel=password&kolom=ID&kunci="+encodeURIComponent(id)+"&kata_sandi="+encodeURIComponent(kata_sandi)+"&user_activation_key="+encodeURIComponent(user_activation_key)); 
    }else
    {
      document.getElementById('kata_sandi_lagi_error').innerHTML="Kata Sandi Harus sama"; 
        document.getElementById('kata_sandi_lagi').focus(); 
        return false;
    }
  }