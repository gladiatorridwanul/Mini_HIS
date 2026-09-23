<?php
class PermissionHelper {
    private $db;
    private $userPermissions = [];
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->loadUserPermissions();
    }
    
    private function loadUserPermissions() {
        if (!isset($_SESSION['user_id'])) {
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $roleId = $_SESSION['role_id'] ?? 0;
        
        // Get role permissions
        $query = "SELECT permission_slug FROM role_permissions WHERE role_id = $roleId";
        $result = $this->db->query($query);
        
        $this->userPermissions = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $this->userPermissions[] = $row['permission_slug'];
            }
        }
        
        // Check if user has custom permissions
        $userQuery = "SELECT permissions FROM users WHERE id = $userId";
        $userResult = $this->db->query($userQuery);
        if ($userResult && $userResult->num_rows > 0) {
            $user = $userResult->fetch_assoc();
            if (!empty($user['permissions'])) {
                $customPermissions = json_decode($user['permissions'], true);
                if (is_array($customPermissions)) {
                    $this->userPermissions = array_merge($this->userPermissions, $customPermissions);
                }
            }
        }
        
        $this->userPermissions = array_unique($this->userPermissions);
    }
    
    public function hasPermission($permissionSlug) {
        // Super admin has all permissions
        if ($_SESSION['role_slug'] === 'super_admin') {
            return true;
        }
        return in_array($permissionSlug, $this->userPermissions);
    }
    
    public function getUserMenu() {
        $menuQuery = "SELECT * FROM menu_items WHERE status = 'active' ORDER BY parent_id, order_index";
        $result = $this->db->query($menuQuery);
        
        $allMenus = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $allMenus[] = $row;
            }
        }
        
        $filteredMenu = [];
        foreach ($allMenus as $menu) {
            if (empty($menu['permission_slug']) || $this->hasPermission($menu['permission_slug'])) {
                $filteredMenu[] = $menu;
            }
        }
        
        // Build hierarchical menu
        $menuTree = [];
        foreach ($filteredMenu as $menu) {
            if ($menu['parent_id'] == 0) {
                $menu['submenus'] = [];
                $menuTree[$menu['id']] = $menu;
            }
        }
        
        foreach ($filteredMenu as $menu) {
            if ($menu['parent_id'] != 0 && isset($menuTree[$menu['parent_id']])) {
                $menuTree[$menu['parent_id']]['submenus'][] = $menu;
            }
        }
        
        return array_values($menuTree);
    }
    
    public function getAllPermissions() {
        $query = "SELECT * FROM permissions ORDER BY module, name";
        $result = $this->db->query($query);
        $permissions = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $permissions[] = $row;
            }
        }
        return $permissions;
    }
    
    public function getPermissionsByModule() {
        $query = "SELECT * FROM permissions ORDER BY module, name";
        $result = $this->db->query($query);
        $grouped = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if (!isset($grouped[$row['module']])) {
                    $grouped[$row['module']] = [];
                }
                $grouped[$row['module']][] = $row;
            }
        }
        return $grouped;
    }
    
    public function getRolePermissions($roleId) {
        $query = "SELECT permission_slug FROM role_permissions WHERE role_id = $roleId";
        $result = $this->db->query($query);
        $permissions = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $permissions[] = $row['permission_slug'];
            }
        }
        return $permissions;
    }
    
    public function assignPermissionsToRole($roleId, $permissions) {
        // Delete existing
        $deleteQuery = "DELETE FROM role_permissions WHERE role_id = $roleId";
        $this->db->query($deleteQuery);
        
        // Insert new
        if (!empty($permissions)) {
            $values = [];
            foreach ($permissions as $permission) {
                $permission = $this->db->real_escape_string($permission);
                $values[] = "($roleId, '$permission')";
            }
            $insertQuery = "INSERT INTO role_permissions (role_id, permission_slug) VALUES " . implode(',', $values);
            return $this->db->query($insertQuery);
        }
        return true;
    }
    
    public function updateUserPermissions($userId, $permissions) {
        $permissionsJson = json_encode($permissions);
        $permissionsJson = $this->db->real_escape_string($permissionsJson);
        $query = "UPDATE users SET permissions = '$permissionsJson' WHERE id = $userId";
        return $this->db->query($query);
    }
    
    public function getUserPermissions($userId) {
        $query = "SELECT permissions FROM users WHERE id = $userId";
        $result = $this->db->query($query);
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (!empty($user['permissions'])) {
                return json_decode($user['permissions'], true);
            }
        }
        return [];
    }
    
    public function canAccessUrl($url) {
        // Find menu item by URL
        $url = $this->db->real_escape_string($url);
        $query = "SELECT permission_slug FROM menu_items WHERE url = '$url' AND status = 'active'";
        $result = $this->db->query($query);
        
        if ($result && $result->num_rows > 0) {
            $menu = $result->fetch_assoc();
            if (!empty($menu['permission_slug'])) {
                return $this->hasPermission($menu['permission_slug']);
            }
        }
        return true; // No permission required for this URL
    }
}
?>