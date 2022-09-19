<?php

namespace BioSounds\Controller;

use BioSounds\Classes\BaseClass;
use BioSounds\Exception\InvalidActionException;
use BioSounds\Listener\Exception\ApiExceptionListener;
use BioSounds\Listener\Exception\ExceptionListener;
use BioSounds\Utils\Utils;
use BioSounds\Security\Session as Session;
use Exception;
use Throwable;

class AppController extends BaseClass
{
    private $title = 'ecoSound-web';

    /**
     * AppController constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->initApp();
        parent::__construct();
    }

    /**
     * @return string
     * @throws Exception
     */
    public function start()
    {
        set_exception_handler([new ExceptionListener($this->twig, $this->title), 'handleException']);

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $slugs = array_filter(explode('/', substr($uri, 1)));

        error_log("Oracle database not available!", 0);

        if (count($slugs) === 1) {
            return $this->twig->render('index.html.twig', [
                'title' => $this->title,
            ]);
        }

        foreach ($slugs as $key => $slug) {
            $slugs[$key] = htmlspecialchars(strip_tags($slug));
        }

        $subdirOffset = 1;
        if ($_SERVER['DOCUMENT_ROOT'] == dirname(__DIR__, 3)) {
            $subdirOffset = 0;
        }

        $className = $slugs[0 + $subdirOffset];
        if ($className === 'api') {
            set_exception_handler([new ApiExceptionListener(), 'handleException']);
            return (new ApiController())->route($this->twig, array_slice($slugs, 1 + $subdirOffset));
        }

        $controllerName = __NAMESPACE__ . '\\' . ucfirst($className) . 'Controller';
        $controller = new $controllerName($this->twig);


        $method = $slugs[1 + $subdirOffset];
        if (!method_exists($controller, $method) || !is_callable([$controller, $method])) {
            throw new InvalidActionException($method);
        }

        return call_user_func_array([$controller, $method], array_slice($slugs, 2 + $subdirOffset));
    }

    /**
     * @throws Exception
     */
    private function initApp()
    {
        (new Session())->startSecureSession();
        session_regenerate_id();
        Utils::deleteOldTmpFiles();
    }
}
