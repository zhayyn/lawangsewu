-- SIPP-HUB Looker Studio Chart Pack
-- Project: lawang-sewu-490507
-- Dataset: sipp_dataset
-- Source table: lawang-sewu-490507.sipp_dataset.server10_ingest_raw
-- Developed by zhayyn™
--
-- Cara pakai:
-- 1) Buka BigQuery SQL Workspace
-- 2) Jalankan per blok CREATE VIEW (boleh satu per satu)
-- 3) Di Looker Studio, pilih view yang dihasilkan sebagai data source chart

-- ============================================================================
-- 01) Kinerja Layanan & Transparansi
-- ============================================================================

-- 01A. Tingkat Keberhasilan Mediasi (bulanan)
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_kpi_keberhasilan_mediasi` AS
WITH mediasi AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    DATE(COALESCE(
      SAFE_CAST(JSON_VALUE(payload, '$.dimulai_mediasi') AS DATETIME),
      SAFE_CAST(JSON_VALUE(payload, '$.penetapan_tanggal_mediasi') AS DATETIME),
      SAFE_CAST(JSON_VALUE(payload, '$.diperbaharui_tanggal') AS DATETIME)
    )) AS tgl_mediasi,
    LOWER(COALESCE(
      JSON_VALUE(payload, '$.mediasi_berhasil'),
      JSON_VALUE(payload, '$.hasil_mediasi'),
      JSON_VALUE(payload, '$.keputusan_mediasi')
    )) AS indikator_berhasil,
    LOWER(COALESCE(
      JSON_VALUE(payload, '$.mediasi_gagal'),
      JSON_VALUE(payload, '$.hasil_mediasi'),
      JSON_VALUE(payload, '$.keputusan_mediasi')
    )) AS indikator_gagal
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara_mediasi'
), scored AS (
  SELECT
    DATE_TRUNC(tgl_mediasi, MONTH) AS bulan,
    CASE
      WHEN indikator_berhasil IN ('1', 'true', 'ya', 'berhasil', 'success')
        OR REGEXP_CONTAINS(indikator_berhasil, r'berhasil|damai|sepakat')
      THEN 'BERHASIL'
      WHEN indikator_gagal IN ('1', 'true', 'ya', 'gagal', 'fail')
        OR REGEXP_CONTAINS(indikator_gagal, r'gagal|tidak berhasil|deadlock')
      THEN 'GAGAL'
      ELSE 'LAINNYA'
    END AS status_mediasi
  FROM mediasi
  WHERE tgl_mediasi IS NOT NULL
)
SELECT
  bulan,
  status_mediasi,
  COUNT(*) AS jumlah_perkara,
  ROUND(100 * COUNT(*) / SUM(COUNT(*)) OVER (PARTITION BY bulan), 2) AS persentase_bulanan
FROM scored
GROUP BY bulan, status_mediasi
ORDER BY bulan, status_mediasi;

-- 01B. Tren Penggunaan E-Court vs Konvensional (bulanan)
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_tren_ecourt_vs_konvensional` AS
WITH perkara AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME)) AS tgl_daftar
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara'
), efiling AS (
  SELECT DISTINCT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara_efiling_id'
)
SELECT
  DATE_TRUNC(p.tgl_daftar, MONTH) AS bulan,
  CASE WHEN e.perkara_id IS NOT NULL THEN 'E-COURT' ELSE 'KONVENSIONAL' END AS kanal,
  COUNT(*) AS jumlah_perkara
FROM perkara p
LEFT JOIN efiling e USING (perkara_id)
WHERE p.tgl_daftar IS NOT NULL
GROUP BY bulan, kanal
ORDER BY bulan, kanal;

