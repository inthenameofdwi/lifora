<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}
require_once '../koneksi.php';

// Generate Nomor RM Otomatis (Format: RM-YYYY-XXXXXX)
$year = date('Y');
$stmtMax = $pdo->query("SELECT MAX(id) as max_id FROM pasien");
$rowMax = $stmtMax->fetch();
$next_id = ($rowMax['max_id'] ?? 0) + 1;
$no_rm_auto = "RM-" . $year . "-" . str_pad($next_id, 6, '0', STR_PAD_LEFT);

$pesan_error = '';

// Proses Simpan Data Pasien Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_pasien'])) {
    // Ambil data dari form
    $no_rm          = trim($_POST['no_rm']);
    $nik            = trim($_POST['nik']);
    $nama_lengkap   = trim($_POST['nama_lengkap']);
    $tempat_lahir   = trim($_POST['tempat_lahir']);
    $tanggal_lahir  = trim($_POST['tanggal_lahir']) ?: null;
    $jenis_kelamin  = trim($_POST['jenis_kelamin'] ?? 'Laki-laki');
    $nama_ibu       = trim($_POST['nama_ibu']);
    $no_hp          = trim($_POST['no_hp']);
    $alamat_ktp     = trim($_POST['alamat_ktp']);
    $rt             = trim($_POST['rt']);
    $rw             = trim($_POST['rw']);
    $kode_pos       = trim($_POST['kode_pos']);
    $jenis_penjamin = trim($_POST['jenis_penjamin'] ?? 'BPJS Kesehatan (PBI)');
    $no_bpjs        = trim($_POST['no_bpjs']);
    $faskes_asal    = trim($_POST['faskes_asal']);
    $nama_pj        = trim($_POST['nama_pj']);
    $hubungan_pj    = trim($_POST['hubungan_pj']);
    $no_darurat     = trim($_POST['no_darurat']);

    // Hitung Usia & Kategori
    $usia = 0;
    $kategori_usia = 'Dewasa';
    if (!empty($tanggal_lahir)) {
        $birthDate = new DateTime($tanggal_lahir);
        $today = new DateTime('today');
        $usia = $birthDate->diff($today)->y;
        if ($usia >= 60) {
            $kategori_usia = 'Lansia';
        } elseif ($usia <= 12) {
            $kategori_usia = 'Anak';
        }
    }

    // Inisial Nama (2 huruf depan)
    $words = explode(' ', trim($nama_lengkap));
    if (count($words) >= 2) {
        $inisial = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    } else {
        $inisial = strtoupper(substr($nama_lengkap, 0, 2));
    }

    // Domisili Wilayah (HTML string)
    $domisili_wilayah = "RT " . htmlspecialchars($rt) . " / RW " . htmlspecialchars($rw) . 
                        "<br><span class=\"text-muted fs-8\">" . htmlspecialchars($alamat_ktp) . "</span>";

    // Jenis Jaminan Ringkas
    $jenis_jaminan = 'Umum / Mandiri';
    if (strpos($jenis_penjamin, 'BPJS') !== false) {
        $jenis_jaminan = 'BPJS Kesehatan';
    } elseif (strpos($jenis_penjamin, 'Asuransi') !== false) {
        $jenis_jaminan = 'Asuransi Lain';
    }

    $nomor_kartu = !empty($no_bpjs) ? $no_bpjs : 'Bayar Langsung';
    $tgl_sekarang = date('Y-m-d');

    try {
        $sql = "INSERT INTO pasien 
            (no_rm, nik_identitas, jenis_identitas, nama_lengkap, inisial, tempat_lahir, tanggal_lahir, jenis_kelamin, nama_ibu_kandung, no_hp, alamat_ktp, rt, rw, kode_pos, jenis_penjamin, no_kartu_bpjs, faskes_asal, nama_penanggung_jawab, hubungan_penanggung_jawab, no_telp_darurat, usia, kategori_usia, jenis_jaminan, nomor_kartu, domisili_wilayah, kunjungan_terakhir, poli_terakhir) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $no_rm, $nik, 'E-KTP Terverifikasi', $nama_lengkap, $inisial, $tempat_lahir, $tanggal_lahir, 
            $jenis_kelamin, $nama_ibu, $no_hp, $alamat_ktp, $rt, $rw, $kode_pos, 
            $jenis_penjamin, $no_bpjs, $faskes_asal, $nama_pj, $hubungan_pj, $no_darurat, 
            $usia, $kategori_usia, $jenis_jaminan, $nomor_kartu, $domisili_wilayah, $tgl_sekarang, 'Poli Umum'
        ]);

        $_SESSION['pesan'] = "Data pasien berhasil didaftarkan!";
        header("Location: kelola_pasien.php");
        exit;
    } catch (PDOException $e) {
        $pesan_error = "Gagal mendaftarkan pasien: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../layout/components/admin/head.php'; ?>
</head>
<body>

<div class="d-flex min-vh-100">
    <!-- Include Sidebar -->
    <?php include '../layout/components/admin/sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-grow-1 d-flex flex-column" style="min-width: 0;">
        
        <!-- Include Topbar -->
        <?php include '../layout/components/admin/topbar.php'; ?>

        <div class="container-fluid py-4 px-4 flex-grow-1 d-flex flex-column gap-4">
            
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 fs-7">
                    <li class="breadcrumb-item"><a href="dashboard_admin.php" class="text-decoration-none text-muted"><i class="fa-solid fa-house-chimney me-1"></i> Beranda</a></li>
                    <li class="breadcrumb-item"><a href="kelola_pasien.php" class="text-decoration-none text-muted">Kelola Pasien</a></li>
                    <li class="breadcrumb-item active text-lifora-primary fw-semibold" aria-current="page">Tambah Pasien Baru</li>
                </ol>
            </nav>

            <!-- Error Alert -->
            <?php if (!empty($pesan_error)): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-4" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($pesan_error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="d-flex flex-column gap-4">

                <!-- Header Form -->
                <div class="card border-0 shadow-sm rounded-4 hero-card">
                    <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2 fs-8 text-muted fw-semibold">
                                <span class="badge bg-lifora-primary-soft text-lifora-primary-dark px-2 py-1">LOKET PENDAFTARAN 01</span>
                                <span class="bg-secondary rounded-circle d-inline-block" style="width: 4px; height: 4px;"></span>
                                Mode Input: Pasien Baru RME
                            </div>
                            <h1 class="fw-bold text-dark mb-2" style="letter-spacing: -0.02em;">Formulir Registrasi & Pembaruan Data Pasien</h1>
                            <p class="text-muted mb-0 fs-6" style="max-width: 600px; line-height: 1.5;">
                                Lengkapi identitas primer, alamat domisili, dan kepesertaan jaminan kesehatan untuk sinkronisasi nasional SatuSehat Kemenkes.
                            </p>
                        </div>
                        <div class="bg-lifora-blue-soft rounded-4 p-3 d-flex align-items-center gap-3 flex-shrink-0">
                            <div class="bg-lifora-primary text-white rounded d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                <i class="fa-regular fa-id-card fs-5"></i>
                            </div>
                            <div>
                                <div class="fs-9 fw-bold text-muted text-uppercase mb-1">No. Rekam Medis (Otomatis)</div>
                                <div class="fs-4 fw-bold text-lifora-primary-dark lh-1" id="rmValue"><?= htmlspecialchars($no_rm_auto); ?></div>
                                <input type="hidden" name="no_rm" value="<?= htmlspecialchars($no_rm_auto); ?>">
                            </div>
                            <button type="button" class="btn btn-sm btn-link text-muted" id="btnCopyRm" title="Salin No RM"><i class="fa-regular fa-copy fs-5"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Grid Form -->
                <div class="row g-4 align-items-start">
                    
                    <!-- Kolom Kiri -->
                    <div class="col-lg-6 d-flex flex-column gap-4">
                        
                        <!-- 1. Data Identitas Utama -->
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-lifora-primary-soft text-lifora-primary-dark rounded d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px;">1</div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0">Data Identitas Utama</h5>
                                            <p class="fs-8 text-muted mb-0">Sesuai kartu identitas kependudukan sah (e-KTP/KIA)</p>
                                        </div>
                                    </div>
                                    <i class="fa-solid fa-fingerprint fs-4 text-muted"></i>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                                        <label class="form-label fs-8 fw-semibold text-dark">NIK (16 Digit) <span class="text-danger">*</span></label>
                                        <span class="fs-9 text-muted" id="nikCount">0/16 Digit</span>
                                    </div>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="fa-regular fa-id-card text-muted"></i></span>
                                        <input type="text" class="form-control bg-light border-0" id="nik" name="nik" required maxlength="16" inputmode="numeric" placeholder="Contoh: 3201123456780001">
                                        <button type="button" class="btn btn-sm bg-lifora-blue-badge text-lifora-blue fw-semibold"><i class="fa-regular fa-circle-check me-1"></i> Cek NIK</button>
                                    </div>
                                    <div class="fs-9 text-muted mt-2">Wajib 16 digit angka. Digunakan untuk pencarian rekam medis lintas faskes nasional.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fs-8 fw-semibold text-dark">Nama Lengkap <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="fa-regular fa-user text-muted"></i></span>
                                        <input type="text" class="form-control bg-light border-0" id="nama_lengkap" name="nama_lengkap" required placeholder="Masukkan nama lengkap tanpa gelar">
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fs-8 fw-semibold text-dark">Tempat Lahir <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control bg-light border-0" id="tempat_lahir" name="tempat_lahir" required placeholder="Kota / Kabupaten">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-baseline mb-1">
                                            <label class="form-label fs-8 fw-semibold text-dark mb-0">Tanggal Lahir <span class="text-danger">*</span></label>
                                            <span class="fs-9 text-lifora-primary fw-semibold" id="umurLabel">-- Tahun</span>
                                        </div>
                                        <input type="date" class="form-control bg-light border-0" id="tanggal_lahir" name="tanggal_lahir" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fs-8 fw-semibold text-dark">Jenis Kelamin <span class="text-danger">*</span></label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="d-flex align-items-center gap-2 bg-lifora-blue-soft rounded p-2 border border-transparent cursor-pointer" style="cursor: pointer;">
                                                <input class="form-check-input m-0" type="radio" name="jenis_kelamin" value="Laki-laki" checked>
                                                <span class="fs-7 fw-medium text-dark">Laki-laki</span>
                                            </label>
                                        </div>
                                        <div class="col-6">
                                            <label class="d-flex align-items-center gap-2 bg-lifora-blue-soft rounded p-2 border border-transparent cursor-pointer" style="cursor: pointer;">
                                                <input class="form-check-input m-0" type="radio" name="jenis_kelamin" value="Perempuan">
                                                <span class="fs-7 fw-medium text-dark">Perempuan</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                                        <label class="form-label fs-8 fw-semibold text-dark mb-0">Nama Gadis Ibu Kandung</label>
                                        <span class="fs-9 text-muted">Verifikasi Kemenkes</span>
                                    </div>
                                    <input type="text" class="form-control bg-light border-0" id="nama_ibu" name="nama_ibu" placeholder="Wajib untuk konfirmasi identitas rekam medis">
                                </div>
                            </div>
                        </div>

                        <!-- 3. Jaminan Pelayanan Kesehatan -->
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-lifora-primary-soft text-lifora-primary-dark rounded d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px;">3</div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0">Jaminan Pelayanan Kesehatan</h5>
                                            <p class="fs-8 text-muted mb-0">Kepesertaan asuransi kesehatan & faskes primer rujukan</p>
                                        </div>
                                    </div>
                                    <i class="fa-solid fa-shield-halved fs-4 text-muted"></i>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fs-8 fw-semibold text-dark mb-2">Jenis Penjamin / Pembiayaan <span class="text-danger">*</span></label>
                                    <div class="row g-2">
                                        <?php 
                                        $jaminanOptions = [
                                            ['val' => 'BPJS Kesehatan (PBI)', 'title' => 'BPJS Kesehatan (PBI)', 'sub' => 'Penerima Bantuan Iuran', 'checked' => true],
                                            ['val' => 'BPJS Mandiri / Pekerja', 'title' => 'BPJS Mandiri / Pekerja', 'sub' => 'Non-PBI / Mandiri / Swasta', 'checked' => false],
                                            ['val' => 'Umum / Tunai', 'title' => 'Umum / Tunai', 'sub' => 'Bayar mandiri loket kasir', 'checked' => false],
                                            ['val' => 'Asuransi Swasta', 'title' => 'Asuransi Swasta', 'sub' => 'Prudential, Sinarmas, dll.', 'checked' => false],
                                        ];
                                        foreach($jaminanOptions as $opt): ?>
                                        <div class="col-md-6">
                                            <label class="d-flex align-items-start gap-2 bg-lifora-blue-soft rounded p-2 border border-transparent h-100" style="cursor: pointer;">
                                                <input class="form-check-input m-0 mt-1" type="radio" name="jenis_penjamin" value="<?= $opt['val']; ?>" <?= $opt['checked'] ? 'checked' : ''; ?>>
                                                <div>
                                                    <div class="fs-7 fw-medium text-dark lh-sm"><?= $opt['title']; ?></div>
                                                    <div class="fs-9 text-muted mt-1"><?= $opt['sub']; ?></div>
                                                </div>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fs-8 fw-semibold text-dark">Nomor Kartu BPJS (13 Digit) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control bg-light border-0" id="no_bpjs" name="no_bpjs" placeholder="0001234567890">
                                            <button type="button" class="btn btn-sm bg-lifora-blue-badge text-lifora-blue fw-semibold">Cek PCare</button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fs-8 fw-semibold text-dark">Faskes Tingkat 1 (FKTP) Asal</label>
                                        <input type="text" class="form-control bg-light border-0" id="faskes_asal" name="faskes_asal" value="Puskesmas Melati Sejahtera">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Kolom Kanan -->
                    <div class="col-lg-6 d-flex flex-column gap-4">

                        <!-- 2. Kontak & Domisili Pasien -->
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-lifora-primary-soft text-lifora-primary-dark rounded d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px;">2</div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0">Kontak & Domisili Pasien</h5>
                                            <p class="fs-8 text-muted mb-0">Alamat KTP dan nomor telepon aktif</p>
                                        </div>
                                    </div>
                                    <i class="fa-solid fa-location-dot fs-4 text-muted"></i>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fs-8 fw-semibold text-dark">Nomor HP / WhatsApp Aktif <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="fa-solid fa-mobile-screen-button text-muted"></i></span>
                                        <input type="text" class="form-control bg-light border-0" id="no_hp" name="no_hp" required placeholder="Contoh: 081234567890">
                                    </div>
                                    <div class="fs-9 text-muted mt-2">Nomor ini akan menerima SMS/WA notifikasi antrean dan jadwal kontrol.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fs-8 fw-semibold text-dark">Alamat Lengkap KTP <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control bg-light border-0" id="alamat_ktp" name="alamat_ktp" required placeholder="Jl. Anggrek No. 12 Blok B, Komplek Melati Asri">
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-4">
                                        <label class="form-label fs-8 fw-semibold text-dark">RT <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control bg-light border-0" id="rt" name="rt" placeholder="004">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label fs-8 fw-semibold text-dark">RW <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control bg-light border-0" id="rw" name="rw" placeholder="007">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label fs-8 fw-semibold text-dark">Kode Pos</label>
                                        <input type="text" class="form-control bg-light border-0" id="kode_pos" name="kode_pos" placeholder="13450">
                                    </div>
                                </div>

                                <label class="d-flex align-items-start gap-2 bg-lifora-blue-soft rounded p-2 fs-8 fw-medium text-dark" style="cursor: pointer;">
                                    <input class="form-check-input m-0 mt-1" type="checkbox" checked>
                                    <span>Centang jika tempat tinggal pasien berada di alamat identitas KTP di atas.</span>
                                </label>
                            </div>
                        </div>

                        <!-- 4. Kontak Darurat & Penanggung Jawab -->
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-lifora-primary-soft text-lifora-primary-dark rounded d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px;">4</div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0">Kontak Darurat & Penanggung Jawab</h5>
                                            <p class="fs-8 text-muted mb-0">Pihak keluarga yang dapat dihubungi</p>
                                        </div>
                                    </div>
                                    <i class="fa-regular fa-address-card fs-4 text-muted"></i>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fs-8 fw-semibold text-dark">Nama Keluarga / Penanggung Jawab <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="fa-solid fa-people-group text-muted"></i></span>
                                        <input type="text" class="form-control bg-light border-0" id="nama_pj" name="nama_pj" placeholder="Nama suami / istri / orang tua / wali">
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fs-8 fw-semibold text-dark">Hubungan dengan Pasien <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control bg-light border-0" id="hubungan_pj" name="hubungan_pj" placeholder="Orang Tua (Ayah/Ibu)">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fs-8 fw-semibold text-dark">Nomor Telepon Darurat <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0"><i class="fa-solid fa-phone-volume text-muted"></i></span>
                                            <input type="text" class="form-control bg-light border-0" id="no_darurat" name="no_darurat" placeholder="Contoh: 08571234567">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 bg-lifora-blue-soft rounded p-3 fs-8 text-dark lh-sm">
                                    <i class="fa-solid fa-shield-heart text-lifora-primary-dark fs-5 mt-1"></i>
                                    <div>
                                        <strong class="d-block fs-7 fw-semibold text-dark mb-1">Persetujuan Pasien (General Consent)</strong>
                                        Dengan mendaftarkan data ini, pasien atau walinya telah menyetujui pemrosesan data medis primer pada Rekam Medis Elektronik Puskesmas sesuai UU Kesehatan No. 17 Tahun 2023.
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Action Bar -->
                <div class="card border-0 shadow-lg rounded-4 sticky-bottom mb-3" style="bottom: 20px; z-index: 20;">
                    <div class="card-body p-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2 fs-8 text-dark">
                            <i class="fa-solid fa-circle-info text-lifora-blue fs-5"></i>
                            <span>Periksa kelengkapan tanda (<span class="text-danger">*</span>) sebelum menyimpan ke database.</span>
                        </div>
                        <div class="d-flex flex-wrap justify-content-end gap-2">
                            <a href="kelola_pasien.php" class="btn btn-light fw-semibold"><i class="fa-solid fa-xmark me-1"></i> Batal</a>
                            <button type="button" class="btn btn-light fw-semibold"><i class="fa-regular fa-bookmark me-1"></i> Simpan Draf</button>
                            <button type="submit" name="simpan_pasien" class="btn bg-lifora-primary text-white fw-semibold px-4"><i class="fa-solid fa-user-check me-1"></i> Simpan & Daftarkan</button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </main>
</div>

<?php include '../layout/components/admin/footer.php'; ?>

<script>
    // Hitung digit NIK
    const nik = document.getElementById('nik');
    nik.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '');
        document.getElementById('nikCount').textContent = this.value.length + '/16 Digit';
    });

    // Hitung umur dari tanggal lahir
    document.getElementById('tanggal_lahir').addEventListener('change', function () {
        const label = document.getElementById('umurLabel');
        if (!this.value) { label.textContent = '-- Tahun'; return; }
        const lahir = new Date(this.value), now = new Date();
        let umur = now.getFullYear() - lahir.getFullYear();
        const m = now.getMonth() - lahir.getMonth();
        if (m < 0 || (m === 0 && now.getDate() < lahir.getDate())) umur--;
        label.textContent = umur + ' Tahun';
    });

    // Salin No. RM
    document.getElementById('btnCopyRm').addEventListener('click', function () {
        const teks = document.getElementById('rmValue').textContent.trim();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(teks);
            alert('No. RM berhasil disalin: ' + teks);
        }
    });
</script>

</body>
</html>