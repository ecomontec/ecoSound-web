<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\Setting;
use BioSounds\Entity\User;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Utils\Auth;
use BioSounds\Utils\Utils;

class SettingController extends BaseController
{
    const SECTION_TITLE = 'Settings';

    /**
     * @return false|string
     * @throws \Exception
     */
    public function show()
    {
        echo Utils::getSetting('license');

        return $this->twig->render('administration/settings.html.twig', [
            'user' => (new User)->getFftValue(Auth::getUserID()),
            'projectFft' => Utils::getSetting('fft'),
            'ffts' => [4096, 2048, 1024, 512, 256, 128,],
        ]);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function save()
    {
        $setting = new Setting();
        foreach ($_POST as $value) {
            $data['itemID'] = Auth::getUserID();
            $data['fft'] = $value;
            (new User())->updateUser($data);
        }

        $_SESSION['settings'] = $setting->getList();

        return json_encode([
            'errorCode' => 0,
            'message' => 'Settings saved successfully.',
        ]);
    }

    public function width()
    {
        $_SESSION['width'] = $_POST['width'];
        return true;
    }
}