-- 01C. Rata-rata Waktu Penyelesaian Perkara (hari)
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_rata_waktu_selesai_perkara` AS
WITH perkara AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME)) AS tgl_daftar,
    COALESCE(JSON_VALUE(payload, '$.jenis_perkara_nama'), JSON_VALUE(payload, '$.jenis_perkara_text')) AS jenis_perkara
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara'
), putusan AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_putusan') AS DATETIME)) AS tgl_putusan,
    COALESCE(JSON_VALUE(payload, '$.status_putusan_nama'), JSON_VALUE(payload, '$.status_putusan_text')) AS status_putusan
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara_putusan'
)
SELECT
  DATE_TRUNC(pu.tgl_putusan, MONTH) AS bulan_putus,
  p.jenis_perkara,
  COUNT(*) AS jumlah_perkara_putus,
  ROUND(AVG(DATE_DIFF(pu.tgl_putusan, p.tgl_daftar, DAY)), 2) AS rata_hari_selesai,
  ROUND(APPROX_QUANTILES(DATE_DIFF(pu.tgl_putusan, p.tgl_daftar, DAY), 100)[OFFSET(50)], 2) AS median_hari_selesai
FROM putusan pu
JOIN perkara p USING (perkara_id)
WHERE p.tgl_daftar IS NOT NULL
  AND pu.tgl_putusan IS NOT NULL
  AND pu.tgl_putusan >= p.tgl_daftar
GROUP BY bulan_putus, p.jenis_perkara
ORDER BY bulan_putus, p.jenis_perkara;

-- 01D. Statistik Perkara Prodeo (jumlah perkara & nilai biaya prodeo)
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_statistik_prodeo` AS
WITH perkara AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME)) AS tgl_daftar,
    SAFE_CAST(JSON_VALUE(payload, '$.prodeo') AS INT64) AS prodeo_flag
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara'
), ec_prodeo AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.efiling_id') AS INT64) AS efiling_id,
    DATE(SAFE_CAST(JSON_VALUE(payload, '$.diperbaharui_tanggal') AS DATETIME)) AS tgl_prodeo,
    SAFE_CAST(REGEXP_REPLACE(COALESCE(JSON_VALUE(payload, '$.biaya_prodeo'), '0'), r'[^0-9.-]', '') AS NUMERIC) AS biaya_prodeo,
    COALESCE(JSON_VALUE(payload, '$.status'), 'N/A') AS status_prodeo
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_ecourt_prodeo'
), map_efiling AS (
  SELECT DISTINCT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    SAFE_CAST(JSON_VALUE(payload, '$.efiling_id') AS INT64) AS efiling_id
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara_efiling_id'
)
SELECT
  DATE_TRUNC(p.tgl_daftar, MONTH) AS bulan,
  COUNTIF(COALESCE(p.prodeo_flag, 0) = 1) AS jumlah_perkara_prodeo_flag,
  COUNT(DISTINCT IF(ep.efiling_id IS NOT NULL, p.perkara_id, NULL)) AS jumlah_perkara_prodeo_ecourt,
  ROUND(SUM(COALESCE(ep.biaya_prodeo, 0)), 2) AS total_biaya_prodeo
FROM perkara p
LEFT JOIN map_efiling m USING (perkara_id)
LEFT JOIN ec_prodeo ep USING (efiling_id)
WHERE p.tgl_daftar IS NOT NULL
GROUP BY bulan
ORDER BY bulan;

-- ============================================================================
-- 02) Analisis Sosial Ekonomi
-- ============================================================================

