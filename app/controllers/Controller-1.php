<?php
// core/Controller.php

// Only declare if it doesn't already exist
if (!class_exists('Controller')) {

    class Controller {
        protected $db;
        
        public function __construct() {
            global $conn;
            $this->db = $conn;
        }
        
        /**
         * RENDER A VIEW WITH FULL LAYOUT
         * This is the MAIN method for rendering pages with layout
         */
        protected function view($view, $data = [], $title = '') {
            extract($data);
            ob_start();
            $viewFile = BASE_PATH . '/app/views/' . $view . '.php';
            if (file_exists($viewFile)) {
                include $viewFile;
            } else {
                echo "View not found: " . $view;
            }
            $content = ob_get_clean();
            $pageTitle = $title ?: ucwords(str_replace('/', ' ', $view));
            $this->renderLayout($pageTitle, $content);
        }
        
        protected function renderView($viewPath, $data = []) {
            extract($data);
            ob_start();
            include BASE_PATH . '/app/views/' . $viewPath . '.php';
            return ob_get_clean();
        }
        
        protected function renderLayout($pageTitle, $content) {
            // Set active menu based on current URL
            $active_menu = $this->getActiveMenu();
            
            // Use the layout file from app/views/layouts/main.php
            $layoutFile = BASE_PATH . '/app/views/layouts/main.php';
            if (file_exists($layoutFile)) {
                include $layoutFile;
            } else {
                // Fallback
                echo '<!DOCTYPE html><html><head><title>' . $pageTitle . '</title>';
                echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">';
                echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">';
                echo '</head><body><div class="container mt-4">' . $content . '</div></body></html>';
            }
        }
        
        protected function redirect($url) {
            header("Location: " . BASE_URL . $url);
            exit;
        }
        
        protected function checkAuth() {
            if (!isset($_SESSION['user_id'])) {
                $this->redirect('/login');
                exit;
            }
        }
        
        protected function json($data) {
            header('Content-Type: application/json');
            echo json_encode($data);
            exit;
        }
        
        protected function setFlash($type, $message) {
            $_SESSION['flash'] = [
                'type' => $type,
                'message' => $message
            ];
        }
        
        protected function getFlash() {
            if (isset($_SESSION['flash'])) {
                $flash = $_SESSION['flash'];
                unset($_SESSION['flash']);
                return $flash;
            }
            return null;
        }
        
        private function getActiveMenu() {
            $request = $_SERVER['REQUEST_URI'];
            
            if(strpos($request, 'admin/dashboard') !== false) return 'dashboard';
            if(strpos($request, 'admin/users') !== false) return 'users';
            if(strpos($request, 'patient/') !== false) return 'patients';
            if(strpos($request, 'doctor/') !== false) return 'doctors';
            if(strpos($request, 'reception/') !== false) return 'appointments';
            if(strpos($request, 'bills') !== false || strpos($request, 'billing') !== false) return 'billing';
            if(strpos($request, 'pharmacy') !== false) return 'pharmacy';
            if(strpos($request, 'lab') !== false) return 'laboratory';
            if(strpos($request, 'inventory') !== false) return 'inventory';
            if(strpos($request, 'audit') !== false) return 'audit';
            if(strpos($request, 'settings') !== false) return 'settings';
            
            return '';
        }
    }
}