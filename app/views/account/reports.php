<div class="container-fluid">
    <h4 class="mb-4"><i class="fas fa-chart-line me-2"></i>Financial Reports</h4>

    <div class="row mb-4">
        <div class="col-md-3">
            <select class="form-select" id="reportType" onchange="loadReport()">
                <option value="daily" <?php echo ($reportType ?? '') == 'daily' ? 'selected' : ''; ?>>Daily Report</option>
                <option value="monthly" <?php echo ($reportType ?? '') == 'monthly' ? 'selected' : ''; ?>>Monthly Report</option>
                <option value="yearly" <?php echo ($reportType ?? '') == 'yearly' ? 'selected' : ''; ?>>Yearly Report</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" class="form-control" id="reportDate" value="<?php echo $date ?? date('Y-m-d'); ?>" onchange="loadReport()">
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary" onclick="loadReport()">
                <i class="fas fa-search me-1"></i>Generate
            </button>
            <button class="btn btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print me-1"></i>Print
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Revenue</div>
                    <div class="h2">$<?php echo number_format($report['total_billed'] ?? 0, 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Collected</div>
                    <div class="h2">$<?php echo number_format($report['total_collected'] ?? 0, 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Pending</div>
                    <div class="h2">$<?php echo number_format($report['total_pending'] ?? 0, 2); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header"><h6 class="mb-0">Revenue Chart</h6></div>
        <div class="card-body">
            <canvas id="reportChart" style="height: 300px;"></canvas>
        </div>
    </div>
</div>

<script>
var ctx = document.getElementById('reportChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [{
            label: 'Revenue',
            data: [5000,6000,5500,7000,6500,8000,7500,9000,8500,10000,9500,11000],
            borderColor: '#4e73df'
        }]
    }
});

function loadReport() {
    var type = $('#reportType').val();
    var date = $('#reportDate').val();
    window.location.href = '<?php echo BASE_URL; ?>/account/reports?type=' + type + '&date=' + date;
}
</script>