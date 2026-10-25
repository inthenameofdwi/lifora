<?php
// layout/components/dokter/footer.php
?>
<script>
    function updateRealtimeClock() {
        const now = new Date();
        const hariList = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const bulanList = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const p = n => String(n).padStart(2, '0');
        document.getElementById('realtimeClock').innerHTML =
            hariList[now.getDay()] + ', ' + p(now.getDate()) + ' ' + bulanList[now.getMonth()] + ' ' + now.getFullYear() +
            ' &nbsp;•&nbsp; <b>' + p(now.getHours()) + ':' + p(now.getMinutes()) + ' WIB</b>';
    }
    updateRealtimeClock();
    setInterval(updateRealtimeClock, 1000);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>