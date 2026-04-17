@extends('guestbook.layout')

@section('title', 'Register Buku Tamu')

@php
    $isEmbedded = request()->boolean('embedded');
@endphp

@push('styles')
<style>
    .guest-shell {
        position: relative;
        min-height: calc(100dvh - 52px);
        background: linear-gradient(115deg, rgba(248, 237, 215, 0.58), rgba(231, 243, 255, 0.64)), #f8fbff;
        padding: clamp(1rem, 2vw, 1.6rem) 0 1.4rem;
        overflow-x: clip;
    }

    .guest-shell::before {
        content: "";
        position: absolute;
        inset: 0;
        background: url('{{ asset('guestbook/foto-bg.png') }}') center center / cover no-repeat;
        opacity: 0.18;
        pointer-events: none;
    }

    .guest-shell > .container-fluid {
        position: relative;
        z-index: 1;
    }

    .guest-card {
        border-radius: 1.5rem;
        box-shadow: 0 24px 54px rgba(18, 38, 63, 0.12);
        background: rgba(255, 255, 255, 0.72);
        backdrop-filter: blur(14px);
        border: 1px solid rgba(255, 255, 255, 0.48);
    }

    .camera-frame {
        width: 100%;
        max-width: 560px;
        aspect-ratio: 4 / 3;
        border-radius: 0.85rem;
        border: 3px solid #dbe7ff;
        background: #101725;
        object-fit: cover;
        box-shadow: 0 18px 36px rgba(16, 30, 48, 0.18);
    }

    .camera-stage {
        display: flex;
        justify-content: center;
    }

    .section-note {
        color: #57708b;
        font-size: 0.95rem;
    }

    .form-control.form-control-lg {
        min-height: 3.7rem;
        border-radius: 1rem;
        border-color: rgba(143, 173, 204, 0.55);
        background: rgba(255, 255, 255, 0.82);
    }

    .btn-glass {
        position: relative;
        border: 1px solid rgba(255, 255, 255, 0.45);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.38), rgba(255, 255, 255, 0.12));
        color: #0f2747;
        backdrop-filter: blur(8px);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.68), 0 10px 24px rgba(19, 38, 66, 0.14);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        overflow: hidden;
    }

    .btn-glass:hover,
    .btn-glass:focus {
        color: #0f2747;
        transform: translateY(-2px);
        border-color: rgba(96, 174, 255, 0.82);
    }

    .badge-step {
        width: 38px;
        height: 38px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(135deg, #1f6feb, #49a2ff);
    }

    .action-stack {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.9rem;
        margin-top: 1.2rem;
    }

    .control-button {
        width: 78px;
        height: 78px;
        border-radius: 999px;
        border-width: 4px;
    }

    .submit-text-button {
        min-width: 220px;
        height: 50px;
        border-radius: 999px;
        font-weight: 700;
    }

    .camera-toolbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .camera-power-button {
        min-width: 220px;
        min-height: 52px;
        border-radius: 999px;
        font-weight: 700;
    }

    .camera-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 0.95rem;
        border-radius: 999px;
        background: rgba(18, 51, 79, 0.08);
        color: #284966;
        font-size: 0.92rem;
        border: 1px solid rgba(18, 51, 79, 0.08);
    }

    .camera-heat-cool {
        background: rgba(61, 139, 201, 0.08);
        color: #275780;
    }

    .camera-heat-warm {
        background: rgba(232, 175, 65, 0.16);
        color: #855710;
    }

    .camera-heat-hot {
        background: rgba(214, 101, 76, 0.16);
        color: #8d2f1c;
    }

    .bypass-toggle {
        position: fixed;
        right: 14px;
        bottom: 14px;
        z-index: 1080;
        font-size: 0.85rem;
        border-radius: 999px;
        padding: 0.45rem 0.8rem;
        text-transform: lowercase;
    }

    .guest-shell.embedded-lite {
        min-height: 100dvh;
        padding-top: 0.5rem;
        background: #f4f8fc;
    }

    .guest-shell.embedded-lite::before {
        opacity: 0;
    }

    .guest-shell.embedded-lite .guest-card {
        border-radius: 1rem;
        box-shadow: 0 12px 28px rgba(18, 38, 63, 0.1);
        background: #ffffff;
        backdrop-filter: none;
        border-color: rgba(143, 173, 204, 0.25);
    }

    .guest-shell.embedded-lite .form-control.form-control-lg {
        min-height: 3.3rem;
    }

    .embed-lite-title {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.7rem;
        padding: 0.5rem 0.8rem;
        border-radius: 999px;
        background: rgba(18, 51, 79, 0.06);
        color: #1e4364;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    @media (max-width: 768px) {
        .guest-shell {
            padding: 0.75rem 0 1.1rem;
        }

        .guest-shell.embedded-lite {
            padding-top: 0.45rem;
        }

        .camera-frame {
            max-width: 100%;
            aspect-ratio: 3 / 4;
        }

        .camera-toolbar {
            align-items: stretch;
        }

        .camera-power-button,
        .submit-text-button {
            min-width: 100%;
        }

        .action-stack {
            gap: 0.65rem;
        }

        .control-button {
            width: 64px;
            height: 64px;
            border-width: 3px;
        }

        .bypass-toggle {
            right: 10px;
            bottom: calc(10px + env(safe-area-inset-bottom));
            font-size: 0.78rem;
            padding: 0.35rem 0.7rem;
        }
    }
