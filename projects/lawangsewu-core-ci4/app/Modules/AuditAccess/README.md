# AuditAccess Module Staging

Modul ini menyiapkan audit trail minimum untuk shell portal Lawangsewu.

## Scope

- log request route staging
- catat resolved path dan status response
- simpan audit ke `writable/logs` staging

## Boundary Penting

- hanya menulis ke area staging
- tidak mengubah log produksi aktif
- belum menangani audit bisnis yang kompleks

## Target Refactor

1. sediakan logger JSONL sederhana untuk shell runtime
2. catat request inti dan redirect auth
3. siapkan fondasi audit akses portal sebelum runtime penuh dipasang