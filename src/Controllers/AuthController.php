<?php
namespace App\Controllers;

use App\Services\UserService;
use App\Utils\Logger;
use App\Http\Request;

class AuthController
{
    private UserService $users;

    public function __construct(UserService $users)
    {
        $this->users = $users;
    }

    public function showLogin(): string
    {
        ob_start();
        $csrf = $_SESSION['csrf_token'] ?? '';
        $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;
        $requireCaptcha = ($_SESSION['login_attempts'] ?? 0) >= 3;
        if ($requireCaptcha && !isset($_SESSION['captcha_question'])) {
            $a = random_int(1, 9); $b = random_int(1, 9);
            $_SESSION['captcha_question'] = "¿Cuánto es $a + $b?";
            $_SESSION['captcha_answer'] = (string)($a + $b);
        }
        $captchaQuestion = $_SESSION['captcha_question'] ?? null;
        include __DIR__ . '/../Views/auth/login.php';
        return ob_get_clean();
    }

    public function login(): string
    {
        $token = Request::postString('csrf_token', '');
        if ($token === null || $token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            return 'CSRF inválido';
        }
        $identityRaw = Request::postString('identity', '');
        $identity = trim((string)$identityRaw);
        $password = (string)(Request::postString('password', '') ?? '');
        // Rate limiting básico por sesión
        $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;
        $requireCaptcha = ($_SESSION['login_attempts'] ?? 0) >= 3;
        if ($requireCaptcha) {
            $captchaRaw = Request::postString('captcha', '');
            $input = trim((string)$captchaRaw);
            $answer = (string)($_SESSION['captcha_answer'] ?? '');
            if ($input === '' || $answer === '' || $input !== $answer) {
                $_SESSION['flash'] = 'Captcha inválido. Intenta de nuevo.';
                http_response_code(401);
                return $this->showLogin();
            }
        }

        $user = $this->users->authenticate($identity, $password);
        if (!$user) {
            http_response_code(401);
            $_SESSION['login_attempts'] = (int)($_SESSION['login_attempts'] ?? 0) + 1;
            $_SESSION['flash'] = 'Credenciales inválidas';
            Logger::info('login_failed', ['identity' => $identity]);
            return $this->showLogin();
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'] ?? '';
        $_SESSION['login_attempts'] = 0;
        unset($_SESSION['captcha_question'], $_SESSION['captcha_answer']);
        Logger::info('login_success', ['user_id' => $user['id'], 'role' => $user['role']]);
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        header('Location: ' . $base . '/app.php?r=/dashboard');
        return '';
    }

    public function logout(): string
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        Logger::info('logout');
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        header('Location: ' . $base . '/app.php?r=/login');
        return '';
    }
}
