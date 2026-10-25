<?php
// layout/components/dokter/head.php
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Pelayanan Pasien - Lifora Puskesmas</title>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --lifora-primary: #00806f;
        --lifora-primary-dark: #005a4e;
        --lifora-primary-soft: #9df2e3;
        --lifora-mint-bg: #e6faf6;
        --lifora-blue-soft: #e4edfb;
        --lifora-blue-badge: #d6e4fa;
        --lifora-blue-text: #0b5c8a;
    }
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f3f6f9 !important;
        font-size: 13px;
        color: #1e293b;
    }
    .text-lifora-primary { color: var(--lifora-primary) !important; }
    .bg-lifora-primary { background-color: var(--lifora-primary) !important; }
    .bg-lifora-primary-dark { background-color: var(--lifora-primary-dark) !important; }
    .bg-lifora-primary-soft { background-color: var(--lifora-primary-soft) !important; }
    .bg-lifora-blue-soft { background-color: var(--lifora-blue-soft) !important; }
    .text-lifora-blue { color: var(--lifora-blue-text) !important; }
    .nav-link.active-custom { background-color: var(--lifora-primary-dark) !important; color: #fff !important; border-radius: 8px; font-weight: 600; }
    .fs-7 { font-size: 11px; }
    .fs-8 { font-size: 10px; }
    .fs-9 { font-size: 9px; }
    .card { border-radius: 16px; }
</style>