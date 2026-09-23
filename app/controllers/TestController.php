<?php
require_once __DIR__ . '/Controller.php';

class TestController extends Controller {
    
    public function index() {
        $this->checkAuth();
        
        $content = '
        <div class="alert alert-success">
            <h4>Page is Working!</h4>
            <p>This page confirms that the sidebar links are working correctly.</p>
        </div>
        <div class="card">
            <div class="card-header">
                <h5>Test Page</h5>
            </div>
            <div class="card-body">
                <p>If you can see this page, the sidebar navigation is functioning properly.</p>
                <p>Current URL: ' . $_SERVER['REQUEST_URI'] . '</p>
            </div>
        </div>';
        
        $title = 'Test Page';
        $page_title = 'Test';
        
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    private function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /unidia/public/login');
            exit;
        }
    }
}
?>