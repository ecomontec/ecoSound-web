<?php

namespace BioSounds\Controller;

use BioSounds\Exception\AuthenticationException;
use BioSounds\Utils\Auth;

class LoginController extends BaseController
{
    /**
     * @return bool
     * @throws \Exception
     */
    public function login(): bool
    {
        $userName = strtolower($_POST["username"]);
        $password = $_POST["password"];
        if (Auth::login($userName, $password)) {
            if ($_SERVER['HTTP_REFERER']) {
                header('Location: ' . $_SERVER['HTTP_REFERER']);
            } else {
                header('Location: ' . APP_URL);
            }
            return true;
        }

        throw new AuthenticationException();
    }

    public function logout(): bool
    {
        Auth::logout();
        header('Location: ' . APP_URL);
        return true;
    }
}
