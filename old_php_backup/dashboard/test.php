<div class="info-box">
    <div class="info-box-content">
        <span class="info-box-text">Total Rejected SSINs - This Month</span>
        <span class="info-box-number" id="totalRejected">Loading...</span>
    </div>
</div>

<script>
    function fetchTotalRejected() {
        fetch("fetch_total_rejected.php")
            .then(response => response.json())
            .then(data => {
                document.getElementById("totalRejected").innerText = data.totalRejected;
            })
            .catch(error => {
                console.error("Error fetching total rejected SSIN count:", error);
            });
    }

    // Fetch on page load
    fetchTotalRejected();
</script>
