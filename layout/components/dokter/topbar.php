<?php
// layout/components/dokter/topbar.php
// Pastikan koneksi database ($pdo) sudah tersedia dari file pemanggil

// Cek jika $profil belum didefinisikan, ambil otomatis dari database
if (!isset($profil) || empty($profil)) {
    $username = $_SESSION['username'] ?? '';
    $profil = ['nama' => $username ?: 'Dokter', 'badge' => '', 'info' => '', 'poli' => '', 'foto_html' => ''];
    
    try {
        // Menggunakan $pdo yang diasumsikan sudah di-include di file utama
        $stmt = $pdo->prepare("SELECT * FROM dokter WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $d = $stmt->fetch();
        
        if ($d) {
            $profil['nama']  = $d['nama_dokter'];
            $profil['badge'] = $d['poli'];
            $profil['poli']  = $d['poli'];
            $profil['info']  = 'SIP. ' . strtok(trim($d['nip_sip']), ' ');
            
            $av = trim($d['foto_avatar'] ?? '');
            $fallback = 'https://ui-avatars.com/api/?name=' . urlencode($d['nama_dokter']) . '&background=0d6b5f&color=fff&bold=true';
            $profil['foto_html'] = '<img src="../assets/img/dokter/' . htmlspecialchars($av) . '" class="rounded-circle border" style="width:40px;height:40px;object-fit:cover;" onerror="this.onerror=null;this.src=\'' . $fallback . '\'">';
        }
    } catch (Throwable $e) {
        // Abaikan error jika query gagal
    }
    
    // Fallback jika foto tidak ditemukan
    if ($profil['foto_html'] === '') {
        $fallback = 'https://ui-avatars.com/api/?name=' . urlencode($profil['nama']) . '&background=0d6b5f&color=fff&bold=true';
        $profil['foto_html'] = '<img src="' . $fallback . '" class="rounded-circle border" style="width:40px;height:40px;object-fit:cover;">';
    }
}
?>
<header class="bg-white border-bottom p-3 d-flex justify-content-between align-items-center sticky-top">
    <div class="d-flex align-items-center gap-3">
        <div class="badge bg-lifora-blue-soft text-dark fw-normal rounded-pill px-3 py-2 d-flex align-items-center gap-2">
            <i class="fa-regular fa-calendar text-lifora-blue"></i>
            <span id="realtimeClock">Memuat waktu...</span>
        </div>
        <div class="badge bg-lifora-primary-soft text-lifora-primary-dark fw-semibold rounded-pill px-3 py-2 d-flex align-items-center gap-2">
            <span class="bg-lifora-primary rounded-circle d-inline-block" style="width: 8px; height: 8px;"></span>
            <?= htmlspecialchars($profil['poli'] ?? 'Poli Umum'); ?> - Buka
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-link text-muted position-relative p-0">
            <i class="fa-regular fa-bell fs-5"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 9px;">3</span>
        </button>
        <div class="d-flex align-items-center border-start ps-3 gap-3">
            <div class="text-end">
                <div class="fw-bold text-dark" style="font-size: 13px;"><?= htmlspecialchars($profil['nama']); ?></div>
                <div class="text-muted fs-8"><?= htmlspecialchars($profil['poli'] ?? ''); ?> <?= htmlspecialchars($profil['info']); ?></div>
            </div>
            <?= $profil['foto_html']; ?>
        </div>
    </div>
</header>