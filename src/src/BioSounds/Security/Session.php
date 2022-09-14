<?php

namespace BioSounds\Security;

use Exception;

class Session
{
    /**
     * @throws Exception
     */
    public function startSecureSession()
    {
        $session_name = 'biosounds_session';
        $secure = false;
        $httpOnly = true;
        // Forces sessions to only use cookies.
        if (!ini_set('session.use_only_cookies', 1)) {
            error_log('Session: error when setting cookies.');
            throw new Exception('There was a problem with your session. Please contact the administrator.');
        }

        // Gets current cookies params.
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params(1800, $cookieParams['path'], $cookieParams['domain'], $secure, $httpOnly);

        //No cache for avoiding back button 'document expired' problem
        header("Cache-Control: no cache");
        session_cache_limiter("private, must-revalidate");

        // Sets the session name to the one set above.
        session_name($session_name);
        session_start();

        if (!isset($_SESSION['regenerate_timeout'])) {
            session_regenerate_id(true);
            $_SESSION['regenerate_timeout'] = time();
        }

        // Regenerate session ID every five minutes:
        if ($_SESSION['regenerate_timeout'] < time() - 300) {
            session_regenerate_id(true);
            $_SESSION['regenerate_timeout'] = time();
        }
    }
}
