<?php
session_start();
if (!empty($_SESSION['user'])) {
    header('Location: home.php');
    exit;
}
header('Location: login.php');
exit;
