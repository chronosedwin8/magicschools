<?php
require_once __DIR__ . '/../includes/bootstrap.php';

logout_user();
session_start();
flash('success', 'Cerraste sesión correctamente.');
redirect('portal/login.php');