</style>
@endpush

@section('content')
<div class="guest-shell {{ $isEmbedded ? 'embedded-lite' : '' }}">
    <div class="container-fluid px-3 px-md-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xxl-11">
                @if($isEmbedded)
                <div class="embed-lite-title">
                    <i class="bi bi-person-vcard"></i> Form Pendopo Cepat
                </div>
                @endif

                @if(!$isEmbedded)
                <section class="batik-hero mb-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
                        <div>
                            <div class="batik-kicker">Pengadilan Agama Semarang</div>
                            <h1 class="batik-hero-title">Register Buku Tamu</h1>
                            <p class="batik-hero-subtitle">Silakan isi data lengkap, ambil foto, lalu simpan registrasi kunjungan.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                            <span class="batik-chip"><i class="bi bi-stars"></i> Layanan Front Desk</span>
                            <span class="batik-chip"><i class="bi bi-shield-check"></i> Responsif dan Aman</span>
                            <a href="{{ route('lawangsewu.guestbook.list', ['period' => 'all']) }}" class="btn btn-glass">Lihat Daftar Tamu</a>
                        </div>
                    </div>
                </section>
                @endif

                <div class="card guest-card">
                    <div class="card-body p-3 p-lg-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                            <div>
                                <h2 class="h4 mb-1">Formulir Kunjungan</h2>
                                <p class="section-note mb-0">Lengkapi data terlebih dahulu, lalu ambil foto untuk menyimpan kunjungan.</p>
                            </div>
                            @if(!$isEmbedded)
                            <span class="batik-chip text-dark" style="background: rgba(18, 51, 79, 0.06); border-color: rgba(18, 51, 79, 0.08); color: #12334f;">
                                <i class="bi bi-camera"></i> Kamera aktif setelah data siap
                            </span>
                            @endif
                        </div>

                        <div id="formAlert" class="alert alert-danger d-none" role="alert"></div>

                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="badge-step">1</span>
                                    <div>
                                        <strong>Data Tamu</strong>
                                        <div class="section-note">Isi nama, jabatan, instansi, dan keperluan.</div>
                                    </div>
                                </div>

                                <form id="form-tamu" class="vstack gap-3">
                                    @csrf
                                    <input type="hidden" id="id_tamu" name="id" value="{{ $idTamu }}">

                                    <input type="text" class="form-control form-control-lg" id="nama" name="nama" placeholder="Nama / Name" autocomplete="off" onkeyup="tombolaktif();">
                                    <input type="text" class="form-control form-control-lg" id="jabatan" name="jabatan" placeholder="Pekerjaan & Jabatan" autocomplete="off" onkeyup="tombolaktif();">
                                    <select class="form-control form-control-lg" id="kategori_instansi" name="kategori_instansi" autocomplete="off" onchange="muatPilihanDetailInstansi(); sinkronkanInstansi(); tombolaktif();">
                                        <option value="">Pilih Kategori Instansi/Satuan</option>
                                        <option value="MAHKAMAH_AGUNG">1. Mahkamah Agung</option>
                                        <option value="INSTANSI_PERUSAHAAN">2. Instansi/Perusahaan</option>
                                        <option value="UNIVERSITAS_SEKOLAH">3. Universitas/Sekolah</option>
                                        <option value="PERSEORANGAN">4. Perseorangan</option>
                                    </select>
                                    <select class="form-control form-control-lg" id="instansi_pilihan" autocomplete="off" onchange="sinkronkanInstansi(); tombolaktif();" disabled>
                                        <option value="">Pilih detail instansi/satuan</option>
                                    </select>
                                    <input type="text" class="form-control form-control-lg" id="instansi_custom" placeholder="Isi instansi lain" autocomplete="off" style="display:none;" onkeyup="sinkronkanInstansi(); tombolaktif();">
                                    <input type="hidden" id="instansi" name="instansi" value="">
                                    <input type="text" class="form-control form-control-lg" id="keperluan" name="keperluan" placeholder="Keperluan" autocomplete="off" onkeyup="tombolaktif();">
                                </form>
                            </div>

                            <div class="col-lg-6 text-center">
                                <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
                                    <span class="badge-step">2</span>
                                    <div class="text-start">
                                        <strong>Foto Tamu</strong>
                                        <div class="section-note">Gunakan kamera utama. Bypass tersedia bila diperlukan.</div>
                                    </div>
                                </div>

                                <div class="camera-stage">
                                    <video id="video" class="camera-frame" autoplay playsinline style="display:none;"></video>
                                    <canvas id="canvas" class="camera-frame" width="560" height="420" style="display:none;"></canvas>
                                </div>

                                <div id="cameraHint" class="alert alert-info mt-3 mb-0" style="display:none;"></div>

                                <div class="camera-toolbar">
                                    <button class="btn btn-glass camera-power-button" type="button" id="powerCamera" disabled>
                                        <i class="bi bi-camera-video"></i> Nyalakan Kamera
                                    </button>
                                    <button class="btn btn-glass" type="button" id="switchCamera" style="display:none;">
                                        <i class="bi bi-arrow-repeat"></i> Ganti Kamera
                                    </button>
                                    <span class="camera-status-pill" id="cameraStatus">
                                        <i class="bi bi-pause-circle"></i> Kamera standby
                                    </span>
                                    <span class="camera-status-pill camera-heat-cool" id="cameraHeatStatus">
                                        <i class="bi bi-thermometer-low"></i> Estimasi suhu: dingin
                                    </span>
                                </div>

                                <div id="uploadFallback" class="mt-3" style="display:none; max-width:560px; margin-inline:auto;">
                                    <label class="form-label fw-semibold text-start d-block" for="fotoFile">Upload Foto</label>
                                    <input type="file" id="fotoFile" class="form-control" accept="image/*" capture="environment">
                                </div>

                                <div class="action-stack">
                                    <button class="btn btn-glass control-button" type="button" id="snap" disabled style="display: none;">
                                        <i class="bi bi-camera-fill fs-4"></i>
                                    </button>

                                    <button class="btn btn-glass control-button" type="button" id="retake" style="display:none;">
                                        <i class="bi bi-arrow-clockwise fs-4"></i>
                                    </button>

                                    <button class="btn btn-glass control-button" type="button" id="simpandata" style="display:none;">
                                        <i class="bi bi-check2 fs-3"></i>
                                    </button>
                                </div>

                                <div class="mt-3">
                                    <button class="btn btn-glass submit-text-button" type="button" id="submitTextBtn" style="display:none;">
                                        Submit Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(!$isEmbedded)
