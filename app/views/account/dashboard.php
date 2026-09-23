<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-calculator me-2"></i>Account Dashboard
        </h1>
        <div>
            <a href="<?php echo BASE_URL; ?>/account/reports" class="btn btn-primary btn-sm me-2">
                <i class="fas fa-chart-bar me-1"></i> Reports
            </a>
            <a href="<?php echo BASE_URL; ?>/account/bills" class="btn btn-success btn-sm">
                <i class="fas fa-file-invoice me-1"></i> Bills
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Today's Revenue</div>
                            <div class="h5 mb-0 font-weight-bold">$<?php echo number_format($todayRevenue, 2); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-dollar-sign fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Payments</div>
                            <div class="h5 mb-0 font-weight-bold">$<?php echo number_format($pendingPayments, 2); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-clock fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Monthly Revenue</div>
                            <div class="h5 mb-0 font-weight-bold">$<?php echo number_format($monthlyRevenue, 2); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-calendar fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Today's Expenses</div>
                            <div class="h5 mb-0 font-weight-bold">$<?php echo number_format($todayExpenses, 2); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-arrow-down fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Revenue Chart</h6>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Bill Generation</h6>
                </div>
                <div class="card-body">
                    <button class="btn btn-primary w-100 mb-3 py-3" data-bs-toggle="modal" data-bs-target="#billModal">
                        <i class="fas fa-file-invoice me-2"></i>Generate New Bill
                    </button>
                    <a href="<?php echo BASE_URL; ?>/account/insurance" class="btn btn-info w-100 mb-3 py-3">
                        <i class="fas fa-shield-alt me-2"></i>Insurance Claims
                    </a>
                    <a href="<?php echo BASE_URL; ?>/account/reports" class="btn btn-warning w-100 py-3">
                        <i class="fas fa-chart-line me-2"></i>Financial Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var ctx = document.getElementById('revenueChart').getContext('2d');
var revenueChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [{
            label: 'Revenue',
            data: [1200, 1900, 1500, 2100, 1800, 900, 700],
            backgroundColor: '#4e73df'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>