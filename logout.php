<?php
require_once 'autoload.php';

$database = new Database();
$session = new Session();
$user = new User($database->getConnection());
$auth = new Auth($user, $session);

$logoutResult = $auth->logout();
header('Location: ' . $logoutResult['redirect']);
exit();
?>