<button type="button" id="toggleBypass" class="btn btn-glass bypass-toggle">bypas foto</button>
@endif
@if($isEmbedded)
<button type="button" id="toggleBypass" class="btn btn-glass bypass-toggle" style="position: static; margin-top: 0.65rem;">bypas foto</button>
@endif

<div class="modal fade" id="modalSuccess" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content text-center">
      <div class="modal-body p-5">
        <h3 class="text-success mb-3">Terima Kasih!</h3>
        <p>Data Anda telah tersimpan.</p>
        <p>Anda adalah tamu ke-<strong id="tamuKe"></strong>.</p>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
var isCameraActive = false;
var video = document.getElementById('video');
var canvas = document.getElementById('canvas');
var context = canvas.getContext('2d');
var formAlert = document.getElementById('formAlert');
var cameraHint = document.getElementById('cameraHint');
var uploadFallback = document.getElementById('uploadFallback');
var fotoFileInput = document.getElementById('fotoFile');
var toggleBypassButton = document.getElementById('toggleBypass');
var powerCameraButton = document.getElementById('powerCamera');
var switchCameraButton = document.getElementById('switchCamera');
var kategoriInstansiSelect = document.getElementById('kategori_instansi');
var instansiSelect = document.getElementById('instansi_pilihan');
var instansiCustom = document.getElementById('instansi_custom');
var instansiHidden = document.getElementById('instansi');
var cameraStatus = document.getElementById('cameraStatus');
var cameraHeatStatus = document.getElementById('cameraHeatStatus');
var snapButton = document.getElementById('snap');
var retakeButton = document.getElementById('retake');
var saveButton = document.getElementById('simpandata');
var submitTextButton = document.getElementById('submitTextBtn');
var fotoBase64 = '';
var currentVideoStream = null;
var standbyTimer = null;
var heatInterval = null;
var standbyDurationMs = 10000;
var cameraStartedAt = null;
var isMobileDevice = /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent || '');
var preferredFacingMode = isMobileDevice ? 'environment' : 'user';
var successModal = new bootstrap.Modal(document.getElementById('modalSuccess'));
var instansiOptionsByCategory = @json($instansiOptionsByCategory ?? []);