-- 02A. Tingkat Pendidikan & Pekerjaan para pihak perceraian
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_sosial_ekonomi_pihak_cerai` AS
WITH perkara_cerai AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME)) AS tgl_daftar,
    COALESCE(JSON_VALUE(payload, '$.jenis_perkara_nama'), JSON_VALUE(payload, '$.jenis_perkara_text')) AS jenis_perkara
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara'
    AND REGEXP_CONTAINS(LOWER(COALESCE(JSON_VALUE(payload, '$.jenis_perkara_nama'), JSON_VALUE(payload, '$.jenis_perkara_text'))), r'cerai')
), pihak_link AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    SAFE_CAST(JSON_VALUE(payload, '$.pihak_id') AS INT64) AS pihak_id,
    'PIHAK1' AS jenis_pihak
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara_pihak1'
  UNION ALL
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    SAFE_CAST(JSON_VALUE(payload, '$.pihak_id') AS INT64) AS pihak_id,
    'PIHAK2' AS jenis_pihak
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara_pihak2'
), pihak_dim AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.id') AS INT64) AS pihak_id,
    COALESCE(JSON_VALUE(payload, '$.pendidikan'), 'TIDAK DIISI') AS pendidikan,
    COALESCE(JSON_VALUE(payload, '$.pekerjaan'), 'TIDAK DIISI') AS pekerjaan
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_pihak'
)
SELECT
  DATE_TRUNC(pc.tgl_daftar, YEAR) AS tahun,
  pl.jenis_pihak,
  pd.pendidikan,
  pd.pekerjaan,
  COUNT(*) AS jumlah_orang
FROM perkara_cerai pc
JOIN pihak_link pl USING (perkara_id)
LEFT JOIN pihak_dim pd USING (pihak_id)
WHERE pc.tgl_daftar IS NOT NULL
GROUP BY tahun, pl.jenis_pihak, pd.pendidikan, pd.pekerjaan
ORDER BY tahun, jumlah_orang DESC;

-- 02B. Perbandingan Cerai Gugat vs Cerai Talak
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_perbandingan_cerai_gugat_talak` AS
WITH akta AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    DATE(SAFE_CAST(JSON_VALUE(payload, '$.tgl_akta_cerai') AS DATETIME)) AS tgl_akta,
    LOWER(COALESCE(JSON_VALUE(payload, '$.jenis_cerai'), 'lainnya')) AS jenis_cerai
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara_akta_cerai'
), agg AS (
  SELECT
    DATE_TRUNC(tgl_akta, YEAR) AS tahun,
    CASE
      WHEN REGEXP_CONTAINS(jenis_cerai, r'gugat') THEN 'CERAI GUGAT'
      WHEN REGEXP_CONTAINS(jenis_cerai, r'talak') THEN 'CERAI TALAK'
      ELSE 'LAINNYA'
    END AS kategori,
    COUNT(*) AS jumlah_perkara
  FROM akta
  WHERE tgl_akta IS NOT NULL
  GROUP BY tahun, kategori
)
SELECT
  tahun,
  kategori,
  jumlah_perkara,
  ROUND(100 * jumlah_perkara / SUM(jumlah_perkara) OVER (PARTITION BY tahun), 2) AS persentase_tahunan
FROM agg
ORDER BY tahun, kategori;

-- ============================================================================
-- 03) Perlindungan & Legalitas
-- ============================================================================

-- 03A. Tren Perkara Isbat Nikah
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_tren_isbat_nikah` AS
SELECT
  DATE_TRUNC(DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME)), MONTH) AS bulan,
  COUNT(*) AS jumlah_perkara_isbat
FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
WHERE source = 'sipp_hub_perkara'
  AND REGEXP_CONTAINS(LOWER(COALESCE(JSON_VALUE(payload, '$.jenis_perkara_nama'), JSON_VALUE(payload, '$.jenis_perkara_text'))), r'isbat')
  AND DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME)) IS NOT NULL
GROUP BY bulan
ORDER BY bulan;

-- 03B. Sengketa Harta Bersama (Gono-Gini)
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_sengketa_harta_bersama` AS
WITH obj AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    LOWER(COALESCE(JSON_VALUE(payload, '$.obyek_gugatan'), '')) AS obyek_gugatan
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara_obyek_sengketa'
), perkara AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME)) AS tgl_daftar,
    COALESCE(JSON_VALUE(payload, '$.jenis_perkara_nama'), JSON_VALUE(payload, '$.jenis_perkara_text')) AS jenis_perkara
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara'
)
SELECT
  DATE_TRUNC(p.tgl_daftar, YEAR) AS tahun,
  p.jenis_perkara,
  COUNT(DISTINCT p.perkara_id) AS jumlah_perkara
FROM perkara p
JOIN obj o USING (perkara_id)
WHERE p.tgl_daftar IS NOT NULL
  AND REGEXP_CONTAINS(o.obyek_gugatan, r'harta\s+bersama|gono|gini')
GROUP BY tahun, p.jenis_perkara
ORDER BY tahun, jumlah_perkara DESC;

