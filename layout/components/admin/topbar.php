<?php
// layout/components/admin/topbar.php
// Pastikan session sudah dimulai di file utama sebelum menginclude file ini
?>
<header class="bg-white border-bottom p-3 d-flex justify-content-between align-items-center sticky-top">
    <div class="d-flex align-items-center gap-3">
        <div class="badge bg-lifora-blue-soft text-dark fw-normal rounded-pill px-3 py-2 d-flex align-items-center gap-2">
            <i class="fa-regular fa-calendar text-lifora-blue"></i>
            <span id="realtimeClock">Memuat waktu...</span>
        </div>
        <div class="badge bg-lifora-primary-soft text-lifora-primary-dark fw-semibold rounded-pill px-3 py-2 d-flex align-items-center gap-2">
            <span class="bg-lifora-primary rounded-circle d-inline-block" style="width: 8px; height: 8px;"></span>
            Poli Umum - Buka
        </div>
    </div>

    <div class="d-flex align-items-center border-start ps-3 gap-3">
        <div class="text-end">
            <div class="fw-bold text-dark" style="font-size: 13px;">dr. Siti Rahmawati</div>
            <div class="text-muted fs-8">
                <span class="badge bg-lifora-blue-soft text-lifora-blue me-1">Poli Umum</span> SIP. 19850412
            </div>
        </div>
        <!-- Panggil fungsi foto yang ada di file utama, atau buat default -->
        <img src="https://ui-avatars.com/api/?name=Siti+Rahmawati&background=00806f&color=fff" alt="Profile" class="rounded-circle border" style="width: 40px; height: 40px; object-fit: cover;">
    </div>
</header>