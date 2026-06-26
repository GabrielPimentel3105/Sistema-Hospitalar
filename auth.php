<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['usuario_logado'])) {
    header("Location: login.php");
    exit;
}