function tampilkanError(pesan) {
    formAlert.textContent = pesan;
    formAlert.classList.remove('d-none');
}

function resetError() {
    formAlert.classList.add('d-none');
    formAlert.textContent = '';
}

function tampilkanHint(pesan, tipe) {
    cameraHint.className = 'alert mt-3 mb-0 alert-' + (tipe || 'info');
    cameraHint.textContent = pesan;
    cameraHint.style.display = 'block';
}

function sembunyikanHint() {
    cameraHint.style.display = 'none';
    cameraHint.textContent = '';
}

function tampilkanFallbackUpload() {
    uploadFallback.style.display = 'none';
    tampilkanHint('Kamera tidak aktif. Gunakan tombol bypas foto bila ingin upload manual.', 'warning');
}

function perbaruiStatusKamera(aktif, pesan) {
    cameraStatus.innerHTML = aktif
        ? '<i class="bi bi-record-circle"></i> ' + pesan
        : '<i class="bi bi-pause-circle"></i> ' + pesan;
}

function perbaruiIndikatorPanas() {
    if (!cameraHeatStatus) {
        return;
    }

    if (!cameraStartedAt || !isCameraActive) {
        cameraHeatStatus.className = 'camera-status-pill camera-heat-cool';
        cameraHeatStatus.innerHTML = '<i class="bi bi-thermometer-low"></i> Estimasi suhu: dingin';
        return;
    }

    var elapsedSeconds = Math.floor((Date.now() - cameraStartedAt) / 1000);
    if (elapsedSeconds >= 20) {
        cameraHeatStatus.className = 'camera-status-pill camera-heat-hot';
        cameraHeatStatus.innerHTML = '<i class="bi bi-thermometer-high"></i> Estimasi suhu: hangat tinggi';
        return;
    }

    if (elapsedSeconds >= 8) {
        cameraHeatStatus.className = 'camera-status-pill camera-heat-warm';
        cameraHeatStatus.innerHTML = '<i class="bi bi-thermometer-half"></i> Estimasi suhu: hangat';
        return;
    }

    cameraHeatStatus.className = 'camera-status-pill camera-heat-cool';
    cameraHeatStatus.innerHTML = '<i class="bi bi-thermometer-low"></i> Estimasi suhu: dingin';
}

