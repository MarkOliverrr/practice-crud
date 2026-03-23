<?php
class Validator {
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validatePassword($password) {
        return strlen($password) >= 8;
    }
    
    public static function validateRequired($field, $value, $fieldName) {
        if (empty($value)) {
            return "$fieldName is required.";
        }
        return null;
    }
    
    public static function validateEmailFormat($email) {
        if (!self::validateEmail($email)) {
            return "Email format is invalid.";
        }
        return null;
    }
    
    public static function validatePasswordStrength($password) {
        if (!self::validatePassword($password)) {
            return "Password must be at least 8 characters.";
        }
        return null;
    }
    
    public static function validatePasswordMatch($password, $confirmPassword) {
        if ($password !== $confirmPassword) {
            return "Password and Confirm Password must match.";
        }
        return null;
    }
    
    public static function validateGender($gender) {
        if (empty($gender)) {
            return "Gender is required.";
        }
        return null;
    }
    
    public static function validateRole($role) {
        if (empty($role)) {
            return "Role is required.";
        }
        return null;
    }
}
