<?php
session_start();
if (empty($_SESSION['admin_id'])) {
    header('Location: /admin/');
    exit;
}
