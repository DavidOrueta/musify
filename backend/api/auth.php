<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? 'me';

if ($action === 'me') {
    requireMethod('GET');
    if (!isset($_SESSION['user_id'])) {
        respond(['authenticated' => false, 'user' => null]);
    }

    $statement = $pdo->prepare('SELECT id, name AS username, email FROM users WHERE id = ?');
    $statement->execute([(int) $_SESSION['user_id']]);
    $user = $statement->fetch();
    respond(['authenticated' => (bool) $user, 'user' => $user ?: null]);
}

if ($action === 'login') {
    requireMethod('POST');
    $input = jsonInput();
    $identity = trim((string) ($input['email'] ?? $input['username'] ?? ''));
    $password = (string) ($input['password'] ?? '');

    if ($identity === '' || $password === '') {
        respond(['error' => 'Usuario y contraseña son obligatorios.'], 422);
    }

    $statement = $pdo->prepare('SELECT id, name AS username, email, password_hash FROM users WHERE email = ? OR name = ? LIMIT 1');
    $statement->execute([$identity, $identity]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        respond(['error' => 'Las credenciales no son correctas.'], 401);
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    unset($user['password_hash']);
    respond(['authenticated' => true, 'user' => $user]);
}

if ($action === 'register') {
    requireMethod('POST');
    $input = jsonInput();
    $username = trim((string) ($input['username'] ?? ''));
    $email = trim((string) ($input['email'] ?? ''));
    $password = (string) ($input['password'] ?? '');

    if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        respond(['error' => 'Introduce un usuario, un email válido y una contraseña de al menos 6 caracteres.'], 422);
    }

    $statement = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    try {
        $statement->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
    } catch (PDOException $exception) {
        if ((int) $exception->errorInfo[1] === 1062) {
            respond(['error' => 'El usuario o email ya existe.'], 409);
        }
        throw $exception;
    }

    $_SESSION['user_id'] = (int) $pdo->lastInsertId();
    respond(['authenticated' => true, 'user' => ['id' => $_SESSION['user_id'], 'username' => $username, 'email' => $email]], 201);
}

if ($action === 'logout') {
    requireMethod('POST');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    respond(['authenticated' => false]);
}

respond(['error' => 'Acción de autenticación no válida.'], 404);