-- 03C. Sengketa Hak Asuh Anak (Hadhanah)
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_sengketa_hadhanah` AS
WITH perkara AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME)) AS tgl_daftar,
    LOWER(COALESCE(JSON_VALUE(payload, '$.jenis_perkara_nama'), JSON_VALUE(payload, '$.jenis_perkara_text'))) AS jenis_perkara
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara'
), anak AS (
  SELECT DISTINCT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    LOWER(COALESCE(JSON_VALUE(payload, '$.diasuh_oleh'), '')) AS diasuh_oleh
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara_anak_pihak'
)
SELECT
  DATE_TRUNC(p.tgl_daftar, YEAR) AS tahun,
  COUNT(DISTINCT p.perkara_id) AS jumlah_perkara_hadhanah
FROM perkara p
LEFT JOIN anak a USING (perkara_id)
WHERE p.tgl_daftar IS NOT NULL
  AND (
    REGEXP_CONTAINS(p.jenis_perkara, r'hadhanah|penguasaan\s+anak|hak\s+asuh')
    OR a.perkara_id IS NOT NULL
  )
GROUP BY tahun
ORDER BY tahun;

-- 03D. Statistik Perkara Bulanan
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_statistik_perkara_bulanan` AS
SELECT
  DATE_TRUNC(DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME)), MONTH) AS bulan,
  COUNT(*) AS jumlah_perkara_masuk
FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
WHERE source = 'sipp_hub_perkara'
  AND DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME)) IS NOT NULL
GROUP BY bulan
ORDER BY bulan;

-- 03E. Penerimaan Perkara Tahunan
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_penerimaan_perkara_tahunan` AS
SELECT
  EXTRACT(YEAR FROM DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME))) AS tahun,
  COUNT(*) AS jumlah_perkara_masuk
FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
WHERE source = 'sipp_hub_perkara'
  AND DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME)) IS NOT NULL
GROUP BY tahun
ORDER BY tahun;

-- 03F. Faktor Perceraian
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_faktor_perceraian` AS
WITH akta AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    DATE(SAFE_CAST(JSON_VALUE(payload, '$.tgl_akta_cerai') AS DATETIME)) AS tgl_akta,
    SAFE_CAST(JSON_VALUE(payload, '$.faktor_perceraian_id') AS INT64) AS faktor_perceraian_id
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara_akta_cerai'
), faktor_ref AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.id') AS INT64) AS faktor_perceraian_id,
    COALESCE(JSON_VALUE(payload, '$.nama'), CONCAT('FAKTOR_ID_', JSON_VALUE(payload, '$.id'))) AS nama_faktor
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_faktor_perceraian'
)
SELECT
  DATE_TRUNC(a.tgl_akta, YEAR) AS tahun,
  COALESCE(fr.nama_faktor, CONCAT('FAKTOR_ID_', CAST(a.faktor_perceraian_id AS STRING))) AS faktor_perceraian,
  COUNT(*) AS jumlah
FROM akta a
LEFT JOIN faktor_ref fr USING (faktor_perceraian_id)
WHERE a.tgl_akta IS NOT NULL
GROUP BY tahun, faktor_perceraian
ORDER BY tahun, jumlah DESC;

