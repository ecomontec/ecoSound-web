<?php

namespace BioSounds\Controller;

use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\RecordingProvider;
use BioSounds\Service\FileService;
use BioSounds\Utils\Auth;
use Cassandra\Varint;

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
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }

        $message = (new FileService())->upload($_POST, 'tmp/' . $uploadDirectory . '/');

        return json_encode([
            'error_code' => 0,
            'message' => $message ? $message : 'Files sent to the upload queue successfully.',
        ]);
    }

    public function metadata()
    {
        $handle = fopen($_FILES['metaDataFile']['tmp_name'], "rb");
        $data = [];
        $i = 1;
        while (!feof($handle)) {
            $d = fgetcsv($handle);
            if ($d && $d[0] != 'recording_start') {
                $data[] = $d;
                if (!strlen($d[0]) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $d[0])) {
                    return json_encode([
                        'error_code' => 0,
                        'message' => 'Row ' . $i . ' column 1 data format error.',
                    ]);
                }
                if (!strlen($d[1]) || !filter_var($d[1], FILTER_SANITIZE_NUMBER_FLOAT)) {
                    return json_encode([
                        'error_code' => 0,
                        'message' => 'Row ' . $i . ' column 2 data format error.',
                    ]);
                }
                if (!strlen($d[2]) || !filter_var($d[2], FILTER_SANITIZE_NUMBER_FLOAT)) {
                    return json_encode([
                        'error_code' => 0,
                        'message' => 'Row ' . $i . ' column 3 data format error.',
                    ]);
                }
                if (strlen($d[3]) && !filter_var($d[3], FILTER_SANITIZE_STRING)) {
                    return json_encode([
                        'error_code' => 0,
                        'message' => 'Row ' . $i . ' column 4 data format error.',
                    ]);
                }
                if (strlen($d[4]) && !filter_var($d[4], FILTER_SANITIZE_NUMBER_INT)) {
                    return json_encode([
                        'error_code' => 0,
                        'message' => 'Row ' . $i . ' column 5 data format error.',
                    ]);
                }
                if (strlen($d[5]) && !filter_var($d[5], FILTER_SANITIZE_NUMBER_INT)) {
                    return json_encode([
                        'error_code' => 0,
                        'message' => 'Row ' . $i . ' column 6 data format error.',
                    ]);
                }
            }
            $i++;
        }
        fclose($handle);
        foreach ($data as $d) {
            $arr['col_id'] = $_POST['colId'];
            $arr['user_id'] = Auth::getUserID();
            $arr['file_date'] = explode(' ', $d[0])[0];
            $arr['file_time'] = explode(' ', $d[0])[1];
            $arr['duration'] = $d[1];
            $arr['sampling_rate'] = $d[2];
            isset($d[3]) ? $arr['name'] = $d[3] : '';
            isset($d[4]) ? $arr['bitrate'] = $d[4] : '';
            isset($d[5]) ? $arr['channel_num'] = $d[5] : '';
            $arr['data_type'] = 'meta-data';
            $arr['creation_date'] = date('Y-m-d H:i:s', time());
            (new RecordingProvider())->insert($arr);
        }
        return json_encode([
            'error_code' => 0,
            'message' => 'Upload success.',
        ]);
    }
}
