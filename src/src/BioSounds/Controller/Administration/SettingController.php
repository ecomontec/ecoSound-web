<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\Setting;
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
		if (!Auth::isManage()){
			throw new ForbiddenException();
		}
		echo Utils::getSetting('license');

        return $this->twig->render('administration/settings.html.twig', [
            'projectFft' => Utils::getSetting('fft'),
            'ffts' => [4096,2048,1024,512,256,128,],
            'licenses' => [
                ['value' => 'Copyright', 'description'=> 'Copyright'],
                ['value' => 'CC BY', 'description'=>'CC BY'],
                ['value' => 'CC BY-SA', 'description'=>'CC BY-SA'],
                ['value' => 'CC BY-ND', 'description'=>'CC BY-ND'],
                ['value' => 'CC BY-NC', 'description'=>'CC BY-NC'],
                ['value' => 'CC BY-NC-SA', 'description'=>'CC BY-NC-SA'],
                ['value' => 'CC BY-NC-ND', 'description'=>'CC BY-NC-ND'],
            ],
        ]);
    }

    /**
     * @return bool
     * @throws \Exception
     */
	public function save()
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        $setting = new Setting();
        foreach ($_POST as $key => $value) {
            $setting->update($key, $value);
        }

        $_SESSION['settings'] = $setting->getList();

        return json_encode([
            'errorCode' => 0,
            'message' => 'Settings saved successfully.',
        ]);
    }
}
