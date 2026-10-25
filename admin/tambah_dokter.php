<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}
require_once '../koneksi.php';

/* ===== Helper umum ===== */
function setFlash($tipe, $teks) {
    $_SESSION['flash_dokter'] = ['tipe' => $tipe, 'teks' => $teks];
}

// Upload foto dokter -> [namaFile, pesanError]
function uploadFoto($field) {
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return ['', ''];
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) return ['', 'Foto gagal diunggah.'];
    if ($_FILES[$field]['size'] > 2 * 1024 * 1024) return ['', 'Ukuran foto maksimal 2 MB.'];
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) || !@getimagesize($_FILES[$field]['tmp_name'])) {
        return ['', 'Format foto harus JPG, PNG, atau WEBP.'];
    }
    $dir = __DIR__ . '/../assets/img/dokter/';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $nama = 'dokter_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dir . $nama)) {
        return ['', 'Foto gagal disimpan ke folder assets/img/dokter.'];
    }
    return [$nama, ''];
}

/* ===== Proses form: simpan (tambah/edit), hapus, aktif/nonaktif ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['toggle_status'])) {
            $id = (int) ($_POST['id'] ?? 0);
            $statusBaru = (($_POST['status_baru'] ?? '') === 'aktif') ? 'Aktif Bertugas' : 'Non-Aktif';
            $stmt = $pdo->prepare("UPDATE dokter SET status_tugas = ? WHERE id = ?");
            $stmt->execute([$statusBaru, $id]);
            setFlash('ok', 'Status dokter berhasil diubah menjadi ' . $statusBaru . '.');

        } elseif (isset($_POST['hapus_dokter'])) {
            $id = (int) ($_POST['id_dokter'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM dokter WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('ok', 'Data dokter berhasil dihapus.');

        } elseif (isset($_POST['simpan_dokter'])) {
            $id     = (int) ($_POST['id_dokter'] ?? 0);
            $nama   = trim($_POST['nama_dokter'] ?? '');
            $gelar  = trim($_POST['gelar_spesialis'] ?? '');
            $nip    = trim($_POST['nip_sip'] ?? '');
            $sat    = trim($_POST['status_satusehat'] ?? '') ?: 'Terkoneksi SatuSehat';
            $poli   = trim($_POST['poli'] ?? '');
            $hari   = trim($_POST['hari_praktik'] ?? '');
            $jam    = trim($_POST['jam_praktik'] ?? '');
            $kuota  = max(0, (int) ($_POST['kuota_harian'] ?? 0));
            $status = trim($_POST['status_tugas'] ?? '') ?: 'Aktif Bertugas';
            $ket    = trim($_POST['keterangan_status'] ?? '');

            if ($nama === '' || $nip === '' || $poli === '') {
                setFlash('err', 'Nama dokter, NIP/SIP, dan poli wajib diisi.');
            } else {
                list($foto, $errFoto) = uploadFoto('foto');
                if ($errFoto !== '') {
                    setFlash('err', $errFoto);
                } elseif ($id > 0) {
                    // Update
                    if ($foto !== '') {
                        $sql = "UPDATE dokter SET nama_dokter=?, gelar_spesialis=?, nip_sip=?, status_satusehat=?, poli=?, hari_praktik=?, jam_praktik=?, kuota_harian=?, status_tugas=?, keterangan_status=?, foto_avatar=? WHERE id=?";
                        $params = [$nama, $gelar, $nip, $sat, $poli, $hari, $jam, $kuota, $status, $ket, $foto, $id];
                    } else {
                        $sql = "UPDATE dokter SET nama_dokter=?, gelar_spesialis=?, nip_sip=?, status_satusehat=?, poli=?, hari_praktik=?, jam_praktik=?, kuota_harian=?, status_tugas=?, keterangan_status=? WHERE id=?";
                        $params = [$nama, $gelar, $nip, $sat, $poli, $hari, $jam, $kuota, $status, $ket, $id];
                    }
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    setFlash('ok', 'Data ' . $nama . ' berhasil diperbarui.');
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO dokter (nama_dokter, gelar_spesialis, nip_sip, status_satusehat, poli, hari_praktik, jam_praktik, kuota_harian, kuota_terisi, status_tugas, keterangan_status, foto_avatar) VALUES (?,?,?,?,?,?,?,?,0,?,?,?)");
                    $stmt->execute([$nama, $gelar, $nip, $sat, $poli, $hari, $jam, $kuota, $status, $ket, $foto]);
                    setFlash('ok', $nama . ' berhasil ditambahkan.');
                }
            }
        }
    } catch (PDOException $e) {
        setFlash('err', 'Terjadi kesalahan database: ' . $e->getMessage());
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$flash = $_SESSION['flash_dokter'] ?? null;
unset($_SESSION['flash_dokter']);

/* ===== Helper Foto ===== */
function fotoDokter($row) {
    $av = trim($row['foto_avatar'] ?? '');
    $adaFile = $av !== '' && preg_match('/\.(jpe?g|png|webp)$/i', $av);
    $file = $adaFile ? $av : 'default.jpg';
    $namaFallback = ($av !== '' && !$adaFile) ? $av : urlencode($row['nama_dokter']);
    $fallback = 'https://ui-avatars.com/api/?name=' . $namaFallback . '&background=0b5c8a&color=fff&bold=true';
    return '<img src="../assets/img/dokter/' . htmlspecialchars($file) . '" alt="' . htmlspecialchars($row['nama_dokter']) . '" class="rounded-circle border" style="width:48px;height:48px;object-fit:cover;" onerror="this.onerror=null;this.src=\'' . $fallback . '\'">';
}

