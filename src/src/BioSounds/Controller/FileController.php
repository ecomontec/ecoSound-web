<?php

namespace BioSounds\Controller;

use BioSounds\Exception\ForbiddenException;
use BioSounds\Service\FileService;
use BioSounds\Utils\Auth;

/**
 * Class FileController
 * @package BioSounds\Controller
 */
class FileController
{
    /**
     * @param string $uploadDirectory
     * @return array
     * @throws \Exception
     */
    public function upload(string $uploadDirectory)
    {
		if (!Auth::isManage()){
			throw new ForbiddenException();
		}

        (new FileService())->upload($_POST, 'tmp/' . $uploadDirectory .'/');

        return json_encode([
            'error_code' => 0,
            'message' => 'Files sent to the upload queue successfully.',
        ]);
    }
}
