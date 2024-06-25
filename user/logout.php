<?php 

session_start();
require_once '../auth.php';
requireLogin();

header('Location: ../');
session_destroy();
?>