function mulaiMonitorPanas() {
    if (heatInterval) {
        window.clearInterval(heatInterval);
    }

    perbaruiIndikatorPanas();
    heatInterval = window.setInterval(perbaruiIndikatorPanas, 1000);
}

function hentikanMonitorPanas() {
    if (heatInterval) {
        window.clearInterval(heatInterval);
        heatInterval = null;
    }
    cameraStartedAt = null;
    perbaruiIndikatorPanas();
}

function resetStandbyTimer() {
    if (standbyTimer) {
        window.clearTimeout(standbyTimer);
    }

    if (!isCameraActive) {
        standbyTimer = null;
        return;
    }

    standbyTimer = window.setTimeout(function () {
        shutDownCameraHardware('Kamera dimatikan otomatis saat standby untuk menjaga perangkat tetap awet.');
    }, standbyDurationMs);
}

function bidangWajibTerisi() {
    return document.getElementById('nama').value.trim() !== ''
        && document.getElementById('jabatan').value.trim() !== ''
        && document.getElementById('kategori_instansi').value.trim() !== ''
        && document.getElementById('instansi').value.trim() !== ''
        && document.getElementById('keperluan').value.trim() !== '';
}

function muatPilihanDetailInstansi() {
    var selectedCategory = kategoriInstansiSelect.value || '';
    instansiSelect.innerHTML = '';

    var defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'Pilih detail instansi/satuan';
    instansiSelect.appendChild(defaultOption);

    if (!selectedCategory) {
        instansiSelect.disabled = true;
        instansiHidden.value = '';
        instansiCustom.style.display = 'none';
        instansiCustom.value = '';
        return;
    }

    var options = (instansiOptionsByCategory[selectedCategory] || []).slice();
    options.sort(function (a, b) {
        return String(a).localeCompare(String(b), 'id', { sensitivity: 'base' });
    });

    options.forEach(function (value) {
        var opt = document.createElement('option');
        opt.value = value;
        opt.textContent = value;
        instansiSelect.appendChild(opt);
    });

    var otherOpt = document.createElement('option');
    otherOpt.value = '__LAINNYA__';
    otherOpt.textContent = 'Lainnya (isi manual)';
    instansiSelect.appendChild(otherOpt);

    instansiSelect.disabled = false;
}

function sinkronkanInstansi() {
    if (instansiSelect.value === '__LAINNYA__') {
        instansiCustom.style.display = 'block';
        instansiHidden.value = (instansiCustom.value || '').trim();
        return;
    }

    instansiCustom.style.display = 'none';
    instansiCustom.value = '';
    instansiHidden.value = (instansiSelect.value || '').trim();
}

function tombolaktif(){
    sinkronkanKontrolKamera();
}

