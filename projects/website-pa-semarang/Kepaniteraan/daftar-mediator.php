<?php
$updatedAt = date('d-m-Y H:i:s');
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daftar Mediator PA Semarang Kelas IA</title>
  <style>
    body { margin: 0; font-family: Arial, sans-serif; background: #f5f7f8; color: #1f2937; }
    .container { max-width: 900px; margin: 24px auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 24px; }
    h1 { margin: 0 0 8px; color: #065f46; font-size: 26px; text-align: center; }
    h2 { margin: 0 0 20px; text-align: center; color: #374151; font-size: 20px; letter-spacing: 1px; }
    .lampiran { background: #f0fdf4; border-left: 4px solid #059669; padding: 14px; margin-bottom: 20px; }
    .lampiran p { margin: 6px 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { border: 1px solid #d1d5db; padding: 10px; vertical-align: top; }
    th { background: #ecfdf5; color: #065f46; text-align: left; width: 40%; }
    .muted { color: #6b7280; font-size: 13px; margin-top: 16px; text-align: right; }
  </style>
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
