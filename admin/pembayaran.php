<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}
require_once '../koneksi.php';

$pesan = '';

// ==========================================
// PROSES CRUD
// ==========================================

// 1. CREATE (Tambah Tagihan Baru)
if (isset($_POST['tambah_tagihan'])) {
    $no_registrasi = trim($_POST['no_registrasi']);
    $total_biaya   = (float)$_POST['total_biaya'];
    $status        = 'Belum Lunas';

    if ($no_registrasi === '' || $total_biaya <= 0) {
        $pesan = "<div class='alert alert-danger alert-dismissible fade show rounded-4' role='alert'>Data tidak valid. No. Registrasi dan Total Biaya wajib diisi.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO pembayaran (no_registrasi, total_biaya, status_pembayaran) VALUES (?, ?, ?)");
            $stmt->execute([$no_registrasi, $total_biaya, $status]);
            $pesan = "<div class='alert alert-success alert-dismissible fade show rounded-4' role='alert'>Tagihan baru berhasil dibuat.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } catch (PDOException $e) {
            $pesan = "<div class='alert alert-danger alert-dismissible fade show rounded-4' role='alert'>Gagal membuat tagihan: " . $e->getMessage() . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// 2. UPDATE (Proses Bayar)
if (isset($_POST['bayar'])) {
    $id_bayar = (int)$_POST['id_pembayaran'];
    $metode   = trim($_POST['metode_pembayaran']);
    $waktu    = date('Y-m-d H:i:s');

    try {
        $stmt = $pdo->prepare("UPDATE pembayaran SET status_pembayaran = 'Lunas', metode_pembayaran = ?, waktu_bayar = ? WHERE id = ?");
        $stmt->execute([$metode, $waktu, $id_bayar]);
        $pesan = "<div class='alert alert-success alert-dismissible fade show rounded-4' role='alert'>Pembayaran berhasil diproses!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } catch (PDOException $e) {
        $pesan = "<div class='alert alert-danger alert-dismissible fade show rounded-4' role='alert'>Gagal memproses pembayaran: " . $e->getMessage() . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// 3. DELETE (Hapus Tagihan)
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    try {
        $stmt = $pdo->prepare("DELETE FROM pembayaran WHERE id = ?");
        $stmt->execute([$id]);
        $pesan = "<div class='alert alert-success alert-dismissible fade show rounded-4' role='alert'>Tagihan berhasil dihapus.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } catch (PDOException $e) {
        $pesan = "<div class='alert alert-danger alert-dismissible fade show rounded-4' role='alert'>Gagal menghapus tagihan: " . $e->getMessage() . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// ==========================================
// AMBIL DATA
// ==========================================
$stmt = $pdo->query("SELECT * FROM pembayaran WHERE status_pembayaran = 'Belum Lunas' ORDER BY id ASC");
$tagihanBelumLunas = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM pembayaran WHERE status_pembayaran = 'Lunas' ORDER BY waktu_bayar DESC LIMIT 10");
$tagihanLunas = $stmt->fetchAll();

// Statistik
$totalTagihan = $pdo->query("SELECT COUNT(*) FROM pembayaran")->fetchColumn();
$totalBelumLunas = count($tagihanBelumLunas);
$totalPendapatan = $pdo->query("SELECT COALESCE(SUM(total_biaya), 0) FROM pembayaran WHERE status_pembayaran = 'Lunas'")->fetchColumn();
$totalPiutang = $pdo->query("SELECT COALESCE(SUM(total_biaya), 0) FROM pembayaran WHERE status_pembayaran = 'Belum Lunas'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../layout/components/admin/head.php'; ?>
</head>
<body>

<div class="d-flex min-vh-100">
    <?php include '../layout/components/admin/sidebar.php'; ?>

    <main class="flex-grow-1 d-flex flex-column" style="min-width: 0;">
        <?php include '../layout/components/admin/topbar.php'; ?>

        <div class="container-fluid py-4 px-4 flex-grow-1 d-flex flex-column gap-4">
            
            <!-- Header -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Kasir & Pembayaran</h2>
                    <p class="text-muted fs-7 mb-0">Manajemen tagihan pasien, pemrosesan pembayaran, dan riwayat transaksi.</p>
                </div>
                <button class="btn bg-lifora-primary text-white fw-semibold px-3 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahTagihan">
                    <i class="fa-solid fa-plus me-2"></i> Buat Tagihan Baru
                </button>
            </div>

            <!-- Notifikasi -->
            <?= $pesan; ?>

            <!-- Statistik Ringkas -->
            <div class="row row-cols-1 row-cols-md-3 g-3">
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">Tagihan Belum Lunas</div>
                                <div class="fs-2 fw-bold text-dark"><?= $totalBelumLunas; ?> <span class="fs-6 fw-normal text-muted">Transaksi</span></div>
                            </div>
                            <div class="bg-danger bg-opacity-10 text-danger rounded d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-file-invoice fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">Total Piutang</div>
                                <div class="fs-2 fw-bold text-danger">Rp <?= number_format($totalPiutang, 0, ',', '.'); ?></div>
                            </div>
                            <div class="bg-danger bg-opacity-10 text-danger rounded d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-hand-holding-dollar fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">Total Pendapatan</div>
                                <div class="fs-2 fw-bold text-lifora-primary-dark">Rp <?= number_format($totalPendapatan, 0, ',', '.'); ?></div>
                            </div>
                            <div class="bg-lifora-primary-soft text-lifora-primary rounded d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-money-bill-wave fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Tagihan Belum Lunas -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-hourglass-half text-danger me-2"></i> Tagihan Pasien Belum Lunas</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-lifora-blue-soft">
                                <tr>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3 ps-3 rounded-start">No Registrasi</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Total Biaya</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Status</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3 text-center rounded-end pe-3" style="width: 40%;">Proses Bayar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($tagihanBelumLunas) > 0): ?>
                                    <?php foreach ($tagihanBelumLunas as $row): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark fs-7"><?= htmlspecialchars($row['no_registrasi']); ?></td>
                                        <td class="fw-bold text-danger fs-7">Rp <?= number_format($row['total_biaya'], 0, ',', '.'); ?></td>
                                        <td>
                                            <span class="badge bg-danger bg-opacity-10 text-danger fw-semibold rounded-pill px-2 py-1 fs-8">
                                                <?= htmlspecialchars($row['status_pembayaran']); ?>
                                            </span>
                                        </td>
                                        <td class="pe-3">
                                            <form method="POST" class="d-flex gap-2 justify-content-center">
                                                <input type="hidden" name="id_pembayaran" value="<?= $row['id']; ?>">
                                                <select name="metode_pembayaran" class="form-select form-select-sm bg-light border-0 w-auto" required>
                                                    <option value="">- Pilih Metode -</option>
                                                    <option value="Tunai">Tunai</option>
                                                    <option value="Kartu Debit">Kartu Debit / Kredit</option>
                                                    <option value="QRIS">QRIS / E-Wallet</option>
                                                    <option value="BPJS Kesehatan">BPJS Kesehatan (Klaim)</option>
                                                </select>
                                                <button type="submit" name="bayar" class="btn btn-sm bg-lifora-primary text-white fw-semibold rounded px-3">
                                                    <i class="fa-solid fa-cash-register me-1"></i> Bayar
                                                </button>
                                                <a href="pembayaran.php?hapus=<?= $row['id']; ?>" class="btn btn-sm btn-light border text-danger rounded" onclick="return confirm('Yakin ingin menghapus tagihan ini?')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted fs-7">
                                            <i class="fa-solid fa-check-circle text-success fs-3 mb-2 d-block"></i>
                                            Semua tagihan telah lunas.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tabel Riwayat Pembayaran Lunas -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-box-archive text-lifora-primary me-2"></i> Riwayat Pembayaran Terakhir</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-lifora-blue-soft">
                                <tr>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3 ps-3 rounded-start">No Registrasi</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Total Biaya</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Metode</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Waktu Bayar</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3 text-center rounded-end pe-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($tagihanLunas) > 0): ?>
                                    <?php foreach ($tagihanLunas as $row): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark fs-7"><?= htmlspecialchars($row['no_registrasi']); ?></td>
                                        <td class="fw-semibold text-lifora-primary-dark fs-7">Rp <?= number_format($row['total_biaya'], 0, ',', '.'); ?></td>
                                        <td>
                                            <span class="badge bg-lifora-blue-soft text-lifora-blue rounded-pill px-2 py-1 fs-8"><?= htmlspecialchars($row['metode_pembayaran']); ?></span>
                                        </td>
                                        <td class="fs-8 text-muted"><?= date('d/m/Y H:i', strtotime($row['waktu_bayar'])); ?> WIB</td>
                                        <td class="text-center pe-3">
                                            <button class="btn btn-sm bg-lifora-blue-soft text-lifora-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width:32px;height:32px;" onclick="cetakKuitansi(this)" 
                                                data-reg="<?= htmlspecialchars($row['no_registrasi']); ?>" 
                                                data-biaya="<?= number_format($row['total_biaya'], 0, ',', '.'); ?>" 
                                                data-metode="<?= htmlspecialchars($row['metode_pembayaran']); ?>" 
                                                data-waktu="<?= date('d/m/Y H:i', strtotime($row['waktu_bayar'])); ?>">
                                                <i class="fa-solid fa-print"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted fs-7">Belum ada riwayat pembayaran lunas.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Modal Tambah Tagihan (CRUD - CREATE) -->
<div class="modal fade" id="modalTambahTagihan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-lifora-primary text-white border-bottom-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Buat Tagihan Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fs-8 fw-semibold text-muted">No. Registrasi</label>
                        <input type="text" name="no_registrasi" class="form-control bg-light border-0" placeholder="Cth: REG-20261021-0089" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-8 fw-semibold text-muted">Total Biaya (Rp)</label>
                        <input type="number" name="total_biaya" class="form-control bg-light border-0" placeholder="0" min="1" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_tagihan" class="btn bg-lifora-primary text-white fw-semibold"><i class="fa-solid fa-save me-1"></i> Simpan Tagihan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../layout/components/admin/footer.php'; ?>

<script>
    // Fungsi Cetak Kuitansi
    function cetakKuitansi(btn) {
        const d = btn.dataset;
        const w = window.open('', '_blank', 'width=400,height=600');
        if (!w) { alert('Pop-up diblokir. Izinkan pop-up untuk mencetak.'); return; }
        
        w.document.write(`
            <html>
            <head>
                <title>Kuitansi ${d.reg}</title>
                <style>
                    body { font-family: 'Courier New', Courier, monospace; padding: 20px; color: #000; font-size: 14px; }
                    .header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 15px; }
                    .header h2 { margin: 0; font-size: 18px; }
                    .header p { margin: 2px 0; font-size: 12px; }
                    .row { display: flex; justify-content: space-between; margin-bottom: 8px; }
                    .row .label { color: #555; }
                    .total { border-top: 1px dashed #000; margin-top: 15px; padding-top: 10px; font-weight: bold; font-size: 16px; }
                    .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #555; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>PUSKESMAS MELATI SEJAHTERA</h2>
                    <p>Jl. Kesehatan No. 1, Kota Sehat</p>
                    <p>Telp: (021) 123-4567</p>
                </div>
                <div style="text-align:center; margin-bottom:15px; font-weight:bold; text-decoration:underline;">KUITANSI PEMBAYARAN</div>
                <div class="row"><span class="label">No. Registrasi</span><span>${d.reg}</span></div>
                <div class="row"><span class="label">Waktu Bayar</span><span>${d.waktu} WIB</span></div>
                <div class="row"><span class="label">Metode</span><span>${d.metode}</span></div>
                <div class="total row"><span class="label">TOTAL</span><span>Rp ${d.biaya}</span></div>
                <div class="footer">
                    <p>Terima kasih atas pembayaran Anda.</p>
                    <p>Kuitansi ini adalah bukti sah pembayaran.</p>
                </div>
                <script>window.onload = function() { window.print(); }<\/script>
            </body>
            </html>
        `);
        w.document.close();
    }
</script>

</body>
</html>