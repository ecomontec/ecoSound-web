<?php

namespace BioSounds\Classes;

use BioSounds\Entity\Setting;
use BioSounds\Provider\CollectionProvider;
use BioSounds\Utils\Auth;
use BioSounds\Utils\Utils;
use Exception;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use \Twig\Extension\DebugExtension;

class BaseClass
{
    const CONFIG_FILENAME = 'config/config.ini';
    const GUIDE_URL = 'https://github.com/nperezg/biosounds/blob/master/docs/user_guide.md';

    /*
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var array
     */
    private $config;


    /**
     * BaseController constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->config = parse_ini_file(self::CONFIG_FILENAME);
        $this->loadConfigFile();

        $loader = new FilesystemLoader(TEMPLATES_DIR);
        $this->twig = new Environment($loader, [
            'cache' => CACHE_DIR,
            'debug' => true
        ]);
        $this->twig->addExtension(new DebugExtension());

        $this->addGlobals();
    }

    /**
     * @throws Exception
     */
    private function loadConfigFile()
    {
        !defined('DRIVER') && define('DRIVER', $this->config['DRIVER']);
        !defined('HOST') && define('HOST', $this->config['HOST']);
        !defined('DATABASE') && define('DATABASE', $this->config['DATABASE']);
        !defined('USER') && define('USER', $this->config['USER']);
        !defined('PASSWORD') && define('PASSWORD', $this->config['PASSWORD']);

        !defined('APP_URL') && define('APP_URL', $this->config['APP_URL']);
        !defined('IMAGES_URL') && define('IMAGES_URL', APP_URL . $this->config['IMAGES_URL']);
        !defined('PROJECT_IMAGES_URL') && define('PROJECT_IMAGES_URL', IMAGES_URL . $this->config['PROJECT_IMAGES_URL']);
        !defined('LICENSE_IMAGES_URL') && define('LICENSE_IMAGES_URL', IMAGES_URL . $this->config['LICENSE_IMAGES_URL']);
        !defined('ABSOLUTE_DIR') && define('ABSOLUTE_DIR', $this->config['ABSOLUTE_DIR']);
        !defined('TEMPLATES_DIR') && define('TEMPLATES_DIR', $this->config['TEMPLATES_DIR']);
        !defined('CACHE_DIR') && define('CACHE_DIR', $this->config['CACHE_DIR']);
        !defined('LOGO') && define('LOGO', IMAGES_URL . $this->config['LOGO']);
        !defined('TMP_DIR') && define('TMP_DIR', $this->config['TMP_DIR']);

        !defined('QUEUE_NAME') && define('QUEUE_NAME', $this->config['QUEUE_NAME']);
        !defined('QUEUE_HOST') && define('QUEUE_HOST', $this->config['QUEUE_HOST']);
        !defined('QUEUE_PORT') && define('QUEUE_PORT', $this->config['QUEUE_PORT']);
        !defined('QUEUE_USER') && define('QUEUE_USER', $this->config['QUEUE_USER']);
        !defined('QUEUE_PASSWORD') && define('QUEUE_PASSWORD', $this->config['QUEUE_PASSWORD']);

        !defined('WINDOW_WIDTH') && define('WINDOW_WIDTH', $this->config['WINDOW_WIDTH']);
        !defined('SPECTROGRAM_LEFT') && define('SPECTROGRAM_LEFT', $this->config['SPECTROGRAM_LEFT']);
        !defined('SPECTROGRAM_RIGHT') && define('SPECTROGRAM_RIGHT', $this->config['SPECTROGRAM_RIGHT']);
        !defined('SPECTROGRAM_HEIGHT') && define('SPECTROGRAM_HEIGHT', $this->config['SPECTROGRAM_HEIGHT']);

        !defined('ERROR_EMPTY_ID') && define('ERROR_EMPTY_ID', $this->config['ERROR_EMPTY_ID']);

        if (!isset($_SESSION['settings'])) {
            $_SESSION['settings'] = (new Setting())->getList();
        }
    }

    /**
     * @throws Exception
     */
    protected function addGlobals()
    {
        $this->twig->addGlobal('logoUrl', LOGO);
        $this->twig->addGlobal('imagesUrl', IMAGES_URL);
        $this->twig->addGlobal('projectImagesUrl', PROJECT_IMAGES_URL);
        $this->twig->addGlobal('licenseImagesUrl', LICENSE_IMAGES_URL);
        $this->twig->addGlobal('baseUrl', APP_URL);
        $this->twig->addGlobal('project_name', Utils::getSetting(Setting::PROJECT_NAME));
        $this->twig->addGlobal('project_description', Utils::getSetting(Setting::PROJECT_DESCRIPTION));
        $this->twig->addGlobal('projectLicense', Utils::getSetting(Setting::FILES_LICENSE));
        $this->twig->addGlobal('license_detail', Utils::getSetting(Setting::FILES_LICENSE_DETAIL));
        $this->twig->addGlobal('guide_url', self::GUIDE_URL);
        $this->twig->addGlobal('isUserLogged', Auth::isUserLogged());
        $this->twig->addGlobal('collections', Auth::isUserAdmin() ? (new CollectionProvider())->getList() : (new CollectionProvider())->getAccessedList((Auth::getUserID() == null) ? 0 : Auth::getUserID()));
        $this->twig->addGlobal('username', Auth::getUserName());
        $this->twig->addGlobal('is_admin', Auth::isUserAdmin());
        $this->twig->addGlobal('user_id', Auth::getUserID());
        $this->twig->addGlobal('regenerate_timeout', $_SESSION['regenerate_timeout']);
    }
}
