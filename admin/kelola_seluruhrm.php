<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}
require_once '../koneksi.php';

/* ============================================================
 * HELPER
 * ============================================================ */
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function bulanId($n) {
    $b = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    return $b[$n - 1];
}
function tglWaktu($dt) {
    if (!$dt) return '-';
    $t = strtotime($dt);
    return date('d', $t) . ' ' . bulanId((int)date('n', $t)) . ' ' . date('Y', $t) . ' • ' . date('H:i', $t) . ' WIB';
}
function inisial($nama) {
    $w = preg_split('/\s+/', trim($nama));
    $a = strtoupper(substr($w[0] ?? '', 0, 1));
    $b = isset($w[1]) ? strtoupper(substr($w[1], 0, 1)) : '';
    return $a . $b;
}
function addLog($pdo, $id, $aksi, $ket) {
    $oleh = $_SESSION['nama_lengkap'] ?? $_SESSION['username'];
    $stmt = $pdo->prepare("INSERT INTO rekam_medis_log (rekam_id, aksi, keterangan, oleh) VALUES (?,?,?,?)");
    $stmt->execute([$id, $aksi, $ket, $oleh]);
}
function ketDefault($status, $dokter, $waktu) {
    $w = preg_split('/\s+/', trim($dokter));
    $nm = trim(($w[0] ?? '') . ' ' . ($w[1] ?? ''));
    if ($status === 'Lengkap & Terkunci') return 'TTE Sah • ' . $nm;
    if ($status === 'Selesai Ditandatangani') return 'Resume Terbit ' . date('H:i', strtotime($waktu)) . ' WIB';
    return 'Resep E-Prescribing dikirim';
}

/* ============================================================
 * KONSTANTA
 * ============================================================ */
$STATUS = ['Lengkap & Terkunci', 'Menunggu Validasi Farmasi', 'Selesai Ditandatangani'];
$JAMINAN = ['BPJS PBI', 'BPJS Non-PBI', 'Umum / Mandiri', 'Asuransi Lain'];
$POLI = ['Poli Umum', 'Poli Gigi & Mulut', 'Poli KIA & Imunisasi', 'Poli Lansia / Geriatri', 'UGD 24 Jam'];
$KATEGORI = [
    'Infeksi & Parasit'           => 'AB',
    'Endokrin & Metabolik'        => 'E',
    'Sirkulasi / Kardiovaskular'  => 'I',
    'Pernapasan'                  => 'J',
    'Pencernaan & Gigi'           => 'K',
];

/* ============================================================
 * ENDPOINT RIWAYAT AUDIT (AJAX / JSON)
 * ============================================================ */
if (isset($_GET['log'])) {
    $stmt = $pdo->prepare("SELECT aksi, keterangan, oleh, DATE_FORMAT(waktu, '%d-%m-%Y %H:%i') AS waktu FROM rekam_medis_log WHERE rekam_id = ? ORDER BY id DESC");
    $stmt->execute([(int)$_GET['log']]);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($stmt->fetchAll());
    exit;
}

/* ============================================================
 * PROSES CRUD (POST)
 * ============================================================ */
function ambilForm() {
    $p = fn($k) => trim($_POST[$k] ?? '');
    $waktu = str_replace('T', ' ', $p('waktu_kunjungan'));
    if (strlen($waktu) === 16) $waktu .= ':00';
    return [
        'waktu' => $waktu, 'no_rm' => $p('no_rm'), 'nama' => $p('nama_pasien'), 'nik' => $p('nik'),
        'jk' => ($p('jenis_kelamin') === 'P') ? 'P' : 'L', 'usia' => max(0, (int)$p('usia')),
        'jaminan' => $p('jaminan'), 'poli' => $p('poli'), 'dokter' => $p('dokter'), 'sip' => $p('sip_dokter'),
        'kode' => strtoupper($p('kode_icd')), 'diag' => $p('nama_diagnosis'), 'ket' => $p('keterangan_klinis'),
        'status' => $p('status_berkas'), 'ketstatus' => $p('ket_status'), 'sat' => isset($_POST['satusehat']) ? 1 : 0,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];
    $pesan = ['ok', ''];

    if ($aksi === 'simpan' || $aksi === 'ubah') {
        $f = ambilForm();
        $valid = $f['no_rm'] !== '' && $f['nama'] !== '' && $f['poli'] !== '' && $f['dokter'] !== ''
              && $f['kode'] !== '' && $f['diag'] !== '' && strtotime($f['waktu']) !== false && in_array($f['status'], $STATUS, true);
        
        if (!$valid) {
            $pesan = ['err', 'Data belum lengkap. Periksa kolom yang bertanda (*).'];
        } else {
            if ($f['ketstatus'] === '') $f['ketstatus'] = ketDefault($f['status'], $f['dokter'], $f['waktu']);

            if ($aksi === 'simpan') {
                $tgl = date('Ymd', strtotime($f['waktu']));
                // Cari nomor registrasi unik
                $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM rekam_medis WHERE DATE(waktu_kunjungan) = ?");
                $stmtCount->execute([date('Y-m-d', strtotime($f['waktu']))]);
                $n = (int)$stmtCount->fetchColumn() + 1;
                
                do {
                    $noreg = "REG-$tgl-" . str_pad($n++, 4, '0', STR_PAD_LEFT);
                    $stmtCek = $pdo->prepare("SELECT id FROM rekam_medis WHERE no_registrasi = ?");
                    $stmtCek->execute([$noreg]);
                } while ($stmtCek->fetch());

                try {
                    $stmt = $pdo->prepare("INSERT INTO rekam_medis (no_registrasi, waktu_kunjungan, no_rm, nama_pasien, nik, jenis_kelamin, usia, jaminan, poli, dokter, sip_dokter, kode_icd, nama_diagnosis, keterangan_klinis, status_berkas, ket_status, satusehat) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$noreg, $f['waktu'], $f['no_rm'], $f['nama'], $f['nik'], $f['jk'], $f['usia'], $f['jaminan'], $f['poli'], $f['dokter'], $f['sip'], $f['kode'], $f['diag'], $f['ket'], $f['status'], $f['ketstatus'], $f['sat']]);
                    $newId = $pdo->lastInsertId();
                    addLog($pdo, $newId, 'Dibuat', "Rekam medis $noreg dibuat (diagnosis {$f['kode']}).");
                    $pesan = ['ok', "Rekam medis $noreg berhasil ditambahkan."];
                } catch (PDOException $e) {
                    $pesan = ['err', 'Gagal menyimpan: ' . $e->getMessage()];
                }
            } else {
                $id = (int)($_POST['id'] ?? 0);
                try {
                    $stmt = $pdo->prepare("UPDATE rekam_medis SET waktu_kunjungan=?, no_rm=?, nama_pasien=?, nik=?, jenis_kelamin=?, usia=?, jaminan=?, poli=?, dokter=?, sip_dokter=?, kode_icd=?, nama_diagnosis=?, keterangan_klinis=?, status_berkas=?, ket_status=?, satusehat=? WHERE id=?");
                    $stmt->execute([$f['waktu'], $f['no_rm'], $f['nama'], $f['nik'], $f['jk'], $f['usia'], $f['jaminan'], $f['poli'], $f['dokter'], $f['sip'], $f['kode'], $f['diag'], $f['ket'], $f['status'], $f['ketstatus'], $f['sat'], $id]);
                    addLog($pdo, $id, 'Diubah', "Data diubah. Status berkas: {$f['status']}.");
                    $pesan = ['ok', 'Rekam medis berhasil diperbarui.'];
                } catch (PDOException $e) {
                    $pesan = ['err', 'Gagal memperbarui: ' . $e->getMessage()];
                }
            }
        }
    } elseif ($aksi === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT no_registrasi FROM rekam_medis WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            try {
                $stmt = $pdo->prepare("DELETE FROM rekam_medis WHERE id = ?");
                $stmt->execute([$id]);
                addLog($pdo, $id, 'Dihapus', "Rekam medis {$row['no_registrasi']} dihapus.");
                $pesan = ['ok', "Rekam medis {$row['no_registrasi']} berhasil dihapus."];
            } catch (PDOException $e) {
                $pesan = ['err', 'Gagal menghapus: ' . $e->getMessage()];
            }
        } else {
            $pesan = ['err', 'Data tidak ditemukan.'];
        }
    }

    $_SESSION['flash_rm'] = $pesan;
    $balik = $_POST['kembali'] ?? '';
    header('Location: kelola_seluruhrm.php' . ($balik !== '' ? '?' . $balik : ''));
    exit;
}

