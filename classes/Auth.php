<?php
class Auth {
    private $user;
    private $session;
    
    public function __construct($user, $session) {
        $this->user = $user;
        $this->session = $session;
    }
    
    public function login($email, $password) {
        $authResult = $this->user->authenticate($email, $password);
        
        if ($authResult['success']) {
            $this->session->start();
            $this->session->set('user_id', $authResult['user']['id']);
            $this->session->set('user_name', $authResult['user']['first_name'] . ' ' . $authResult['user']['last_name']);
            $this->session->set('role', $authResult['user']['role']);
            $this->session->set('email', $authResult['user']['email']);
            
            return ['success' => true, 'redirect' => $this->getRedirectUrl($authResult['user']['role'])];
        }
        
        return $authResult;
    }
    
    public function logout() {
        $this->session->destroy();
        return ['success' => true, 'redirect' => 'login.php'];
    }
    
    public function isLoggedIn() {
        return $this->session->has('user_id') && !empty($this->session->get('user_id'));
    }
    
    public function protectPage() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
    
    public function requireAdmin() {
        $this->protectPage();
        if ($this->session->get('role') !== 'admin') {
            header('Location: user_dashboard.php');
            exit();
        }
    }
    
    private function getRedirectUrl($role) {
        return $role === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php';
    }
    
    public function getCurrentUserId() {
        return $this->session->get('user_id');
    }
    
    public function getCurrentUserRole() {
        return $this->session->get('role');
    }
    
    public function getCurrentUserName() {
        return $this->session->get('user_name');
    }
    
    public function getCurrentUserEmail() {
        return $this->session->get('email');
    }
}
