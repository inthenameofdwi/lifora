<?php
// layout/components/dokter/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="d-flex flex-column flex-shrink-0 p-3 bg-white border-end" style="width: 280px; min-height: 100vh; position: sticky; top: 0; overflow-y: auto;">
    <!-- Brand -->
    <a href="dashboard_dokter.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-decoration-none px-2">
        <div class="bg-lifora-primary text-white rounded d-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px;">
            <i class="fa-solid fa-leaf"></i>
        </div>
        <div>
            <div class="fw-bold fs-6 lh-1 text-dark">Lifora</div>
            <div class="text-lifora-primary fw-semibold fs-9 text-uppercase" style="letter-spacing: 0.08em;">Puskesmas</div>
        </div>
    </a>
    
    <hr>

    <!-- Faskes Info -->
    <div class="bg-lifora-blue-soft rounded p-2 mb-3 d-flex align-items-center gap-2">
        <i class="fa-solid fa-hospital text-lifora-primary"></i>
        <div>
            <small class="text-muted fs-8">Fasilitas Kesehatan</small>
            <div class="fw-semibold text-dark" style="font-size: 12px;">Puskesmas Melati Sejahtera</div>
        </div>
    </div>

    <!-- Nav Menu -->
    <ul class="nav nav-pills flex-column mb-auto gap-1">
        
        <!-- Section: Dashboard -->
        <li class="nav-item">
            <a href="dashboard_dokter.php" class="nav-link d-flex align-items-center gap-2 <?= $currentPage === 'dashboard_dokter.php' ? 'active-custom' : 'text-secondary'; ?>">
                <i class="fa-solid fa-gauge-high"></i> Dashboard Pelayanan
            </a>
        </li>

        <!-- Section: Pelayanan Pasien -->
        <li class="nav-item mt-3">
            <span class="text-muted fw-bold text-uppercase d-block px-2 mb-1" style="font-size: 10px; letter-spacing: 0.08em;">Pelayanan Pasien</span>
        </li>
        <li class="nav-item">
            <a href="kelola_pemeriksaan.php" class="nav-link d-flex align-items-center gap-2 <?= in_array($currentPage, ['kelola_pemeriksaan.php', 'input_rekammedis.php']) ? 'active-custom' : 'text-secondary'; ?>">
                <i class="fa-solid fa-stethoscope"></i> Antrean Pemeriksaan
            </a>
        </li>

        <!-- Section: Rekam Medis -->
        <li class="nav-item mt-3">
            <span class="text-muted fw-bold text-uppercase d-block px-2 mb-1" style="font-size: 10px; letter-spacing: 0.08em;">Rekam Medis</span>
        </li>
        <li class="nav-item">
            <a href="catat_rm.php" class="nav-link d-flex align-items-center gap-2 <?= $currentPage === 'catat_rm.php' ? 'active-custom' : 'text-secondary'; ?>">
                <i class="fa-regular fa-file-lines"></i> Catat RM Baru
            </a>
        </li>
        <li class="nav-item">
            <a href="arsip_rm.php" class="nav-link d-flex align-items-center gap-2 <?= $currentPage === 'arsip_rm.php' ? 'active-custom' : 'text-secondary'; ?>">
                <i class="fa-regular fa-folder-open"></i> Arsip Rekam Medis
            </a>
        </li>

        <!-- Section: Jadwal -->
        <li class="nav-item mt-3">
            <span class="text-muted fw-bold text-uppercase d-block px-2 mb-1" style="font-size: 10px; letter-spacing: 0.08em;">Jadwal</span>
        </li>
        <li class="nav-item">
            <a href="jadwal_praktik.php" class="nav-link d-flex align-items-center gap-2 <?= $currentPage === 'jadwal_praktik.php' ? 'active-custom' : 'text-secondary'; ?>">
                <i class="fa-regular fa-calendar-days"></i> Jadwal Praktik Saya
            </a>
        </li>
    </ul>

    <!-- Footer Sidebar -->
    <div class="bg-lifora-blue-soft rounded p-3 mt-4">
        <div class="d-flex align-items-center gap-2 mb-2 fw-semibold" style="font-size: 11px;">
            <span class="bg-lifora-primary rounded-circle d-inline-block" style="width: 8px; height: 8px;"></span>
            Server Sinkronisasi Aktif
        </div>
        <a href="../logout.php" class="btn btn-light w-100 text-danger fw-bold d-flex justify-content-center align-items-center gap-2">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar Sesi
        </a>
    </div>
</aside>