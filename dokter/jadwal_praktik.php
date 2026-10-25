<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}
require_once '../koneksi.php';

$pesan = '';
$username = $_SESSION['username'];

// Proses Update Jadwal
if (isset($_POST['update_jadwal'])) {
    $hari  = trim($_POST['hari_praktik']);
    $jam   = trim($_POST['jam_praktik']);
    $kuota = max(0, (int)$_POST['kuota_harian']);

    try {
        $stmt = $pdo->prepare("UPDATE dokter SET hari_praktik = ?, jam_praktik = ?, kuota_harian = ? WHERE username = ?");
        $stmt->execute([$hari, $jam, $kuota, $username]);
        $pesan = "<div class='alert alert-success rounded-4'><i class='fa-solid fa-circle-check me-2'></i>Jadwal praktik berhasil diperbarui!</div>";
    } catch (PDOException $e) {
        $pesan = "<div class='alert alert-danger rounded-4'><i class='fa-solid fa-circle-exclamation me-2'></i>Gagal memperbarui jadwal: " . $e->getMessage() . "</div>";
    }
}

// Ambil data jadwal dokter
$stmt = $pdo->prepare("SELECT * FROM dokter WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$dataDokter = $stmt->fetch();
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
            
            <div>
                <h2 class="fw-bold text-dark mb-1">Jadwal Praktik Saya</h2>
                <p class="text-muted fs-7 mb-0">Kelola ketersediaan dan kuota harian Anda di poli.</p>
            </div>

            <?= $pesan; ?>

            <div class="row g-4">
                <!-- Kolom Kiri: Info Profil -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 text-center h-100">
                        <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center">
                            <div class="bg-lifora-blue-soft rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fa-solid fa-user-doctor text-lifora-primary fs-1"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($dataDokter['nama_dokter'] ?? '-'); ?></h4>
                            <div class="badge bg-lifora-primary-soft text-lifora-primary-dark rounded-pill px-3 py-2 fs-7 mb-3"><?= htmlspecialchars($dataDokter['poli'] ?? '-'); ?></div>
                            <div class="fs-8 text-muted w-100 border-top pt-3 mt-2">
                                <div class="d-flex justify-content-between mb-2"><span>NIP/SIP</span> <b class="text-dark"><?= htmlspecialchars($dataDokter['nip_sip'] ?? '-'); ?></b></div>
                                <div class="d-flex justify-content-between"><span>Status SatuSehat</span> <b class="text-success"><?= htmlspecialchars($dataDokter['status_satusehat'] ?? '-'); ?></b></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Form Jadwal -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h5 class="fw-bold text-dark mb-4 border-bottom pb-3"><i class="fa-regular fa-calendar-days text-lifora-primary me-2"></i> Pengaturan Jadwal & Kuota</h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fs-8 fw-bold text-dark">Hari Praktik</label>
                                    <input type="text" name="hari_praktik" class="form-control bg-light border-0 py-2" value="<?= htmlspecialchars($dataDokter['hari_praktik'] ?? ''); ?>" placeholder="Contoh: Senin - Jumat">
                                    <div class="fs-9 text-muted mt-1">Gunakan format teks bebas, misal: "Senin, Rabu, Jumat" atau "Senin - Jumat".</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fs-8 fw-bold text-dark">Jam Praktik</label>
                                    <input type="text" name="jam_praktik" class="form-control bg-light border-0 py-2" value="<?= htmlspecialchars($dataDokter['jam_praktik'] ?? ''); ?>" placeholder="Contoh: 08.00 - 14.00 WIB">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fs-8 fw-bold text-dark">Kuota Harian Pasien</label>
                                    <input type="number" name="kuota_harian" class="form-control bg-light border-0 py-2" value="<?= htmlspecialchars($dataDokter['kuota_harian'] ?? 30); ?>" min="0">
                                    <div class="fs-9 text-muted mt-1">Isi 0 jika dokter praktik tanpa batas kuota (misal: dokter jaga UGD).</div>
                                </div>
                                <div class="d-flex justify-content-end pt-3 border-top">
                                    <button type="submit" name="update_jadwal" class="btn bg-lifora-primary text-white fw-semibold px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?php include '../layout/components/dokter/footer.php'; ?>
</body>
</html>