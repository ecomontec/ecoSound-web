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
        if (isset($_POST["username"]) && isset($_POST["password"])) {
            $userName = strtolower($_POST["username"]);
            $password = $_POST["password"];
            if (Auth::login($userName, $password)) {
                if ($_SERVER['HTTP_REFERER']) {
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                } else {
                    header('Location: ' . APP_URL);
                }
            }
            exit;
        }
        if (!Auth::isUserLogged()) {
            throw new AuthenticationException();
        } else {
            header('Location: ' . APP_URL);
        }
    }

    public function logout(): bool
    {
        Auth::logout();
        header('Location: ' . APP_URL);
        return true;
    }

    public function getSession()
    {
        return $_SESSION['regenerate_timeout'];
    }
}