$flash = $_SESSION['flash_rm'] ?? null;
unset($_SESSION['flash_rm']);

/* ============================================================
 * FILTER, PENCARIAN, PAGINATION (GET)
 * ============================================================ */
$q       = trim($_GET['q'] ?? '');
$periode = $_GET['periode'] ?? ($q !== '' ? 'semua' : 'hari');
if (!in_array($periode, ['hari', '7hari', 'bulan', 'kustom', 'semua'], true)) $periode = 'hari';
$dari    = $_GET['dari']   ?? '';
$sampai  = $_GET['sampai'] ?? '';
$fPoli   = $_GET['poli']   ?? '';
$fDokter = $_GET['dokter'] ?? '';
$fKat    = $_GET['kat']    ?? '';
$baris   = (int)($_GET['baris'] ?? 10);
if (!in_array($baris, [10, 25, 50], true)) $baris = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));

$where = []; $params = [];
if ($q !== '') {
    $where[] = "(no_registrasi LIKE ? OR no_rm LIKE ? OR nama_pasien LIKE ? OR nik LIKE ?)";
    $like = "%$q%"; array_push($params, $like, $like, $like, $like);
}
if ($periode === 'hari')       $where[] = "DATE(waktu_kunjungan) = CURDATE()";
elseif ($periode === '7hari')  $where[] = "DATE(waktu_kunjungan) >= CURDATE() - INTERVAL 6 DAY";
elseif ($periode === 'bulan')  $where[] = "YEAR(waktu_kunjungan) = YEAR(CURDATE()) AND MONTH(waktu_kunjungan) = MONTH(CURDATE())";
elseif ($periode === 'kustom') {
    if ($dari !== '')   { $where[] = "DATE(waktu_kunjungan) >= ?"; $params[] = $dari; }
    if ($sampai !== '') { $where[] = "DATE(waktu_kunjungan) <= ?"; $params[] = $sampai; }
}
if ($fPoli !== '')   { $where[] = "poli = ?";   $params[] = $fPoli; }
if ($fDokter !== '') { $where[] = "dokter = ?"; $params[] = $fDokter; }
if ($fKat !== '' && isset($KATEGORI[$fKat])) $where[] = "kode_icd REGEXP '^[" . $KATEGORI[$fKat] . "]'";
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM rekam_medis $whereSql");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalHal = max(1, (int)ceil($total / $baris));
$page = min($page, $totalHal);
$offset = ($page - 1) * $baris;

$stmtRows = $pdo->prepare("SELECT * FROM rekam_medis $whereSql ORDER BY waktu_kunjungan DESC, id DESC LIMIT $baris OFFSET $offset");
$stmtRows->execute($params);
$rows = $stmtRows->fetchAll();
$jumlahTampil = count($rows);

function qs($ganti = []) {
    $p = array_merge($_GET, $ganti);
    unset($p['log']);
    foreach ($p as $k => $v) if ($v === '' || $v === null) unset($p[$k]);
    return http_build_query($p);
}

/* ============================================================
 * STATISTIK KARTU ATAS
 * ============================================================ */
