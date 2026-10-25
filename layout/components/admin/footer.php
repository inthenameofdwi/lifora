<?php
// layout/components/admin/footer.php
?>
<script>
    function updateRealtimeClock() {
        const now = new Date();
        const hariList = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const bulanList = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        const tanggal = String(now.getDate()).padStart(2, '0');
        const jam = String(now.getHours()).padStart(2, '0');
        const menit = String(now.getMinutes()).padStart(2, '0');

        document.getElementById('realtimeClock').innerHTML =
            hariList[now.getDay()] + ', ' + tanggal + ' ' + bulanList[now.getMonth()] + ' ' + now.getFullYear() +
            ' &nbsp;•&nbsp; <b>' + jam + ':' + menit + ' WIB</b>';
    }
    updateRealtimeClock();
    setInterval(updateRealtimeClock, 1000);
</script>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>