-- 03G. Status Putusan
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_status_putusan` AS
SELECT
  DATE_TRUNC(DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_putusan') AS DATETIME)), YEAR) AS tahun,
  COALESCE(JSON_VALUE(payload, '$.status_putusan_nama'), JSON_VALUE(payload, '$.status_putusan_text'), 'TIDAK DIISI') AS status_putusan,
  COUNT(*) AS jumlah
FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
WHERE source = 'sipp_hub_perkara_putusan'
  AND DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_putusan') AS DATETIME)) IS NOT NULL
GROUP BY tahun, status_putusan
ORDER BY tahun, jumlah DESC;

-- 03H. Pemenuhan Hak Ibu dan Anak (proxy monitoring)
-- Proxy: ada data anak + nilai nafkah + status eksekusi
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_pemenuhan_hak_ibu_dan_anak` AS
WITH anak AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    SAFE_CAST(REGEXP_REPLACE(COALESCE(JSON_VALUE(payload, '$.jumlah_nafkah'), '0'), r'[^0-9.-]', '') AS NUMERIC) AS jumlah_nafkah,
    COALESCE(JSON_VALUE(payload, '$.status_eksekusi'), 'N/A') AS status_eksekusi,
    DATE(SAFE_CAST(JSON_VALUE(payload, '$.diinput_tanggal') AS DATETIME)) AS tgl_input
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara_anak_pihak'
)
SELECT
  DATE_TRUNC(COALESCE(tgl_input, CURRENT_DATE()), YEAR) AS tahun,
  status_eksekusi,
  COUNT(DISTINCT perkara_id) AS jumlah_perkara_beranak,
  ROUND(SUM(COALESCE(jumlah_nafkah, 0)), 2) AS total_nafkah_tercatat
FROM anak
GROUP BY tahun, status_eksekusi
ORDER BY tahun, jumlah_perkara_beranak DESC;

-- 03I. Perkara Dispensasi Kawin
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_perkara_dispensasi_kawin` AS
SELECT
  DATE_TRUNC(DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME)), YEAR) AS tahun,
  COUNT(*) AS jumlah_perkara
FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
WHERE source = 'sipp_hub_perkara'
  AND REGEXP_CONTAINS(LOWER(COALESCE(JSON_VALUE(payload, '$.jenis_perkara_nama'), JSON_VALUE(payload, '$.jenis_perkara_text'))), r'dispensasi\s+kawin')
  AND DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME)) IS NOT NULL
GROUP BY tahun
ORDER BY tahun;

-- 03J. Lama Umur Pernikahan (sampai perkara didaftarkan)
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_lama_umur_pernikahan` AS
WITH nikah AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    DATE(SAFE_CAST(JSON_VALUE(payload, '$.tgl_nikah') AS DATETIME)) AS tgl_nikah
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara_data_pernikahan'
), perkara AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME)) AS tgl_daftar,
    COALESCE(JSON_VALUE(payload, '$.jenis_perkara_nama'), JSON_VALUE(payload, '$.jenis_perkara_text')) AS jenis_perkara
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara'
)
SELECT
  DATE_TRUNC(p.tgl_daftar, YEAR) AS tahun,
  p.jenis_perkara,
  COUNT(*) AS jumlah_perkara,
  ROUND(AVG(DATE_DIFF(p.tgl_daftar, n.tgl_nikah, YEAR)), 2) AS rata_tahun_usia_pernikahan,
  ROUND(APPROX_QUANTILES(DATE_DIFF(p.tgl_daftar, n.tgl_nikah, YEAR), 100)[OFFSET(50)], 2) AS median_tahun_usia_pernikahan
FROM perkara p
JOIN nikah n USING (perkara_id)
WHERE p.tgl_daftar IS NOT NULL
  AND n.tgl_nikah IS NOT NULL
  AND p.tgl_daftar >= n.tgl_nikah
GROUP BY tahun, p.jenis_perkara
ORDER BY tahun, p.jenis_perkara;