$stmtStat = $pdo->query("SELECT COUNT(*) t,
    COALESCE(SUM(status_berkas <> 'Menunggu Validasi Farmasi'),0) lengkap,
    COALESCE(SUM(status_berkas = 'Menunggu Validasi Farmasi'),0) pending,
    COALESCE(SUM(status_berkas IN ('Lengkap & Terkunci','Selesai Ditandatangani')),0) terkunci
    FROM rekam_medis");
$st = $stmtStat->fetch();
$sTotal = (int)$st['t']; $sLengkap = (int)$st['lengkap']; $sPending = (int)$st['pending']; $sTerkunci = (int)$st['terkunci'];
$pctLengkap = $sTotal ? round($sLengkap / $sTotal * 100, 1) : 0;

function pctHari($pdo, $offsetHari) {
    $stmt = $pdo->prepare("SELECT COUNT(*) t, COALESCE(SUM(status_berkas <> 'Menunggu Validasi Farmasi'),0) l FROM rekam_medis WHERE DATE(waktu_kunjungan) = CURDATE() - INTERVAL ? DAY");
    $stmt->execute([$offsetHari]);
    $r = $stmt->fetch();
    return ((int)$r['t'] > 0) ? ($r['l'] / $r['t'] * 100) : null;
}
$pHari = pctHari($pdo, 0); $pKemarin = pctHari($pdo, 1);
if ($pHari !== null && $pKemarin !== null) {
    $delta = round($pHari - $pKemarin, 1);
    $teksDelta = ($delta >= 0 ? '+' : '') . $delta . '% dari kemarin';
} else {
    $teksDelta = 'Belum ada data pembanding';
}

$stmtTop = $pdo->query("SELECT kode_icd, nama_diagnosis, COUNT(*) c FROM rekam_medis GROUP BY kode_icd, nama_diagnosis ORDER BY c DESC LIMIT 2");
$top = $stmtTop->fetchAll();
$top1 = $top[0] ?? null; $top2 = $top[1] ?? null;
$p1 = ($top1 && $sTotal) ? round($top1['c'] / $sTotal * 100, 1) : 0;
$p2 = ($top2 && $sTotal) ? round($top2['c'] / $sTotal * 100) : 0;

// Data dropdown form
$listDokter = $pdo->query("SELECT nama_dokter, nip_sip, poli FROM dokter ORDER BY nama_dokter ASC")->fetchAll();
$listPasien = $pdo->query("SELECT no_rm, nama_lengkap, nik_identitas, jenis_kelamin, usia, jenis_jaminan FROM pasien ORDER BY nama_lengkap ASC LIMIT 500")->fetchAll();
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
                <div class="alert <?= $flash[0] === 'ok' ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show rounded-4" role="alert">
                    <i class="fa-solid <?= $flash[0] === 'ok' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> me-2"></i>
                    <?= h($flash[1]); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Header Page -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
                <div>
                    <h1 class="fw-bold text-dark mb-2" style="letter-spacing: -0.02em;">Kelola Seluruh Arsip Rekam Medis</h1>
                    <span class="badge bg-lifora-primary-soft text-lifora-primary-dark rounded-pill px-3 py-2 mb-2 fs-8"><i class="fa-solid fa-circle fs-9 me-1"></i> Integrasi SatuSehat Aktif</span>
                    <p class="text-muted fs-7 mb-0" style="max-width: 600px;">Pusat pengawasan rekam medis elektronik terpusat, kepatuhan ICD-10, validasi digital resume medis, dan jejak audit faskes.</p>
                </div>
                <button type="button" class="btn bg-lifora-primary text-white fw-semibold px-3 py-2 shadow-sm flex-shrink-0" onclick="bukaForm()">
                    <i class="fa-solid fa-plus me-2"></i> Catat RM Baru
                </button>
            </div>

            <!-- Statistik -->
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="text-muted fs-8 fw-semibold text-uppercase" style="max-width: 130px; line-height: 1.35;">Kelengkapan Berkas RM</div>
                                <div class="bg-lifora-primary-soft text-lifora-primary rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fa-solid fa-gear fs-5"></i></div>
                            </div>
                            <div class="fs-1 fw-bold text-lifora-primary-dark lh-1 mb-2"><?= $pctLengkap; ?>%</div>
                            <div class="progress mb-2" style="height: 6px;"><div class="progress-bar bg-lifora-primary-dark" style="width: <?= $pctLengkap; ?>%;"></div></div>
                            <div class="d-flex justify-content-between fs-8 fw-semibold text-dark">
                                <span>Total: <?= $sLengkap; ?> / <?= $sTotal; ?></span>
                                <span class="text-lifora-primary"><?= h($teksDelta); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="text-muted fs-8 fw-semibold text-uppercase" style="max-width: 130px; line-height: 1.35;">Validasi Farmasi Pending</div>
                                <div class="bg-lifora-blue-badge text-lifora-blue rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fa-solid fa-clipboard-check fs-5"></i></div>
                            </div>
                            <div class="fs-1 fw-bold text-lifora-blue lh-1 mb-2"><?= $sPending; ?></div>
                            <div class="fs-8 fw-semibold text-dark mt-2"><i class="fa-solid fa-circle-info text-lifora-blue me-1"></i> Membutuhkan telaah resep</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="text-muted fs-8 fw-semibold text-uppercase" style="max-width: 130px; line-height: 1.35;">Terkunci & TTE Sah</div>
                                <div class="bg-lifora-primary-soft text-lifora-primary rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fa-solid fa-lock fs-5"></i></div>
                            </div>
                            <div class="fs-1 fw-bold text-dark lh-1 mb-2"><?= $sTerkunci; ?></div>
                            <div class="fs-8 fw-semibold text-dark mt-2"><i class="fa-regular fa-circle-check text-lifora-primary me-1"></i> Sesuai Permenkes 24/2022</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="text-muted fs-8 fw-semibold text-uppercase" style="max-width: 130px; line-height: 1.35;">Diagnosa Tertinggi</div>
                                <div class="bg-lifora-blue-badge text-lifora-blue rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fa-solid fa-chart-line fs-5"></i></div>
                            </div>
                            <div class="fs-5 fw-bold text-dark lh-sm mb-2"><?= $top1 ? h($top1['nama_diagnosis']) . ' (' . h($top1['kode_icd']) . ')' : '-'; ?></div>
                            <div class="d-flex justify-content-between align-items-center fs-8 fw-semibold text-dark mt-1">
                                <span class="d-flex align-items-center gap-1"><span class="bg-primary rounded-circle d-inline-block" style="width: 8px; height: 8px;"></span> <?= $top2 ? h($top2['nama_diagnosis']) . ': ' . $p2 . '%' : 'Belum ada'; ?></span>
                                <span class="text-lifora-primary"><?= $p1; ?>% Dominan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <form method="GET" action="kelola_seluruhrm.php" id="filterForm" class="card border-0 shadow-sm rounded-4 p-3">
                <input type="hidden" name="baris" id="barisHidden" value="<?= $baris; ?>">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-md-6">
                        <div class="position-relative">
                            <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left: 14px; top: 50%; transform: translateY(-50%);"></i>
                            <input type="text" name="q" value="<?= h($q); ?>" class="form-control bg-lifora-blue-soft border-0 py-2 ps-5" placeholder="Cari No. Registrasi, No. RM, atau Nama Pasien...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex bg-lifora-blue-soft rounded-3 p-1 gap-1">
                            <button type="submit" name="periode" value="hari"  class="btn btn-sm flex-fill <?= $periode === 'hari'  ? 'bg-white text-lifora-primary-dark fw-bold shadow-sm' : 'text-dark'; ?>">Hari Ini</button>
                            <button type="submit" name="periode" value="7hari" class="btn btn-sm flex-fill <?= $periode === '7hari' ? 'bg-white text-lifora-primary-dark fw-bold shadow-sm' : 'text-dark'; ?>">7 Hari</button>
                            <button type="submit" name="periode" value="bulan" class="btn btn-sm flex-fill <?= $periode === 'bulan' ? 'bg-white text-lifora-primary-dark fw-bold shadow-sm' : 'text-dark'; ?>">Bulan Ini</button>
                            <button type="button" id="btnKustom" class="btn btn-sm flex-fill <?= $periode === 'kustom' ? 'bg-white text-lifora-primary-dark fw-bold shadow-sm' : 'text-dark'; ?>"><i class="fa-regular fa-calendar me-1"></i> Kustom</button>
                        </div>
                    </div>
                </div>

                <div class="row g-2 mb-3" id="kustomRow" style="display: <?= $periode === 'kustom' ? 'flex' : 'none'; ?>;">
                    <div class="col-auto d-flex align-items-center fs-8 fw-semibold text-muted">Dari</div>
                    <div class="col-auto"><input type="date" name="dari" value="<?= h($dari); ?>" class="form-control form-control-sm bg-lifora-blue-soft border-0"></div>
                    <div class="col-auto d-flex align-items-center fs-8 fw-semibold text-muted">sampai</div>
                    <div class="col-auto"><input type="date" name="sampai" value="<?= h($sampai); ?>" class="form-control form-control-sm bg-lifora-blue-soft border-0"></div>
                    <div class="col-auto"><button type="submit" name="periode" value="kustom" class="btn btn-sm btn-light fw-semibold">Terapkan</button></div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fs-9 fw-bold text-muted text-uppercase mb-1">Poli Layanan</label>
                        <select name="poli" class="form-select bg-lifora-blue-soft border-0 py-2 fw-medium" onchange="this.form.submit()">
                            <option value="">Semua Poli Pelayanan</option>
                            <?php foreach ($POLI as $p): ?>
                                <option value="<?= h($p); ?>" <?= $fPoli === $p ? 'selected' : ''; ?>><?= h($p); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fs-9 fw-bold text-muted text-uppercase mb-1">Dokter DPJP</label>
                        <select name="dokter" class="form-select bg-lifora-blue-soft border-0 py-2 fw-medium" onchange="this.form.submit()">
                            <option value="">Semua Dokter</option>
                            <?php foreach ($listDokter as $d): ?>
                                <option value="<?= h($d['nama_dokter']); ?>" <?= $fDokter === $d['nama_dokter'] ? 'selected' : ''; ?>><?= h($d['nama_dokter']); ?></option>
                            <?php endforeach; ?>
                            <?php if ($fDokter !== '' && !in_array($fDokter, array_column($listDokter, 'nama_dokter'), true)): ?>
                                <option value="<?= h($fDokter); ?>" selected><?= h($fDokter); ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fs-9 fw-bold text-muted text-uppercase mb-1">Kategori ICD-10</label>
                        <select name="kat" class="form-select bg-lifora-blue-soft border-0 py-2 fw-medium" onchange="this.form.submit()">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($KATEGORI as $nama => $huruf): ?>
                                <option value="<?= h($nama); ?>" <?= $fKat === $nama ? 'selected' : ''; ?>><?= h($nama); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>

            <!-- Tabel Rekam Medis -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-lifora-blue-soft border-bottom-0 py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="fw-bold text-dark mb-0"><i class="fa-regular fa-folder-open text-lifora-primary me-2"></i> Master Rekam Medis</h5>
                        <span class="badge bg-lifora-blue-badge text-dark rounded-pill fs-9"><?= $jumlahTampil; ?> Ditampilkan</span>
                    </div>
                    <div class="fs-8 text-muted">Faskes: <b>Puskesmas Melati Sejahtera</b></div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="min-width: 1050px;">
                        <thead class="bg-lifora-blue-soft">
                            <tr>
                                <th class="fs-9 text-muted text-uppercase fw-bold py-3 ps-3" style="width:150px;">No. Reg RM & Waktu</th>
                                <th class="fs-9 text-muted text-uppercase fw-bold py-3" style="width:270px;">Pasien & No. RM</th>
                                <th class="fs-9 text-muted text-uppercase fw-bold py-3" style="width:150px;">Poli & DPJP</th>
                                <th class="fs-9 text-muted text-uppercase fw-bold py-3">Diagnosis Utama (ICD-10)</th>
                                <th class="fs-9 text-muted text-uppercase fw-bold py-3" style="width:190px;">Status Berkas</th>
                                <th class="fs-9 text-muted text-uppercase fw-bold py-3 text-center pe-3" style="width:140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($jumlahTampil > 0): foreach ($rows as $r):
                            $jamCls = strpos($r['jaminan'], 'BPJS') !== false ? '' : (strpos($r['jaminan'], 'Asuransi') !== false ? 'asuransi' : 'umum');
                            $sbCls  = $r['status_berkas'] === 'Lengkap & Terkunci' ? 'lengkap' : ($r['status_berkas'] === 'Selesai Ditandatangani' ? 'selesai' : 'menunggu');
                            $sbIco  = $sbCls === 'menunggu' ? 'fa-hourglass-half' : ($sbCls === 'lengkap' ? 'fa-lock' : 'fa-pen-nib');
                            $json = h(json_encode($r, JSON_UNESCAPED_UNICODE));
                        ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-lifora-primary-dark fs-7" style="line-height:1.35;word-break:break-all;"><?= h($r['no_registrasi']); ?></div>
                                    <div class="fs-8 fw-semibold text-dark mt-1"><?= h(tglWaktu($r['waktu_kunjungan'])); ?></div>
                                    <?php if ($r['satusehat']): ?>
                                        <div class="fs-8 text-lifora-primary mt-2"><i class="fa-solid fa-check-double me-1"></i> SatuSehat</div>
                                    <?php else: ?>
                                        <div class="fs-8 text-muted mt-2"><i class="fa-regular fa-clock me-1"></i> Belum</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 align-items-start">
                                        <div class="bg-lifora-blue-badge text-dark rounded-circle fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;font-size:11px;"><?= h(inisial($r['nama_pasien'])); ?></div>
                                        <div style="flex:1">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <strong class="fs-7 fw-medium text-dark"><?= h($r['nama_pasien']); ?></strong>
                                                <span class="badge bg-lifora-blue-soft text-muted fs-9"><?= h($r['jenis_kelamin']); ?> / <?= (int)$r['usia']; ?> Th</span>
                                            </div>
                                            <small class="d-block fs-8 text-muted mt-1">No. RM: <b class="text-dark"><?= h($r['no_rm']); ?></b></small>
                                            <?php if ($r['nik'] && $r['nik'] !== '-'): ?><small class="d-block fs-8 text-muted">NIK: <?= h($r['nik']); ?></small><?php endif; ?>
                                            <span class="badge <?= $jamCls === 'asuransi' ? 'bg-lifora-primary-soft text-lifora-primary-dark' : ($jamCls === 'umum' ? 'bg-lifora-blue-soft text-muted' : 'bg-lifora-blue-badge text-lifora-blue'); ?> rounded-pill mt-2 fs-9 px-2"><?= h($r['jaminan']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fs-7 fw-medium text-dark" style="line-height:1.35;"><?= h($r['poli']); ?></div>
                                    <div class="fs-8 text-muted mt-1" style="line-height:1.5;"><?= h($r['dokter']); ?><?php if ($r['sip_dokter']): ?><br>SIP: <?= h($r['sip_dokter']); ?><?php endif; ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-lifora-blue-badge text-dark rounded px-2 py-1 fs-8"><span class="bg-secondary rounded-circle d-inline-block me-1" style="width:4px;height:4px;"></span> <?= h($r['kode_icd']); ?> <i class="fa-solid fa-circle fs-9 text-muted mx-1"></i> <?= h($r['nama_diagnosis']); ?></span>
                                    <div class="fs-8 text-muted mt-2" style="line-height:1.5;"><?= h($r['keterangan_klinis']); ?></div>
                                </td>
                                <td>
                                    <span class="badge <?= $sbCls === 'menunggu' ? 'bg-lifora-blue-badge text-dark' : 'bg-lifora-primary-soft text-lifora-primary-dark'; ?> rounded-pill px-2 py-1 fs-8"><i class="fa-solid <?= $sbIco; ?> me-1"></i> <?= h($r['status_berkas']); ?></span>
                                    <div class="fs-8 text-muted mt-2 <?= $sbCls === 'menunggu' ? 'text-lifora-blue' : ''; ?>" style="line-height:1.5;"><?= h($r['ket_status']); ?></div>
                                </td>
                                <td class="text-center pe-3">
                                    <div class="d-flex justify-content-center gap-1" data-rm="<?= $json; ?>">
                                        <button type="button" class="btn btn-sm bg-lifora-blue-soft text-lifora-primary-dark rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;" title="Detail" onclick="bukaDetail(this)"><i class="fa-regular fa-folder-open"></i></button>
                                        <button type="button" class="btn btn-sm bg-lifora-blue-soft text-lifora-blue rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;" title="Cetak" onclick="cetakRm(this)"><i class="fa-solid fa-print"></i></button>
                                        <button type="button" class="btn btn-sm bg-lifora-blue-soft text-muted rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;" title="Audit Trail" onclick="bukaRiwayat(this)"><i class="fa-solid fa-clock-rotate-left"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fa-regular fa-folder-open fs-2 mb-2 d-block"></i>Belum ada rekam medis pada filter ini.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-lifora-blue-soft border-top-0 py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <div class="fs-8 fw-medium text-dark d-flex align-items-center gap-2 flex-wrap">
                        Menampilkan <?= $total ? $offset + 1 : 0; ?> - <?= $offset + $jumlahTampil; ?> dari <?= $total; ?> rekam medis <span>•</span> Baris:
                        <select class="form-select form-select-sm bg-white border-0 fw-bold text-lifora-blue" style="width:auto;" onchange="document.getElementById('barisHidden').value=this.value;document.getElementById('filterForm').submit()">
                            <?php foreach ([10, 25, 50] as $b): ?>
                                <option value="<?= $b; ?>" <?= $baris === $b ? 'selected' : ''; ?>><?= $b; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <a class="btn btn-sm btn-light fw-bold rounded <?= $page <= 1 ? 'disabled text-muted' : 'text-dark'; ?>" href="?<?= qs(['page' => $page - 1]); ?>"><i class="fa-solid fa-angle-left"></i></a>
                        <?php
                        $tampil = [];
                        for ($i = 1; $i <= $totalHal; $i++) {
                            if ($i <= 3 || $i === $totalHal || abs($i - $page) <= 1) $tampil[] = $i;
                        }
                        $prev = 0;
                        foreach ($tampil as $i) {
                            if ($prev && $i - $prev > 1) echo '<span class="text-muted px-1">...</span>';
                            echo '<a class="btn btn-sm ' . ($i === $page ? 'bg-lifora-primary-dark text-white' : 'btn-light text-dark') . ' fw-bold rounded" href="?' . qs(['page' => $i]) . '">' . $i . '</a>';
                            $prev = $i;
                        }
                        ?>
                        <a class="btn btn-sm btn-light fw-bold rounded <?= $page >= $totalHal ? 'disabled text-muted' : 'text-dark'; ?>" href="?<?= qs(['page' => $page + 1]); ?>"><i class="fa-solid fa-angle-right"></i></a>
                    </div>
                </div>
            </div>

        </div> <!-- End Container -->
    </main>
</div>

<!-- Modal Form (Tambah/Ubah) -->
<div class="modal fade" id="mdlForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content border-0 rounded-4 shadow" method="POST" action="kelola_seluruhrm.php" id="formRm">
            <input type="hidden" name="aksi" id="f_aksi" value="simpan">
            <input type="hidden" name="id" id="f_id" value="">
            <input type="hidden" name="kembali" value="<?= h(qs()); ?>">
            
            <div class="modal-header bg-lifora-primary text-white border-bottom-0 rounded-top-4">
                <div>
                    <h5 class="modal-title fw-bold" id="formJudul">Catat Rekam Medis Baru</h5>
                    <p class="fs-8 mb-0 opacity-75">Lengkapi data kunjungan, diagnosis ICD-10, dan status berkas.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                <div class="row g-3">
                    <div class="col-12"><div class="fs-9 fw-bold text-lifora-primary-dark text-uppercase mb-1">Data Pasien</div></div>
                    
                    <div class="col-12">
                        <label class="form-label fs-8 fw-semibold text-muted">Pilih dari Data Pasien Terdaftar (opsional)</label>
                        <select id="f_pilih" class="form-select bg-light border-0">
                            <option value="">-- Ketik manual / pilih pasien --</option>
                            <?php foreach ($listPasien as $p): ?>
                                <option value="<?= h($p['no_rm']); ?>"
                                    data-nama="<?= h($p['nama_lengkap']); ?>" data-nik="<?= h($p['nik_identitas']); ?>"
                                    data-jk="<?= $p['jenis_kelamin'] === 'Perempuan' ? 'P' : 'L'; ?>" data-usia="<?= (int)$p['usia']; ?>"
                                    data-jaminan="<?= h($p['jenis_jaminan'] === 'BPJS Kesehatan' ? 'BPJS PBI' : $p['jenis_jaminan']); ?>">
                                    <?= h($p['no_rm'] . ' — ' . $p['nama_lengkap']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6"><label class="form-label fs-8 fw-semibold text-muted">No. Rekam Medis <span class="text-danger">*</span></label><input type="text" name="no_rm" id="f_no_rm" class="form-control bg-light border-0" required></div>
                    <div class="col-md-6"><label class="form-label fs-8 fw-semibold text-muted">Nama Lengkap Pasien <span class="text-danger">*</span></label><input type="text" name="nama_pasien" id="f_nama" class="form-control bg-light border-0" required></div>
                    <div class="col-md-6"><label class="form-label fs-8 fw-semibold text-muted">NIK</label><input type="text" name="nik" id="f_nik" maxlength="16" class="form-control bg-light border-0"></div>
                    <div class="col-md-3"><label class="form-label fs-8 fw-semibold text-muted">Kelamin</label><select name="jenis_kelamin" id="f_jk" class="form-select bg-light border-0"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></div>
                    <div class="col-md-3"><label class="form-label fs-8 fw-semibold text-muted">Usia (Th)</label><input type="number" name="usia" id="f_usia" min="0" max="130" value="0" class="form-control bg-light border-0"></div>
                    <div class="col-12"><label class="form-label fs-8 fw-semibold text-muted">Jaminan</label>
                        <select name="jaminan" id="f_jaminan" class="form-select bg-light border-0">
                            <?php foreach ($JAMINAN as $j): ?><option value="<?= h($j); ?>"><?= h($j); ?></option><?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 mt-4"><div class="fs-9 fw-bold text-lifora-primary-dark text-uppercase mb-1">Kunjungan & Dokter</div></div>
                    
                    <div class="col-md-6"><label class="form-label fs-8 fw-semibold text-muted">Waktu Kunjungan <span class="text-danger">*</span></label><input type="datetime-local" name="waktu_kunjungan" id="f_waktu" class="form-control bg-light border-0" required></div>
                    <div class="col-md-6"><label class="form-label fs-8 fw-semibold text-muted">Poli Tujuan <span class="text-danger">*</span></label>
                        <select name="poli" id="f_poli" class="form-select bg-light border-0" required>
                            <?php foreach ($POLI as $p): ?><option value="<?= h($p); ?>"><?= h($p); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label fs-8 fw-semibold text-muted">Dokter DPJP <span class="text-danger">*</span></label>
                        <select name="dokter" id="f_dokter" class="form-select bg-light border-0" required>
                            <option value="">-- Pilih dokter --</option>
                            <?php foreach ($listDokter as $d): ?>
                                <option value="<?= h($d['nama_dokter']); ?>" data-sip="<?= h($d['nip_sip']); ?>" data-poli="<?= h($d['poli']); ?>"><?= h($d['nama_dokter']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label fs-8 fw-semibold text-muted">SIP Dokter</label><input type="text" name="sip_dokter" id="f_sip" class="form-control bg-light border-0"></div>

                    <div class="col-12 mt-4"><div class="fs-9 fw-bold text-lifora-primary-dark text-uppercase mb-1">Diagnosis & Status</div></div>
                    
                    <div class="col-md-6"><label class="form-label fs-8 fw-semibold text-muted">Kode ICD-10 <span class="text-danger">*</span></label><input type="text" name="kode_icd" id="f_kode" class="form-control bg-light border-0" required placeholder="J06.9"></div>
                    <div class="col-md-6"><label class="form-label fs-8 fw-semibold text-muted">Nama Diagnosis <span class="text-danger">*</span></label><input type="text" name="nama_diagnosis" id="f_diag" class="form-control bg-light border-0" required placeholder="ISPA Akut"></div>
                    <div class="col-12"><label class="form-label fs-8 fw-semibold text-muted">Keterangan Klinis</label><textarea name="keterangan_klinis" id="f_ket" class="form-control bg-light border-0" rows="2" placeholder="Contoh: Tekanan Darah 150/95 mmHg"></textarea></div>
                    <div class="col-md-6"><label class="form-label fs-8 fw-semibold text-muted">Status Berkas RM <span class="text-danger">*</span></label>
                        <select name="status_berkas" id="f_status" class="form-select bg-light border-0">
                            <?php foreach ($STATUS as $s): ?><option value="<?= h($s); ?>"><?= h($s); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label fs-8 fw-semibold text-muted">Keterangan Status</label><input type="text" name="ket_status" id="f_ketstatus" class="form-control bg-light border-0" placeholder="Otomatis jika kosong"></div>
                    <div class="col-12"><div class="form-check"><input type="checkbox" name="satusehat" id="f_sat" value="1" class="form-check-input" checked><label class="form-check-label fs-8" for="f_sat">Sudah terkirim ke SatuSehat Kemenkes</label></div></div>
                </div>
            </div>
            
            <div class="modal-footer border-top-0 px-4 pb-4">
                <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn bg-lifora-primary text-white fw-semibold"><i class="fa-regular fa-floppy-disk me-1"></i> <span id="formTombol">Simpan Rekam Medis</span></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="mdlDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-lifora-primary text-white border-bottom-0 rounded-top-4">
                <div><h5 class="modal-title fw-bold" id="d_judul">Detail Rekam Medis</h5><p class="fs-8 mb-0 opacity-75" id="d_sub"></p></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4"><div class="row g-3" id="d_isi"></div></div>
            <div class="modal-footer border-top-0 px-4 pb-4">
                <button type="button" class="btn btn-danger fw-semibold" id="d_hapus"><i class="fa-regular fa-trash-can me-1"></i> Hapus</button>
                <button type="button" class="btn btn-light fw-semibold" id="d_cetak"><i class="fa-solid fa-print me-1"></i> Cetak</button>
                <button type="button" class="btn bg-lifora-primary text-white fw-semibold" id="d_ubah"><i class="fa-regular fa-pen-to-square me-1"></i> Ubah Data</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="mdlHapus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form class="modal-content border-0 rounded-4 shadow" method="POST" action="kelola_seluruhrm.php">
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id" id="h_id">
            <input type="hidden" name="kembali" value="<?= h(qs()); ?>">
            <div class="modal-header border-bottom-0 pb-0"><h5 class="modal-title fw-bold">Hapus Rekam Medis?</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body fs-7 text-muted"><span id="h_teks"></span><br><br>Data yang dihapus tidak dapat dikembalikan. Tindakan ini akan tercatat pada audit trail.</div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger fw-semibold"><i class="fa-regular fa-trash-can me-1"></i> Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Riwayat -->
<div class="modal fade" id="mdlRiwayat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-lifora-primary text-white border-bottom-0 rounded-top-4">
                <div><h5 class="modal-title fw-bold">Riwayat Audit Trail</h5><p class="fs-8 mb-0 opacity-75" id="r_sub"></p></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="r_isi"></div>
        </div>
    </div>
</div>

<!-- Include Footer & Scripts -->
<?php include '../layout/components/admin/footer.php'; ?>

<script>
    /* ---------- Utilitas ---------- */
    const $ = id => document.getElementById(id);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const dataBaris = btn => JSON.parse(btn.closest('[data-rm]').dataset.rm);

    // Tombol Kustom Filter
    $('btnKustom').addEventListener('click', () => {
        const row = $('kustomRow');
        row.style.display = (row.style.display === 'none') ? 'flex' : 'none';
    });

    /* ---------- Form Tambah/Ubah ---------- */
    function nowLocal() {
        const d = new Date(); d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        return d.toISOString().slice(0, 16);
    }
    function setDokter(nama) {
        const sel = $('f_dokter');
        if (nama && ![...sel.options].some(o => o.value === nama)) sel.add(new Option(nama, nama));
        sel.value = nama || '';
    }
    function bukaForm(d) {
        $('formRm').reset();
        $('f_pilih').value = '';
        if (d) {
            $('formJudul').textContent = 'Ubah Rekam Medis ' + d.no_registrasi;
            $('formTombol').textContent = 'Simpan Perubahan';
            $('f_aksi').value = 'ubah'; $('f_id').value = d.id;
            $('f_waktu').value = d.waktu_kunjungan.replace(' ', 'T').slice(0, 16);
            $('f_no_rm').value = d.no_rm; $('f_nama').value = d.nama_pasien; $('f_nik').value = (d.nik === '-' ? '' : d.nik) || '';
            $('f_jk').value = d.jenis_kelamin; $('f_usia').value = d.usia;
            if (![...$('f_jaminan').options].some(o => o.value === d.jaminan)) $('f_jaminan').add(new Option(d.jaminan, d.jaminan));
            $('f_jaminan').value = d.jaminan;
            if (![...$('f_poli').options].some(o => o.value === d.poli)) $('f_poli').add(new Option(d.poli, d.poli));
            $('f_poli').value = d.poli;
            setDokter(d.dokter); $('f_sip').value = d.sip_dokter || '';
            $('f_kode').value = d.kode_icd; $('f_diag').value = d.nama_diagnosis; $('f_ket').value = d.keterangan_klinis || '';
            $('f_status').value = d.status_berkas; $('f_ketstatus').value = d.ket_status || '';
            $('f_sat').checked = d.satusehat == 1;
        } else {
            $('formJudul').textContent = 'Catat Rekam Medis Baru';
            $('formTombol').textContent = 'Simpan Rekam Medis';
            $('f_aksi').value = 'simpan'; $('f_id').value = '';
            $('f_waktu').value = nowLocal();
        }
        // Bootstrap Modal
        const mDetail = bootstrap.Modal.getInstance($('mdlDetail'));
        if(mDetail) mDetail.hide();
        new bootstrap.Modal($('mdlForm')).show();
    }
    $('f_pilih').addEventListener('change', function () {
        const o = this.options[this.selectedIndex];
        if (!this.value) return;
        $('f_no_rm').value = this.value; $('f_nama').value = o.dataset.nama; $('f_nik').value = o.dataset.nik || '';
        $('f_jk').value = o.dataset.jk; $('f_usia').value = o.dataset.usia;
        if ([...$('f_jaminan').options].some(x => x.value === o.dataset.jaminan)) $('f_jaminan').value = o.dataset.jaminan;
    });
    $('f_dokter').addEventListener('change', function () {
        const o = this.options[this.selectedIndex];
        if (o && o.dataset.sip) $('f_sip').value = o.dataset.sip;
        if (o && o.dataset.poli && [...$('f_poli').options].some(x => x.value === o.dataset.poli)) $('f_poli').value = o.dataset.poli;
    });
    if (new URLSearchParams(location.search).get('tambah') === '1') bukaForm();

    /* ---------- Detail ---------- */
    function bukaDetail(btn) {
        const d = dataBaris(btn);
        $('d_judul').textContent = d.nama_pasien;
        $('d_sub').textContent = d.no_registrasi + ' • ' + d.no_rm;
        const item = (l, v, full) => '<div class="' + (full ? 'col-12' : 'col-md-6') + '"><div class="fs-9 fw-bold text-muted text-uppercase mb-1">' + l + '</div><div class="fs-7 fw-semibold text-dark">' + esc(v || '-') + '</div></div>';
        $('d_isi').innerHTML =
            item('No. Registrasi', d.no_registrasi) + item('Waktu Kunjungan', d.waktu_kunjungan.slice(0, 16)) +
            item('No. Rekam Medis', d.no_rm) + item('NIK', d.nik) +
            item('Jenis Kelamin / Usia', (d.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan') + ' / ' + d.usia + ' Th') + item('Jaminan', d.jaminan) +
            item('Poli Tujuan', d.poli) + item('DPJP', d.dokter + (d.sip_dokter ? ' (SIP. ' + d.sip_dokter + ')' : '')) +
            item('Diagnosis (ICD-10)', d.kode_icd + ' — ' + d.nama_diagnosis, true) +
            item('Keterangan Klinis', d.keterangan_klinis, true) +
            item('Status Berkas', d.status_berkas) + item('Keterangan Status', d.ket_status) +
            item('SatuSehat', d.satusehat == 1 ? 'Terkirim' : 'Belum terkirim');
        
        $('d_ubah').onclick = () => bukaForm(d);
        $('d_cetak').onclick = () => cetakData(d);
        $('d_hapus').onclick = () => { 
            $('h_id').value = d.id; 
            $('h_teks').textContent = d.no_registrasi + ' — ' + d.nama_pasien; 
            bootstrap.Modal.getInstance($('mdlDetail')).hide();
            new bootstrap.Modal($('mdlHapus')).show(); 
        };
        new bootstrap.Modal($('mdlDetail')).show();
    }

    /* ---------- Riwayat Audit ---------- */
    function bukaRiwayat(btn) {
        const d = dataBaris(btn);
        $('r_sub').textContent = d.no_registrasi + ' — ' + d.nama_pasien;
        $('r_isi').innerHTML = '<p class="text-muted fs-7">Memuat riwayat...</p>';
        new bootstrap.Modal($('mdlRiwayat')).show();
        fetch('kelola_seluruhrm.php?log=' + encodeURIComponent(d.id))
            .then(r => r.json())
            .then(list => {
                $('r_isi').innerHTML = list.length ? list.map(l =>
                    '<div class="d-flex gap-3 border-bottom py-2"><span class="bg-lifora-primary-dark rounded-circle mt-1 flex-shrink-0" style="width:10px;height:10px;"></span><div><strong class="fs-7 text-dark">' + esc(l.aksi) + '</strong>' +
                    '<p class="fs-8 text-muted mb-1">' + esc(l.keterangan) + '</p><small class="fs-9 text-muted">' + esc(l.waktu) + ' • ' + esc(l.oleh) + '</small></div></div>'
                ).join('') : '<p class="text-muted fs-7">Belum ada riwayat untuk berkas ini.</p>';
            })
            .catch(() => { $('r_isi').innerHTML = '<p class="text-danger fs-7">Gagal memuat riwayat.</p>'; });
    }

    /* ---------- Cetak Resume ---------- */
    function cetakRm(btn) { cetakData(dataBaris(btn)); }
    function cetakData(d) {
        const w = window.open('', '_blank', 'width=800,height=900');
        if (!w) { alert('Pop-up diblokir browser. Izinkan pop-up untuk mencetak.'); return; }
        const row = (l, v) => '<tr><td style="width:190px;color:#475569;padding:6px 0">' + l + '</td><td style="padding:6px 0"><b>' + esc(v || '-') + '</b></td></tr>';
        w.document.write('<html><head><title>Resume Medis ' + esc(d.no_registrasi) + '</title></head>' +
            '<body style="font-family:Arial,sans-serif;padding:32px;color:#0f172a;font-size:14px">' +
            '<h2 style="margin:0">Puskesmas Melati Sejahtera</h2><p style="margin:2px 0 16px;color:#475569">Resume Medis Rawat Jalan</p><hr>' +
            '<table style="width:100%;border-collapse:collapse;margin-top:12px">' +
            row('No. Registrasi', d.no_registrasi) + row('Waktu Kunjungan', d.waktu_kunjungan.slice(0, 16)) +
            row('No. Rekam Medis', d.no_rm) + row('Nama Pasien', d.nama_pasien) + row('NIK', d.nik) +
            row('Jenis Kelamin / Usia', (d.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan') + ' / ' + d.usia + ' Th') +
            row('Jaminan', d.jaminan) + row('Poli', d.poli) + row('DPJP', d.dokter) +
            row('Diagnosis (ICD-10)', d.kode_icd + ' — ' + d.nama_diagnosis) + row('Keterangan Klinis', d.keterangan_klinis) +
            row('Status Berkas', d.status_berkas + ' (' + (d.ket_status || '-') + ')') +
            '</table><p style="margin-top:48px;text-align:right">Dokter Penanggung Jawab<br><br><br><b>' + esc(d.dokter) + '</b></p>' +
            '<script>window.onload=function(){window.print();}<\/script></body></html>');
        w.document.close();
    }
</script>

</body>
</html>