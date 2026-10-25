<?php
// layout/components/admin/head.php
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin - Lifora Puskesmas</title>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* Custom Colors & Variables untuk menyesuaikan dengan desain asli */
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
    .bg-lifora-primary-soft { background-color: var(--lifora-primary-soft) !important; }
    .text-lifora-blue { color: var(--lifora-blue-text) !important; }
    .bg-lifora-blue-soft { background-color: var(--lifora-blue-soft) !important; }
    
    /* Hero Background */
    .hero-card {
        background: linear-gradient(100deg, #fff 55%, var(--lifora-mint-bg) 100%);
    }
    
    /* Custom Donut Chart */
    .donut-chart {
        width: 152px; height: 152px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto;
    }
    .donut-inner {
        width: 114px; height: 114px; border-radius: 50%; background: #fff;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
    }

    /* Timeline custom */
    .timeline { position: relative; border-left: 1px solid #e2e8f0; padding-left: 20px; margin-left: 10px; }
    .timeline-item { position: relative; margin-bottom: 20px; }
    .timeline-item::before {
        content: ''; position: absolute; left: -26px; top: 4px; width: 11px; height: 11px; border-radius: 50%; border: 2px solid #fff; box-sizing: content-box;
    }
    
    /* Utilities */
    .fs-7 { font-size: 11px; }
    .fs-8 { font-size: 10px; }
    .fs-9 { font-size: 9px; }
    .nav-link.active-custom { background-color: var(--lifora-primary) !important; color: #fff !important; border-radius: 8px; font-weight: 600; }
</style>