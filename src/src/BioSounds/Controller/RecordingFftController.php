<?php

namespace BioSounds\Controller;

use BioSounds\Entity\RecordingFft;
use BioSounds\Entity\UserPermission;
use BioSounds\Entity\User;
use BioSounds\Entity\Permission;
use BioSounds\Provider\ProjectProvider;
use BioSounds\Utils\Auth;

class RecordingFftController extends BaseController
{
    /**
     * @return string
     * @throws \Exception
     */
    public function save(): string
    {
        $recording = new RecordingFft();
        if (isset($_POST['recording_id'])) {
            $recording->delete($_POST['user_id'], $_POST['recording_id']);
            if ($_POST['fft'] > 0) {
                return $recording->insert($_POST);
            }
            return true;
        }
        return false;
    }
}
