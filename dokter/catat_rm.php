<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}
require_once '../koneksi.php';

// Ambil Profil Dokter
$stmt = $pdo->prepare("SELECT * FROM dokter WHERE username = ? LIMIT 1");
$stmt->execute([$_SESSION['username']]);
$profilDokter = $stmt->fetch();
$nama_dokter = $profilDokter['nama_dokter'] ?? 'Dokter';
$poli_dokter = $profilDokter['poli'] ?? 'Poli Umum';

$pesan = '';

// Proses Simpan RM Baru
if (isset($_POST['simpan_rm'])) {
    $no_registrasi     = "REG-" . date('Ymd') . "-" . rand(1000, 9999);
    $waktu_kunjungan   = date('Y-m-d H:i:s');
    $no_rm             = trim($_POST['no_rm']);
    $nama_pasien       = trim($_POST['nama_pasien']);
    $kode_icd          = trim($_POST['kode_icd']);
    $nama_diagnosis    = trim($_POST['nama_diagnosis']);
    $keterangan_klinis = trim($_POST['keterangan_klinis']);
    $jaminan           = trim($_POST['jaminan']);

    try {
        $sql = "INSERT INTO rekam_medis (no_registrasi, waktu_kunjungan, no_rm, nama_pasien, poli, dokter, kode_icd, nama_diagnosis, keterangan_klinis, jaminan, status_berkas) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu Validasi Farmasi')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$no_registrasi, $waktu_kunjungan, $no_rm, $nama_pasien, $poli_dokter, $nama_dokter, $kode_icd, $nama_diagnosis, $keterangan_klinis, $jaminan]);
        $pesan = "<div class='alert alert-success rounded-4'><i class='fa-solid fa-circle-check me-2'></i>Rekam medis berhasil disimpan dengan No. Reg: <b>$no_registrasi</b></div>";
    } catch (PDOException $e) {
        $pesan = "<div class='alert alert-danger rounded-4'><i class='fa-solid fa-circle-exclamation me-2'></i>Gagal menyimpan: " . $e->getMessage() . "</div>";
    }
}

// Ambil data pasien untuk dropdown (opsional)
$listPasien = $pdo->query("SELECT no_rm, nama_lengkap, jenis_jaminan FROM pasien ORDER BY nama_lengkap ASC LIMIT 100")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../layout/components/dokter/head.php'; ?>
</head>
<body>
<div class="d-flex min-vh-100">
    <?php include '../layout/components/dokter/sidebar.php'; ?>

    <main class="flex-grow-1 d-flex flex-column" style="min-width: 0;">
        <?php include '../layout/components/dokter/topbar.php'; ?>

        <div class="container-fluid py-4 px-4 flex-grow-1 d-flex flex-column gap-4">
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Catat Rekam Medis Baru</h2>
                    <p class="text-muted fs-7 mb-0">Input manual rekam medis untuk pasien walk-in atau tanpa antrean.</p>
                </div>
                <a href="arsip_rm.php" class="btn btn-light border fw-semibold px-3 py-2"><i class="fa-solid fa-folder-open me-2"></i> Lihat Arsip</a>
            </div>

            <?= $pesan; ?>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <form method="POST">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fs-8 fw-bold text-dark">Pilih Pasien (Opsional)</label>
                                <select id="pilih_pasien" class="form-select bg-light border-0 py-2">
                                    <option value="">-- Ketik manual --</option>
                                    <?php foreach ($listPasien as $p): ?>
                                        <option value="<?= htmlspecialchars($p['no_rm']) ?>" data-nama="<?= htmlspecialchars($p['nama_lengkap']) ?>" data-jaminan="<?= htmlspecialchars($p['jenis_jaminan']) ?>">
                                            <?= htmlspecialchars($p['no_rm'] . ' - ' . $p['nama_lengkap']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-8 fw-bold text-dark">No. RM Pasien <span class="text-danger">*</span></label>
                                <input type="text" name="no_rm" id="no_rm" class="form-control bg-light border-0 py-2" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-8 fw-bold text-dark">Nama Pasien <span class="text-danger">*</span></label>
                                <input type="text" name="nama_pasien" id="nama_pasien" class="form-control bg-light border-0 py-2" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fs-8 fw-bold text-dark">Jaminan / Penjamin</label>
                                <select name="jaminan" id="jaminan" class="form-select bg-light border-0 py-2">
                                    <option value="Umum / Mandiri">Umum / Mandiri</option>
                                    <option value="BPJS PBI">BPJS PBI</option>
                                    <option value="BPJS Non-PBI">BPJS Non-PBI</option>
                                    <option value="Asuransi Lain">Asuransi Lain</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-8 fw-bold text-dark">Kode ICD-10 <span class="text-danger">*</span></label>
                                <input type="text" name="kode_icd" class="form-control bg-light border-0 py-2" placeholder="J06.9" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-8 fw-bold text-dark">Nama Diagnosis <span class="text-danger">*</span></label>
                                <input type="text" name="nama_diagnosis" class="form-control bg-light border-0 py-2" placeholder="ISPA Akut" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fs-8 fw-bold text-dark">Catatan SOAP / Keterangan Klinis <span class="text-danger">*</span></label>
                            <textarea name="keterangan_klinis" rows="6" class="form-control bg-light border-0 py-3" placeholder="Subjective...&#10;Objective...&#10;Assessment...&#10;Plan..." required></textarea>
                        </div>

                        <div class="d-flex justify-content-end pt-3 border-top">
                            <button type="submit" name="simpan_rm" class="btn bg-lifora-primary text-white fw-semibold px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Rekam Medis</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../layout/components/dokter/footer.php'; ?>
<script>
    // JS untuk auto-fill form saat memilih pasien dari dropdown
    document.getElementById('pilih_pasien').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (this.value) {
            document.getElementById('no_rm').value = this.value;
            document.getElementById('nama_pasien').value = opt.dataset.nama;
            document.getElementById('jaminan').value = opt.dataset.jaminan;
        } else {
            document.getElementById('no_rm').value = '';
            document.getElementById('nama_pasien').value = '';
        }
    });
</script>
</body>
</html>