/* ===== Profil dokter yang sedang login (Topbar) ===== */
function ambilProfil($pdo) {
    $username = $_SESSION['username'] ?? '';
    $profil = ['nama' => $username ?: 'Pengguna', 'badge' => '', 'info' => '', 'poli' => '', 'foto_html' => ''];

    try {
        $stmt = $pdo->prepare("SELECT * FROM dokter WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $d = $stmt->fetch();

        if ($d) {
            $profil['nama']      = $d['nama_dokter'];
            $profil['badge']     = $d['poli'];
            $profil['poli']      = $d['poli'];
            $profil['info']      = 'SIP. ' . strtok(trim($d['nip_sip']), ' ');
            $profil['foto_html'] = fotoDokter($d);
        }
    } catch (Throwable $e) {
        // Kolom username belum ada
    }

    if ($profil['foto_html'] === '') {
        $fallback = 'https://ui-avatars.com/api/?name=' . urlencode($profil['nama']) . '&background=0d6b5f&color=fff&bold=true';
        $profil['foto_html'] = '<img src="' . $fallback . '" class="rounded-circle border" style="width:40px;height:40px;object-fit:cover;">';
    }
    return $profil;
}
$profil = ambilProfil($pdo);

/* ===== Filter, Pencarian, Pagination ===== */
$tab = $_GET['tab'] ?? 'daftar';
if (!in_array($tab, ['daftar', 'shift', 'cuti'], true)) $tab = 'daftar';

$perPage = 5;
$page = max(1, (int) ($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$poli_filter = trim($_GET['poli'] ?? '');

// Bangun WHERE clause dinamis dengan PDO
$where = "1=1";
$params = [];

if ($search !== '') {
    $searchNorm = preg_replace('/[\s.,]+/', '', $search);
    $where .= " AND (REPLACE(REPLACE(REPLACE(nama_dokter, ' ', ''), '.', ''), ',', '') LIKE ? 
                OR REPLACE(REPLACE(REPLACE(nip_sip, ' ', ''), '.', ''), ',', '') LIKE ?
                OR REPLACE(REPLACE(REPLACE(gelar_spesialis, ' ', ''), '.', ''), ',', '') LIKE ?
                OR REPLACE(REPLACE(REPLACE(poli, ' ', ''), '.', ''), ',', '') LIKE ?)";
    $like = "%$searchNorm%";
    array_push($params, $like, $like, $like, $like);
}
if ($poli_filter !== '') {
    $where .= " AND poli = ?";
    $params[] = $poli_filter;
}
if ($tab === 'cuti') $where .= " AND status_tugas = 'Sedang Cuti'";

// Hitung total
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM dokter WHERE $where");
$stmtCount->execute($params);
$totalHasil = (int)$stmtCount->fetchColumn();

$totalSemua = (int)$pdo->query("SELECT COUNT(*) FROM dokter")->fetchColumn();
$totalCuti  = (int)$pdo->query("SELECT COUNT(*) FROM dokter WHERE status_tugas = 'Sedang Cuti'")->fetchColumn();

$totalHal = max(1, (int)ceil($totalHasil / $perPage));
$page = min($page, $totalHal);
$offset = ($page - 1) * $perPage;

// Ambil data dengan LIMIT/OFFSET
$stmtRows = $pdo->prepare("SELECT * FROM dokter WHERE $where ORDER BY id ASC LIMIT $perPage OFFSET $offset");
$stmtRows->execute($params);
$dokterList = $stmtRows->fetchAll();
$jumlahTampil = count($dokterList);

function urlHal($tab, $page, $search, $poli) {
    $q = ['tab' => $tab, 'page' => $page];
    if ($search !== '') $q['search'] = $search;
    if ($poli !== '')   $q['poli'] = $poli;
    return '?' . http_build_query($q);
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
            
            <!-- Flash Message -->
            <?php if ($flash): ?>
                <div class="alert <?= $flash['tipe'] === 'ok' ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show rounded-4" role="alert">
                    <i class="fa-solid <?= $flash['tipe'] === 'ok' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> me-2"></i>
                    <?= htmlspecialchars($flash['teks']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Hero Header -->
            <div class="card border-0 shadow-sm rounded-4 hero-card">
                <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
                    <div>
                        <div class="fs-8 fw-semibold text-muted text-uppercase mb-2" style="letter-spacing: 0.04em;">Manajemen SDM Medis &bull; <span class="text-lifora-primary">Puskesmas Melati Sejahtera</span></div>
                        <h1 class="fw-bold text-dark mb-2" style="letter-spacing: -0.02em;">Kelola Data Dokter & Tenaga Medis</h1>
                        <p class="text-muted mb-0 fs-6" style="max-width: 500px; line-height: 1.5;">Tata kelola jadwal praktik, SIP/NIP terintegrasi SatuSehat, dan monitoring kapasitas antrean poli.</p>
                    </div>
                    <button type="button" class="btn bg-lifora-primary text-white fw-semibold px-3 py-2 shadow-sm flex-shrink-0" id="btnTambah">
                        <i class="fa-solid fa-user-plus me-2"></i> Tambah Dokter Baru
                    </button>
                </div>
            </div>

            <!-- Kartu Statistik -->
            <div class="row row-cols-1 row-cols-md-3 g-3">
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fs-8 fw-semibold text-muted text-uppercase mb-2">Kapasitas Medis</div>
                                <div class="fs-1 fw-bold text-dark lh-1 mb-2"><?= $totalSemua; ?></div>
                                <div class="d-flex align-items-center gap-2 fs-8 text-muted flex-wrap">
                                    <span>Dokter Spesialis & Umum</span>
                                    <span class="badge bg-lifora-primary-soft text-lifora-primary-dark rounded-pill">100% Terverifikasi</span>
                                </div>
                            </div>
                            <div class="bg-lifora-primary-soft text-lifora-primary rounded d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-briefcase-medical fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fs-8 fw-semibold text-muted text-uppercase mb-2">Jaga Operasional</div>
                                <div class="fs-1 fw-bold text-lifora-blue lh-1 mb-2">8 <span class="fs-5 fw-normal text-muted">/ <?= $totalSemua; ?> Dokter</span></div>
                                <div class="d-flex align-items-center gap-2 fs-8 text-muted flex-wrap">
                                    <span class="d-flex align-items-center gap-1"><span class="bg-lifora-primary rounded-circle d-inline-block" style="width: 6px; height: 6px;"></span> 6 Siang &bull; 2 Malam</span>
                                    <span class="fw-semibold text-dark">Shift Aktif</span>
                                </div>
                            </div>
                            <div class="bg-lifora-blue-badge text-lifora-blue rounded d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-id-badge fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fs-8 fw-semibold text-muted text-uppercase mb-2">Layanan Buka</div>
                                <div class="fs-1 fw-bold text-lifora-blue lh-1 mb-2">5 <span class="fs-5 fw-normal text-muted">Unit</span></div>
                                <div class="d-flex align-items-center gap-2 fs-8 text-muted flex-wrap">
                                    <span>Umum, Gigi, KIA, Lansia, UGD</span>
                                    <span class="badge bg-lifora-blue-soft text-lifora-blue rounded-pill">Optimal</span>
                                </div>
                            </div>
                            <div class="bg-lifora-blue-badge text-lifora-blue rounded d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-building fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab & Filter -->
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div class="d-flex bg-lifora-blue-soft rounded-3 p-1 gap-1 overflow-auto">
                    <a href="<?= htmlspecialchars(urlHal('daftar', 1, $search, $poli_filter)); ?>" class="btn btn-sm text-nowrap d-flex align-items-center gap-2 <?= $tab === 'daftar' ? 'bg-white text-lifora-primary-dark fw-bold shadow-sm' : 'text-dark'; ?>">
                        <i class="fa-solid fa-user-doctor"></i> Daftar Tenaga <span class="badge bg-lifora-primary-soft text-lifora-primary-dark rounded-pill"><?= $totalSemua; ?></span>
                    </a>
                    <a href="<?= htmlspecialchars(urlHal('shift', 1, $search, $poli_filter)); ?>" class="btn btn-sm text-nowrap d-flex align-items-center gap-2 <?= $tab === 'shift' ? 'bg-white text-lifora-primary-dark fw-bold shadow-sm' : 'text-dark'; ?>">
                        <i class="fa-regular fa-calendar-check"></i> Jadwal Shift
                    </a>
                    <a href="<?= htmlspecialchars(urlHal('cuti', 1, $search, $poli_filter)); ?>" class="btn btn-sm text-nowrap d-flex align-items-center gap-2 <?= $tab === 'cuti' ? 'bg-white text-lifora-primary-dark fw-bold shadow-sm' : 'text-dark'; ?>">
                        <i class="fa-regular fa-calendar-xmark"></i> Izin & Cuti <span class="badge bg-danger bg-opacity-25 text-danger rounded-pill"><?= $totalCuti; ?></span>
                    </a>
                </div>

                <form method="GET" action="" class="d-flex flex-column flex-md-row gap-2">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab); ?>">
                    <div class="position-relative">
                        <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left: 14px; top: 50%; transform: translateY(-50%);"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" class="form-control bg-white border py-2 ps-5" placeholder="Cari nama dokter, SIP, atau spesialis...">
                    </div>
                    <div class="position-relative">
                        <i class="fa-solid fa-sliders position-absolute text-muted" style="left: 14px; top: 50%; transform: translateY(-50%);"></i>
                        <select name="poli" class="form-select bg-white border py-2 ps-5" onchange="this.form.submit()">
                            <option value="">Poli: Semua</option>
                            <option value="Poli Umum" <?= $poli_filter == 'Poli Umum' ? 'selected' : ''; ?>>Poli Umum</option>
                            <option value="Poli Gigi & Mulut" <?= $poli_filter == 'Poli Gigi & Mulut' ? 'selected' : ''; ?>>Poli Gigi & Mulut</option>
                            <option value="Poli Lansia / Geriatri" <?= $poli_filter == 'Poli Lansia / Geriatri' ? 'selected' : ''; ?>>Poli Lansia</option>
                            <option value="Poli KIA & Imunisasi" <?= $poli_filter == 'Poli KIA & Imunisasi' ? 'selected' : ''; ?>>Poli KIA</option>
                            <option value="UGD 24 Jam" <?= $poli_filter == 'UGD 24 Jam' ? 'selected' : ''; ?>>UGD 24 Jam</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Tabel Dokter -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <?php if ($tab === 'shift'): ?>
                            <thead class="bg-lifora-blue-soft">
                                <tr>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3 ps-3">Dokter / Tenaga Medis</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Poli / Spesialisasi</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Hari Praktik</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Jam Praktik</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3 pe-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($jumlahTampil > 0): foreach ($dokterList as $row): $isUgd = strpos($row['poli'], 'UGD') !== false; ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <?= fotoDokter($row); ?>
                                            <div>
                                                <div class="fw-semibold text-dark fs-7"><?= htmlspecialchars($row['nama_dokter']); ?></div>
                                                <div class="fs-8 text-muted"><?= htmlspecialchars($row['gelar_spesialis']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge <?= $isUgd ? 'bg-danger bg-opacity-10 text-danger' : 'bg-lifora-blue-soft text-lifora-blue'; ?> rounded-pill px-2 py-1 fs-8"><?= htmlspecialchars($row['poli']); ?></span></td>
                                    <td class="fs-7 fw-medium text-dark"><?= htmlspecialchars($row['hari_praktik']); ?></td>
                                    <td class="fs-7 fw-semibold text-dark"><i class="fa-regular fa-clock text-muted me-1"></i> <?= htmlspecialchars($row['jam_praktik']); ?></td>
                                    <td class="pe-3">
                                        <?php if ($row['status_tugas'] == 'Aktif Bertugas'): ?>
                                            <span class="badge bg-lifora-primary-soft text-lifora-primary-dark rounded-pill px-2 py-1 fs-8"><span class="bg-lifora-primary rounded-circle d-inline-block me-1" style="width: 7px; height: 7px;"></span> Aktif Bertugas</span>
                                        <?php elseif ($row['status_tugas'] == 'Sedang Cuti'): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2 py-1 fs-8"><span class="bg-danger rounded-circle d-inline-block me-1" style="width: 7px; height: 7px;"></span> Sedang Cuti</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted rounded-pill px-2 py-1 fs-8"><span class="bg-secondary rounded-circle d-inline-block me-1" style="width: 7px; height: 7px;"></span> Non-Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted fs-7">Data dokter tidak ditemukan.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        <?php else: ?>
                            <thead class="bg-lifora-blue-soft">
                                <tr>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3 ps-3">Dokter / Tenaga Medis</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">NIP / SIP Kemenkes</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Poli / Spesialisasi</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Hari & Jam</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3 text-center">Kuota Harian</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Status</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3 text-center pe-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($jumlahTampil > 0): foreach ($dokterList as $row): 
                                    $isUgd = strpos($row['poli'], 'UGD') !== false;
                                    $isCuti = $row['status_tugas'] != 'Aktif Bertugas';
                                    $kuotaHarian = (int) $row['kuota_harian'];
                                    $kuotaTerisi = (int) $row['kuota_terisi'];
                                    $persen = $kuotaHarian > 0 ? round(($kuotaTerisi / $kuotaHarian) * 100) : 0;
                                    
                                    $ket = trim($row['keterangan_status'] ?? '');
                                    $pengganti = ''; $sampai = '';
                                    if (preg_match('/Pengganti:\s*(.+?)\s*(?:\(s\/d\s*([^)]+)\))?$/i', $ket, $m)) {
                                        $pengganti = trim($m[1]);
                                        $sampai = isset($m[2]) ? trim($m[2]) : '';
                                    }
                                    $ketKuota = trim(preg_replace('/^Siaga\s*/i', '', $ket));
                                    if ($ketKuota === '') $ketKuota = 'Tanpa Batas Kuota';

                                    $dataEdit = htmlspecialchars(json_encode([
                                        'id'                => (int) $row['id'],
                                        'nama_dokter'       => $row['nama_dokter'],
                                        'gelar_spesialis'   => $row['gelar_spesialis'],
                                        'nip_sip'           => $row['nip_sip'],
                                        'status_satusehat'  => $row['status_satusehat'],
                                        'poli'              => $row['poli'],
                                        'hari_praktik'      => $row['hari_praktik'],
                                        'jam_praktik'       => $row['jam_praktik'],
                                        'kuota_harian'      => $kuotaHarian,
                                        'status_tugas'      => $row['status_tugas'],
                                        'keterangan_status' => $ket,
                                    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                                ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <?= fotoDokter($row); ?>
                                            <div>
                                                <div class="fw-semibold text-dark fs-7"><?= htmlspecialchars($row['nama_dokter']); ?></div>
                                                <div class="fs-8 text-muted"><?= htmlspecialchars($row['gelar_spesialis']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark fs-7"><?= htmlspecialchars($row['nip_sip']); ?></div>
                                        <div class="fs-8 text-lifora-primary fw-semibold"><i class="fa-regular fa-circle-check me-1"></i> <?= htmlspecialchars($row['status_satusehat']); ?></div>
                                    </td>
                                    <td><span class="badge <?= $isUgd ? 'bg-danger bg-opacity-10 text-danger' : 'bg-lifora-blue-soft text-lifora-blue'; ?> rounded-pill px-2 py-1 fs-8"><?= htmlspecialchars($row['poli']); ?></span></td>
                                    <td>
                                        <div class="fw-semibold text-dark fs-7"><?= htmlspecialchars($row['hari_praktik']); ?></div>
                                        <div class="fs-8 text-muted"><i class="fa-regular fa-clock me-1"></i> <?= htmlspecialchars($row['jam_praktik']); ?></div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($kuotaHarian > 0): ?>
                                            <div class="fw-bold text-dark fs-7 mb-1"><?= $kuotaTerisi; ?> / <?= $kuotaHarian; ?></div>
                                            <?php if ($kuotaTerisi > 0): ?>
                                                <div class="progress mx-auto mb-1" style="height: 5px; width: 60px;">
                                                    <div class="progress-bar <?= $persen >= 90 ? 'bg-danger' : 'bg-lifora-primary-dark'; ?>" style="width: <?= $persen; ?>%;"></div>
                                                </div>
                                                <small class="fs-9 text-muted"><?= $persen; ?>%</small>
                                            <?php elseif ($isCuti && $pengganti !== ''): ?>
                                                <small class="fs-9 text-danger fw-semibold d-block mt-1">Pengganti: <?= htmlspecialchars($pengganti); ?></small>
                                            <?php else: ?>
                                                <small class="fs-9 text-danger fw-semibold d-block mt-1">Tidak Praktik</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="fw-bold text-dark fs-7">Siaga</div>
                                            <small class="fs-9 text-muted d-block mt-1"><?= htmlspecialchars($ketKuota); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$isCuti && $row['status_tugas'] == 'Aktif Bertugas'): ?>
                                            <span class="badge bg-lifora-primary-soft text-lifora-primary-dark rounded-pill px-2 py-1 fs-8"><span class="bg-lifora-primary rounded-circle d-inline-block me-1" style="width: 7px; height: 7px;"></span> Aktif</span>
                                        <?php elseif ($isCuti && $row['status_tugas'] == 'Sedang Cuti'): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2 py-1 fs-8"><span class="bg-danger rounded-circle d-inline-block me-1" style="width: 7px; height: 7px;"></span> Cuti<?= $sampai !== '' ? ' (s/d ' . htmlspecialchars($sampai) . ')' : ''; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted rounded-pill px-2 py-1 fs-8"><span class="bg-secondary rounded-circle d-inline-block me-1" style="width: 7px; height: 7px;"></span> Non-Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-3">
                                        <div class="d-flex justify-content-center align-items-center gap-2 text-muted fs-6">
                                            <a href="<?= htmlspecialchars(urlHal('shift', 1, $row['nip_sip'], '')); ?>" class="text-muted text-decoration-none" title="Jadwal Shift"><i class="fa-regular fa-calendar-days"></i></a>
                                            <button type="button" class="btn btn-link p-0 text-muted" title="Edit Data" data-edit="<?= $dataEdit; ?>"><i class="fa-regular fa-pen-to-square"></i></button>
                                            <form method="POST" onsubmit="return confirm('<?= $isCuti ? 'Aktifkan' : 'Nonaktifkan'; ?> dokter ini?');" class="d-inline">
                                                <input type="hidden" name="toggle_status" value="1">
                                                <input type="hidden" name="id" value="<?= (int) $row['id']; ?>">
                                                <input type="hidden" name="status_baru" value="<?= $isCuti ? 'aktif' : 'nonaktif'; ?>">
                                                <button type="submit" class="btn btn-link p-0 <?= $isCuti ? 'text-lifora-primary' : 'text-muted'; ?>" title="<?= $isCuti ? 'Aktifkan' : 'Nonaktifkan'; ?>">
                                                    <i class="fa-regular <?= $isCuti ? 'fa-circle-play' : 'fa-circle-pause'; ?>"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="7" class="text-center py-4 text-muted fs-7"><?= $tab === 'cuti' ? 'Tidak ada dokter yang sedang izin atau cuti.' : 'Data dokter tidak ditemukan.'; ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        <?php endif; ?>
                    </table>
                </div>

                <div class="card-footer bg-lifora-blue-soft border-top-0 py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                    <span class="fs-8 fw-semibold text-dark">Menampilkan <?= $jumlahTampil; ?> dari <?= $totalHasil; ?> Tenaga Dokter</span>
                    <div class="d-flex gap-1">
                        <?php if ($page > 1): ?>
                            <a class="btn btn-sm btn-light fw-bold rounded" href="<?= htmlspecialchars(urlHal($tab, $page - 1, $search, $poli_filter)); ?>"><i class="fa-solid fa-angle-left"></i></a>
                        <?php else: ?>
                            <span class="btn btn-sm btn-light fw-bold rounded disabled"><i class="fa-solid fa-angle-left"></i></span>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($totalHal, $page + 2); $i++): ?>
                            <a class="btn btn-sm <?= $i === $page ? 'bg-lifora-primary-dark text-white' : 'btn-light text-dark'; ?> fw-bold rounded" href="<?= htmlspecialchars(urlHal($tab, $i, $search, $poli_filter)); ?>"><?= $i; ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $totalHal): ?>
                            <a class="btn btn-sm btn-light fw-bold rounded" href="<?= htmlspecialchars(urlHal($tab, $page + 1, $search, $poli_filter)); ?>"><i class="fa-solid fa-angle-right"></i></a>
                        <?php else: ?>
                            <span class="btn btn-sm btn-light fw-bold rounded disabled"><i class="fa-solid fa-angle-right"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Matriks Beban Kerja (Sederhana) -->
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Matriks Beban Kerja Mingguan</h5>
                        <p class="fs-8 text-muted mb-0">Monitoring sebaran dokter agar tidak ada kekosongan ruang pelayanan.</p>
                    </div>
                    <div class="d-flex gap-3 fs-8 fw-semibold text-dark">
                        <span class="d-flex align-items-center gap-1"><span class="bg-lifora-primary-dark rounded" style="width: 10px; height: 10px; display: inline-block;"></span> Pagi</span>
                        <span class="d-flex align-items-center gap-1"><span class="bg-lifora-blue rounded" style="width: 10px; height: 10px; display: inline-block;"></span> Siang</span>
                        <span class="d-flex align-items-center gap-1"><span class="bg-lifora-blue-soft rounded" style="width: 10px; height: 10px; display: inline-block;"></span> Off</span>
                    </div>
                </div>
                <div class="row row-cols-2 row-cols-md-4 row-cols-lg-7 g-2">
                    <?php 
                    $matriks = [
                        1 => ['Senin', '9 Dokter', 85, 10], 2 => ['Selasa', '8 Dokter', 75, 15], 3 => ['Rabu', '8 Dokter', 70, 20],
                        4 => ['Kamis', '8 Dokter', 70, 20], 5 => ['Jumat', '7 Dokter', 40, 45], 6 => ['Sabtu', '5 Dokter', 20, 60],
                        0 => ['Minggu', 'UGD 24 Jam', 0, 0]
                    ];
                    $hariIni = (int) date('w');
                    foreach ([1,2,3,4,5,6,0] as $w): list($nm, $dr, $p, $s) = $matriks[$w]; $isToday = ($w === $hariIni); ?>
                    <div class="col">
                        <div class="bg-lifora-blue-soft rounded-3 p-2 text-center h-100 <?= $isToday ? 'border border-2 border-lifora-primary-soft bg-lifora-mint-bg' : ''; ?>">
                            <div class="fs-7 fw-bold text-dark mb-1"><?= $nm; ?> <?= $isToday ? '<span class="bg-lifora-primary rounded-circle d-inline-block ms-1" style="width: 6px; height: 6px;"></span>' : ''; ?></div>
                            <div class="fs-8 fw-semibold <?= $w === 0 ? 'text-danger' : 'text-lifora-blue'; ?> mb-2"><?= $dr; ?></div>
                            <?php if ($w === 0): ?>
                                <div class="progress mb-2" style="height: 5px;"><div class="progress-bar bg-danger" style="width: 30%;"></div></div>
                            <?php else: ?>
                                <div class="progress mb-2" style="height: 5px;">
                                    <div class="progress-bar bg-lifora-primary-dark" style="width: <?= $p; ?>%;"></div>
                                    <div class="progress-bar bg-lifora-blue" style="width: <?= $s; ?>%;"></div>
                                </div>
                            <?php endif; ?>
                            <small class="fs-9 text-muted"><?= $isToday ? 'Hari Ini' : ($w === 0 ? 'Darurat' : 'Kuota'); ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Modal Form Tambah / Edit Dokter -->
<div class="modal fade" id="mdlDokter" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" enctype="multipart/form-data" class="modal-content border-0 rounded-4 shadow" id="formDokter">
            <input type="hidden" name="id_dokter" value="0">
            
            <div class="modal-header bg-lifora-primary text-white border-bottom-0 rounded-top-4">
                <div>
                    <h5 class="modal-title fw-bold" id="dlgJudul">Tambah Dokter Baru</h5>
                    <p class="fs-8 mb-0 opacity-75" id="dlgSub">Lengkapi identitas, poli, dan jadwal praktik tenaga medis.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fs-8 fw-semibold text-dark">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-light border-0" id="f_nama" name="nama_dokter" required placeholder="dr. Siti Rahmawati, Sp.PD">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-8 fw-semibold text-dark">Spesialisasi / Jabatan</label>
                        <input type="text" class="form-control bg-light border-0" id="f_gelar" name="gelar_spesialis" placeholder="Dokter Spesialis Penyakit Dalam">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-8 fw-semibold text-dark">NIP / SIP Kemenkes <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-light border-0" id="f_nip" name="nip_sip" required placeholder="19850412 201101 2 003">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-8 fw-semibold text-dark">Poli <span class="text-danger">*</span></label>
                        <select class="form-select bg-light border-0" id="f_poli" name="poli" required>
                            <option value="">Pilih poli...</option>
                            <option value="Poli Umum">Poli Umum</option>
                            <option value="Poli Gigi & Mulut">Poli Gigi & Mulut</option>
                            <option value="Poli Lansia / Geriatri">Poli Lansia / Geriatri</option>
                            <option value="Poli KIA & Imunisasi">Poli KIA & Imunisasi</option>
                            <option value="UGD 24 Jam">UGD 24 Jam</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-8 fw-semibold text-dark">Status SatuSehat</label>
                        <input type="text" class="form-control bg-light border-0" id="f_sat" name="status_satusehat" value="Terkoneksi SatuSehat">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-8 fw-semibold text-dark">Hari Praktik</label>
                        <input type="text" class="form-control bg-light border-0" id="f_hari" name="hari_praktik" placeholder="Senin - Jumat">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-8 fw-semibold text-dark">Jam Praktik</label>
                        <input type="text" class="form-control bg-light border-0" id="f_jam" name="jam_praktik" placeholder="08.00 - 14.00 WIB">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-8 fw-semibold text-dark">Kuota Harian</label>
                        <input type="number" class="form-control bg-light border-0" id="f_kuota" name="kuota_harian" value="30" min="0">
                        <div class="fs-9 text-muted mt-1">Isi 0 untuk dokter siaga/UGD (tanpa batas kuota).</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-8 fw-semibold text-dark">Status Tugas</label>
                        <select class="form-select bg-light border-0" id="f_status" name="status_tugas">
                            <option value="Aktif Bertugas">Aktif Bertugas</option>
                            <option value="Sedang Cuti">Sedang Cuti</option>
                            <option value="Non-Aktif">Non-Aktif</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fs-8 fw-semibold text-dark">Keterangan Status</label>
                        <input type="text" class="form-control bg-light border-0" id="f_ket" name="keterangan_status" placeholder="Pengganti: Bdn. Dewi (s/d 27 Okt)">
                        <div class="fs-9 text-muted mt-1">Opsional. Untuk cuti: "Pengganti: Nama (s/d tanggal)". Untuk dokter siaga: "Siaga Tanpa Batas Kuota".</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fs-8 fw-semibold text-dark">Foto Dokter</label>
                        <input type="file" class="form-control bg-light border-0" id="f_foto" name="foto" accept="image/png,image/jpeg,image/webp">
                        <div class="fs-9 text-muted mt-1">Opsional. JPG, PNG, atau WEBP, maksimal 2 MB. Kosongkan jika tidak ingin mengganti foto.</div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer border-top-0 px-4 pb-4 d-flex justify-content-between">
                <button type="submit" name="hapus_dokter" value="1" class="btn btn-danger fw-semibold" id="btnHapus" formnovalidate hidden onclick="return confirm('Hapus dokter ini secara permanen?');">
                    <i class="fa-regular fa-trash-can me-1"></i> Hapus
                </button>
                <div class="d-flex gap-2 ms-auto">
                    <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_dokter" value="1" class="btn bg-lifora-primary text-white fw-semibold">
                        <i class="fa-solid fa-user-check me-1"></i> Simpan Dokter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../layout/components/admin/footer.php'; ?>

<script>
    // Popup tambah / edit dokter
    const dlg = document.getElementById('mdlDokter');
    const frm = document.getElementById('formDokter');
    const kolom = ['nama_dokter', 'gelar_spesialis', 'nip_sip', 'status_satusehat', 'poli', 'hari_praktik', 'jam_praktik', 'kuota_harian', 'status_tugas', 'keterangan_status'];

    function isiSelect(el, nilai) {
        if (nilai && ![...el.options].some(o => o.value === nilai)) el.add(new Option(nilai, nilai));
        el.value = nilai;
    }

    function bukaForm(data) {
        frm.reset();
        const edit = !!data;
        frm.elements['id_dokter'].value = edit ? data.id : 0;
        document.getElementById('dlgJudul').textContent = edit ? 'Edit Data Dokter' : 'Tambah Dokter Baru';
        document.getElementById('dlgSub').textContent = edit
            ? 'Perbarui data dokter, lalu simpan perubahan.'
            : 'Lengkapi identitas, poli, dan jadwal praktik tenaga medis.';
        document.getElementById('btnHapus').hidden = !edit;
        if (edit) {
            kolom.forEach(function (k) {
                const el = frm.elements[k];
                const nilai = data[k] === null || data[k] === undefined ? '' : data[k];
                if (el.tagName === 'SELECT') isiSelect(el, String(nilai)); else el.value = nilai;
            });
        }
        new bootstrap.Modal(dlg).show();
    }

    document.getElementById('btnTambah').addEventListener('click', function () { bukaForm(null); });
    document.querySelectorAll('[data-edit]').forEach(function (btn) {
        btn.addEventListener('click', function () { bukaForm(JSON.parse(btn.dataset.edit)); });
    });
</script>

</body>
</html>