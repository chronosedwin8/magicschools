<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!is_post()) {
    redirect('#contacto');
}
csrf_check();

// Honeypot anti-spam
if (!empty($_POST['website'])) {
    redirect('#contacto');
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('error', 'Escribe tu nombre y un correo válido.');
    redirect('#contacto');
}

db_exec('INSERT INTO contact_requests (name, email, institution, phone, message) VALUES (?, ?, ?, ?, ?)', [
    mb_substr($name, 0, 120),
    mb_substr($email, 0, 190),
    mb_substr(trim((string) ($_POST['institution'] ?? '')), 0, 190),
    mb_substr(trim((string) ($_POST['phone'] ?? '')), 0, 30),
    mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 2000),
]);

flash('success', '¡Gracias! Un asesor te contactará muy pronto.');
redirect('#contacto');
