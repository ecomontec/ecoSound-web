<?php

namespace BioSounds\Controller;

use BioSounds\Entity\PlayLog;
use BioSounds\Exception\NotAuthenticatedException;
use BioSounds\Utils\Auth;

class PlayLogController
{
    /**
     * @return false|string|void
     * @throws \Exception
     */
    public function save()
    {
        if(!Auth::isUserLogged()){
            return json_encode([
                'errorCode' => 0,
                'message' => 'User not authenticated. Skipping play log saving.'
            ]);
        }

		$data[PlayLog::RECORDING_ID] = filter_var($_POST['recordingId'], FILTER_SANITIZE_NUMBER_INT);
		$data[PlayLog::USER_ID] = filter_var($_POST['userId'], FILTER_SANITIZE_NUMBER_INT);
		$data[PlayLog::START_TIME] = date('Y-m-d H:i:s', (int)$_POST['startTime']);
		$data[PlayLog::STOP_TIME] = date('Y-m-d H:i:s', (int)$_POST['stopTime']);

		(new PlayLog())->insert($data);

        return json_encode([
            'errorCode' => 0,
        ]);
	}
}