function sinkronkanKontrolKamera() {
    var siapKamera = bidangWajibTerisi();
    powerCameraButton.disabled = !siapKamera;

    if (!siapKamera) {
        powerCameraButton.innerHTML = '<i class="bi bi-lock"></i> Lengkapi Data Dulu';
        snapButton.disabled = true;
        if (isCameraActive) {
            shutDownCameraHardware('Kamera dimatikan karena data tamu belum lengkap.');
        }
        return;
    }

    switchCameraButton.style.display = isMobileDevice ? 'inline-flex' : 'none';

    if (isCameraActive) {
        powerCameraButton.innerHTML = '<i class="bi bi-power"></i> Matikan Kamera';
        snapButton.style.display = 'inline-block';
        snapButton.disabled = false;
        perbaruiStatusKamera(true, 'Kamera aktif');
        return;
    }

    powerCameraButton.innerHTML = '<i class="bi bi-camera-video"></i> Nyalakan Kamera';
    if (!fotoBase64) {
        perbaruiStatusKamera(false, 'Kamera standby');
    }
}

function stopCameraStream() {
    if (standbyTimer) {
        window.clearTimeout(standbyTimer);
        standbyTimer = null;
    }

    if (currentVideoStream && typeof currentVideoStream.getTracks === 'function') {
        currentVideoStream.getTracks().forEach(function (track) {
            track.stop();
        });
    }

    currentVideoStream = null;
    isCameraActive = false;
    hentikanMonitorPanas();
    video.srcObject = null;
}

function requestCameraStream() {
    return navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: { ideal: preferredFacingMode },
            width: { ideal: 1280 },
            height: { ideal: 720 }
        },
        audio: false
    });
}

function shutDownCameraHardware(pesan) {
    stopCameraStream();
    video.style.display = 'none';
    snapButton.style.display = 'none';
    snapButton.disabled = true;
    if (!fotoBase64) {
        canvas.style.display = 'none';
    }
    sinkronkanKontrolKamera();
    if (pesan) {
        tampilkanHint(pesan, 'secondary');
    }
}

function mulaicam(){
    if (isCameraActive) {
        stopCameraStream();
    }

    requestCameraStream().then(function(stream) {
        video.srcObject = stream;
        currentVideoStream = stream;
        video.play();
        isCameraActive = true;
        cameraStartedAt = Date.now();
        video.style.display = 'block';
        canvas.style.display = 'none';
        snapButton.style.display = 'inline-block';
        snapButton.disabled = false;
        retakeButton.style.display = 'none';
        saveButton.style.display = 'none';
        submitTextButton.style.display = 'none';
        uploadFallback.style.display = 'none';
        perbaruiStatusKamera(true, 'Kamera aktif');
        sembunyikanHint();
        mulaiMonitorPanas();
        resetStandbyTimer();
    }).catch(function(err){
        console.error(err);
        shutDownCameraHardware();
        tampilkanFallbackUpload();
    });
}

snapButton.addEventListener('click', function() {
    context.drawImage(video, 0, 0, 560, 420);
    video.style.display = 'none';
    snapButton.style.display = 'none';
    canvas.style.display = 'block';
    retakeButton.style.display = 'inline-block';
    saveButton.style.display = 'inline-block';
    submitTextButton.style.display = 'inline-block';
    fotoBase64 = canvas.toDataURL('image/jpeg');
    shutDownCameraHardware();
    perbaruiStatusKamera(false, 'Kamera berhenti setelah foto diambil');
    resetError();
});

retakeButton.addEventListener('click', function() {
    canvas.style.display = 'none';
    retakeButton.style.display = 'none';
    saveButton.style.display = 'none';
    submitTextButton.style.display = 'none';
    fotoBase64 = '';
    if (bidangWajibTerisi()) {
        mulaicam();
    } else {
        sinkronkanKontrolKamera();
    }
});

