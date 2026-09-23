<?php
class AccountController extends Controller {
    private $billModel;
    private $patientModel;
    
    public function __construct() {
        $this->requireAuth();
        $this->billModel = $this->model('Bill');
        $this->patientModel = $this->model('Patient');
    }
    
    public function dashboard() {
        $data = [
            'title' => 'Account Dashboard',
            'todayRevenue' => $this->billModel->getTodayRevenue(),
            'pendingPayments' => $this->billModel->getPendingPayments(),
            'monthlyRevenue' => $this->billModel->getMonthlyRevenue(),
            'todayExpenses' => $this->billModel->getTodayExpenses()
        ];
        $this->view('account/dashboard', $data);
    }
    
    public function bills() {
        $filters = [
            'date_from' => $this->input('date_from'),
            'date_to' => $this->input('date_to'),
            'status' => $this->input('status'),
            'type' => $this->input('type')
        ];
        
        $data = [
            'title' => 'Bill Management',
            'bills' => $this->billModel->getAllBills($filters)
        ];
        $this->view('account/bills', $data);
    }
    
    public function financialReports() {
        $reportType = $this->input('type', 'daily');
        $date = $this->input('date', date('Y-m-d'));
        
        switch($reportType) {
            case 'daily':
                $report = $this->billModel->getDailyReport($date);
                break;
            case 'monthly':
                $report = $this->billModel->getMonthlyReport($date);
                break;
            case 'yearly':
                $report = $this->billModel->getYearlyReport($date);
                break;
        }
        
        $data = [
            'title' => 'Financial Reports',
            'report' => $report,
            'reportType' => $reportType,
            'date' => $date
        ];
        $this->view('account/reports', $data);
    }
    
    public function insurance() {
        $data = [
            'title' => 'Insurance Management',
            'claims' => $this->billModel->getInsuranceClaims(),
            'pendingClaims' => $this->billModel->getPendingClaims()
        ];
        $this->view('account/insurance', $data);
    }
    
    public function claimInsurance() {
        if($this->isPost()) {
            $claimData = [
                'claim_number' => $this->generateClaimNumber(),
                'bill_id' => $this->input('bill_id'),
                'patient_id' => $this->input('patient_id'),
                'insurance_provider' => $this->input('insurance_provider'),
                'policy_number' => $this->input('policy_number'),
                'claim_amount' => $this->input('claim_amount'),
                'claim_date' => date('Y-m-d'),
                'status' => 'pending'
            ];
            
            $this->billModel->createClaim($claimData);
            
            $_SESSION['success'] = 'Insurance claim submitted successfully';
            $this->redirect('account/insurance');
        }
    }
    
    private function generateClaimNumber() {
        $prefix = 'CLM';
        $year = date('Y');
        $sql = "SELECT MAX(CAST(SUBSTRING(claim_number, 7) AS UNSIGNED)) as max_num 
                FROM insurance_claims WHERE claim_number LIKE '{$prefix}{$year}%'";
        $result = $this->billModel->query($sql);
        $nextNum = ($result[0]['max_num'] ?? 0) + 1;
        return $prefix . $year . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
    }
    
    private function requireAuth() {
        if(!isset($_SESSION['user_id'])) {
            $this->redirect('login');
        }
    }
}