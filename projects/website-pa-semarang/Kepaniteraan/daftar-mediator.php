<?php
$updatedAt = date('d-m-Y H:i:s');
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daftar Mediator PA Semarang Kelas IA</title>
  <link rel="stylesheet" href="custom-kepaniteraan.css">
</head>
<body>
  <div class="container">
    <h1>DAFTAR MEDIATOR PENGADILAN AGAMA SEMARANG KELAS I A</h1>
    <h2>TAHUN 2025</h2>

    <div class="lampiran">
      <p><strong>LAMPIRAN KEPUTUSAN KETUA PENGADILAN AGAMA SEMARANG KELAS I A</strong></p>
      <p>Nomor: <strong>18.a/KPA.W11-A1/SK.HK1.3/I/2025</strong></p>
      <p>Tanggal: <strong>2 Januari 2025</strong></p>
    </div>

    <h3>JAM KERJA PELAYANAN</h3>
    <table>
      <tr><th>Senin - Kamis</th><td>08:00 - 16:30</td></tr>
      <tr><th>Jumat</th><td>07:00 - 16:00</td></tr>
      <tr><th>Istirahat (Jumat)</th><td>11:30 - 13:00</td></tr>
      <tr><th>Jadwal Sidang (Senin - Jumat)</th><td>09:00 - selesai</td></tr>
    </table>

    <p class="muted">Diperbarui server: <?php echo htmlspecialchars($updatedAt, ENT_QUOTES, 'UTF-8'); ?> | Browser: <span id="updatedAt"></span></p>
  </div>

  <script>
    document.getElementById('updatedAt').textContent = new Date().toLocaleString('id-ID');
  </script>
</body>
</html>
