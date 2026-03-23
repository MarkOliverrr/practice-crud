<?php
class Session {
    private $started = false;
    
    public function start() {
        if (!$this->started) {
            session_start();
            $this->started = true;
        }
    }
    
    public function set($key, $value) {
        $this->start();
        $_SESSION[$key] = $value;
    }
    
    public function get($key, $default = null) {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }
    
    public function has($key) {
        $this->start();
        return isset($_SESSION[$key]);
    }
    
    public function remove($key) {
        $this->start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    public function destroy() {
        // Start session if not already started to ensure we can destroy it
        $this->start();
        session_destroy();
        $this->started = false;
    }
    
    public function regenerateId() {
        $this->start();
        session_regenerate_id(true);
    }
    
    public function all() {
        $this->start();
        return $_SESSION;
    }
    
    public function clear() {
        $this->start();
        $_SESSION = [];
    }
}
