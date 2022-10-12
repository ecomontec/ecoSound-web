<?php

namespace BioSounds\Utils;

use BioSounds\Entity\User;

class Auth
{
    /**
     * @return int|null
     */
    static function getUserLoggedID(): ?int
    {
        if (isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        }
        return null;
    }

    /**
     * @return string
     */
    static function getUserName()
    {
        if (isset($_SESSION['username'])) {
            return $_SESSION['username'];
        }
        return '';
    }

    /**
     * @param string $username
     * @param string $password
     * @return bool
     * @throws \Exception
     */
    static function login(string $username, string $password): bool
    {
        $user = new User();
        $encPwd = $user->getPassword($username);

        if (Utils::checkPasswords($password, $encPwd)) {
            $user_browser = $_SERVER['HTTP_USER_AGENT'];
            $user_id = $user->getUserId($username);
            $user_id = preg_replace('/[^0-9]+/', '', $user_id); // XSS protection
            $_SESSION['user_id'] = $user_id;

            $username = preg_replace('/[^a-zA-Z0-9_\-]+/', '', $username); // XSS protection
            $_SESSION['username'] = $username;
            $_SESSION['login_string'] = hash('sha512', $encPwd . $user_browser);
            return true;
        }

        return false;
//		else {
//			/*$now = time();
//			$mysqli->query("INSERT INTO login_attempts(user_id, time)
//							VALUES ('$user_id', '$now')");*/
//			return false;
//		}
    }

    static function logout()
    {
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_unset();
        session_destroy();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    static function isUserLogged()
    {
        if (isset($_SESSION['login_string'])) {
            $userId = $_SESSION['user_id'];
            if (empty($userId)) {
                return false;
            }

            #get host name of user
            /*	$remote_host = $_SERVER['REMOTE_ADDR'];
                $user_loggedin = query_one('SELECT COUNT(*) FROM Cookies WHERE user_id = '$cookie_to_test1' AND cookie = '$cookie_to_test2' AND hostname = '$remote_host' LIMIT 1", $connection);*/
            return (new User())->isUserActive($userId);
        }
        return false;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    static function isUserAdmin()
    {
        if (!self::isUserLogged()) {
            return false;
        }
        return (new User())->isUserAdmin($_SESSION['user_id']);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    static function isManage()
    {
        if (!self::isUserLogged()) {
            return false;
        }
        if ((new User())->isUserAdmin($_SESSION['user_id'])) {
            return true;
        }
        return (new User())->isManage($_SESSION['user_id']);
    }

    /**
     * @return int|null
     */
    static function getUserID(): ?int
    {
        if (isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        }
        return null;
    }
}