-- 03K. Range Umur Penggugat/Pemohon
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_range_umur_penggugat_pemohon` AS
WITH perkara AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME)) AS tgl_daftar
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara'
), pihak1 AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    SAFE_CAST(JSON_VALUE(payload, '$.pihak_id') AS INT64) AS pihak_id
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara_pihak1'
), pihak AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.id') AS INT64) AS pihak_id,
    DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_lahir') AS DATETIME)) AS tgl_lahir
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_pihak'
), usia AS (
  SELECT
    DATE_TRUNC(p.tgl_daftar, YEAR) AS tahun,
    DATE_DIFF(p.tgl_daftar, ph.tgl_lahir, YEAR) AS usia_tahun
  FROM perkara p
  JOIN pihak1 p1 USING (perkara_id)
  JOIN pihak ph USING (pihak_id)
  WHERE p.tgl_daftar IS NOT NULL
    AND ph.tgl_lahir IS NOT NULL
    AND p.tgl_daftar >= ph.tgl_lahir
)
SELECT
  tahun,
  CASE
    WHEN usia_tahun < 20 THEN '<20'
    WHEN usia_tahun BETWEEN 20 AND 29 THEN '20-29'
    WHEN usia_tahun BETWEEN 30 AND 39 THEN '30-39'
    WHEN usia_tahun BETWEEN 40 AND 49 THEN '40-49'
    WHEN usia_tahun BETWEEN 50 AND 59 THEN '50-59'
    ELSE '60+'
  END AS range_umur,
  COUNT(*) AS jumlah
FROM usia
GROUP BY tahun, range_umur
ORDER BY tahun, range_umur;

-- 03L. Perkara per Kecamatan (berdasarkan pihak penggugat/pemohon)
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_perkara_per_kecamatan` AS
WITH perkara AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME)) AS tgl_daftar
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara'
), pihak1 AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    SAFE_CAST(JSON_VALUE(payload, '$.pihak_id') AS INT64) AS pihak_id
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara_pihak1'
), pihak AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.id') AS INT64) AS pihak_id,
    COALESCE(NULLIF(JSON_VALUE(payload, '$.kecamatan'), ''), 'TIDAK DIISI') AS kecamatan
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_pihak'
)
SELECT
  DATE_TRUNC(p.tgl_daftar, YEAR) AS tahun,
  ph.kecamatan,
  COUNT(*) AS jumlah_perkara
FROM perkara p
JOIN pihak1 p1 USING (perkara_id)
LEFT JOIN pihak ph USING (pihak_id)
WHERE p.tgl_daftar IS NOT NULL
GROUP BY tahun, ph.kecamatan
ORDER BY tahun, jumlah_perkara DESC;

-- 03M. Perkara Perceraian PNS/ASN
CREATE OR REPLACE VIEW `lawang-sewu-490507.sipp_dataset.vw_perkara_perceraian_pns_asn` AS
WITH perkara_cerai AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    DATE(SAFE_CAST(JSON_VALUE(payload, '$.tanggal_pendaftaran') AS DATETIME)) AS tgl_daftar,
    COALESCE(JSON_VALUE(payload, '$.jenis_perkara_nama'), JSON_VALUE(payload, '$.jenis_perkara_text')) AS jenis_perkara
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara'
    AND REGEXP_CONTAINS(LOWER(COALESCE(JSON_VALUE(payload, '$.jenis_perkara_nama'), JSON_VALUE(payload, '$.jenis_perkara_text'))), r'cerai')
), pihak_link AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    SAFE_CAST(JSON_VALUE(payload, '$.pihak_id') AS INT64) AS pihak_id,
    'PENGGUGAT/PEMOHON' AS posisi
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara_pihak1'
  UNION ALL
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.perkara_id') AS INT64) AS perkara_id,
    SAFE_CAST(JSON_VALUE(payload, '$.pihak_id') AS INT64) AS pihak_id,
    'TERGUGAT/TERMOHON' AS posisi
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_perkara_pihak2'
), pihak AS (
  SELECT
    SAFE_CAST(JSON_VALUE(payload, '$.id') AS INT64) AS pihak_id,
    LOWER(COALESCE(JSON_VALUE(payload, '$.pekerjaan'), '')) AS pekerjaan
  FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
  WHERE source = 'sipp_hub_pihak'
)
SELECT
  DATE_TRUNC(pc.tgl_daftar, YEAR) AS tahun,
  pl.posisi,
  COUNT(*) AS jumlah_orang
FROM perkara_cerai pc
JOIN pihak_link pl USING (perkara_id)
JOIN pihak ph USING (pihak_id)
WHERE pc.tgl_daftar IS NOT NULL
  AND REGEXP_CONTAINS(ph.pekerjaan, r'\bpns\b|\basn\b|pegawai\s+negeri|aparatur\s+sipil\s+negara')
GROUP BY tahun, pl.posisi
ORDER BY tahun, pl.posisi;

-- ============================================================================
-- END OF CHART PACK
-- ============================================================================
