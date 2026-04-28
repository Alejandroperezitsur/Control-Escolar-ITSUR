<?php

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Middleware\SecurityMiddleware;

class AuthController extends Controller {
    private UserRepository $userRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    public function login() {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            return redirect('/login')->with('error', 'Email y contraseña requeridos');
        }

        $user = $this->userRepository->findByEmail($email);

        // Timing attack protection: verificar password incluso si usuario no existe
        $validPassword = SecurityMiddleware::safePasswordVerify($password, $user?->password_hash);

        if (!$user || !$validPassword) {
            return redirect('/login')->with('error', 'Credenciales invalidas');
        }

        // Regenerar session ID para prevenir Session Fixation
        SecurityMiddleware::regenerateSessionContext('login_success');

        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['user_name'] = $user->nombre;
        $_SESSION['logged_in'] = true;

        return redirect('/dashboard');
    }

    public function logout() {
        SecurityMiddleware::regenerateSessionContext('logout');
        session_destroy();
        return redirect('/login');
    }
}