fotoFileInput.addEventListener('change', function (event) {
    var file = event.target.files && event.target.files[0];
    if (!file) {
        return;
    }

    if (!file.type.match(/^image\//)) {
        tampilkanError('File harus berupa gambar.');
        return;
    }

    var reader = new FileReader();
    reader.onload = function (e) {
        var img = new Image();
        img.onload = function () {
            var targetW = 560;
            var targetH = 420;
            context.fillStyle = '#101725';
            context.fillRect(0, 0, targetW, targetH);
            var ratio = Math.min(targetW / img.width, targetH / img.height);
            var drawW = img.width * ratio;
            var drawH = img.height * ratio;
            var dx = (targetW - drawW) / 2;
            var dy = (targetH - drawH) / 2;
            context.drawImage(img, dx, dy, drawW, drawH);
            fotoBase64 = canvas.toDataURL('image/jpeg', 0.92);
            canvas.style.display = 'block';
            video.style.display = 'none';
            retakeButton.style.display = 'none';
            snapButton.style.display = 'none';
            saveButton.style.display = 'inline-block';
            submitTextButton.style.display = 'inline-block';
            resetError();
            perbaruiStatusKamera(false, 'Foto dipilih dari perangkat');
            tampilkanHint('Foto berhasil dipilih. Anda bisa langsung submit data.', 'success');
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
});

function kirimDataTamu() {
    if (!fotoBase64) {
        tampilkanError('Ambil foto atau upload foto terlebih dahulu sebelum menyimpan data.');
        return;
    }

    resetError();
    $('#simpandata, #submitTextBtn').prop('disabled', true);
    var formData = new FormData($('#form-tamu')[0]);
    formData.append('foto', fotoBase64);

    $.ajax({
        url: '{{ route('lawangsewu.guestbook.store') }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response){
            if(response.status === 'success'){
                stopCameraStream();
                $('#tamuKe').text(response.jumlah);
                successModal.show();
                setTimeout(function(){
                    window.location.reload();
                }, 1500);
            } else {
                tampilkanError(response.message || 'Terjadi kesalahan saat menyimpan data.');
                $('#simpandata, #submitTextBtn').prop('disabled', false);
            }
        },
        error: function(xhr){
            var response = xhr.responseJSON || {};
            var message = response.message || 'Gagal menyimpan data. Silakan coba lagi.';
            if (response.errors) {
                var daftarError = Object.values(response.errors).join(' ');
                if (daftarError) {
                    message = daftarError;
                }
            }

            tampilkanError(message);
            $('#simpandata, #submitTextBtn').prop('disabled', false);
        }
    });
}

saveButton.addEventListener('click', kirimDataTamu);
submitTextButton.addEventListener('click', kirimDataTamu);

powerCameraButton.addEventListener('click', function () {
    if (isCameraActive) {
        shutDownCameraHardware('Kamera dimatikan manual untuk menghemat perangkat.');
        return;
    }

    if (!bidangWajibTerisi()) {
        tampilkanError('Lengkapi data tamu terlebih dahulu sebelum menyalakan kamera.');
        return;
    }

    mulaicam();
});

switchCameraButton.addEventListener('click', function () {
    preferredFacingMode = preferredFacingMode === 'environment' ? 'user' : 'environment';
    if (isCameraActive) {
        mulaicam();
    }
});

toggleBypassButton.addEventListener('click', function() {
    if (uploadFallback.style.display === 'block') {
        uploadFallback.style.display = 'none';
        return;
    }

    uploadFallback.style.display = 'block';
    submitTextButton.style.display = 'inline-block';
    tampilkanHint('Mode bypas aktif. Pilih foto dari perangkat Anda.', 'info');
});

video.addEventListener('playing', resetStandbyTimer);
video.addEventListener('click', resetStandbyTimer);
video.addEventListener('touchstart', resetStandbyTimer, { passive: true });

['nama', 'jabatan', 'kategori_instansi', 'instansi_pilihan', 'instansi_custom', 'keperluan'].forEach(function (fieldId) {
    document.getElementById(fieldId).addEventListener('focus', function () {
        if (isCameraActive) {
            shutDownCameraHardware('Kamera dimatikan sementara saat Anda mengedit data.');
        }
    });
});

muatPilihanDetailInstansi();
sinkronkanInstansi();
sinkronkanKontrolKamera();
perbaruiIndikatorPanas();

window.addEventListener('beforeunload', function () {
    shutDownCameraHardware();
});
</script>
@endpush
