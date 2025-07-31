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
        (new FileService())->upload($_POST, 'tmp/' . $uploadDirectory . '/');

        return json_encode([
            'error_code' => 0,
            'message' => 'Files sent to the upload queue successfully.',
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
                        'message' => 'Data format error in column 1, row ' . $i . ': Expected date in format YYYY-MM-DD HH:MM:SS.',
                    ]);
                }
                if (!strlen($d[1]) || !filter_var($d[1], FILTER_SANITIZE_NUMBER_FLOAT)) {
                    return json_encode([
                        'error_code' => 0,
                        'message' => 'Data format error in column 2, row ' . $i . ': Expected float number.',
                    ]);
                }
                if (!strlen($d[2]) || !filter_var($d[2], FILTER_SANITIZE_NUMBER_FLOAT)) {
                    return json_encode([
                        'error_code' => 0,
                        'message' => 'Data format error in column 3, row ' . $i . ': Expected float number.',
                    ]);
                }
                if (strlen($d[3]) && !filter_var($d[3], FILTER_SANITIZE_STRING)) {
                    return json_encode([
                        'error_code' => 0,
                        'message' => 'Data format error in column 4, row ' . $i . ': Expected string.',
                    ]);
                }
                if (strlen($d[4]) && !filter_var($d[4], FILTER_SANITIZE_NUMBER_INT)) {
                    return json_encode([
                        'error_code' => 0,
                        'message' => 'Data format error in column 5, row ' . $i . ': Expected integer.',
                    ]);
                }
                if (strlen($d[5]) && !filter_var($d[5], FILTER_SANITIZE_NUMBER_INT)) {
                    return json_encode([
                        'error_code' => 0,
                        'message' => 'Data format error in column 6, row ' . $i . ': Expected integer.',
                    ]);
                }
                if (strlen($d[6]) && !filter_var($d[6], FILTER_SANITIZE_NUMBER_INT)) {
                    return json_encode([
                        'error_code' => 0,
                        'message' => 'Data format error in column 7, row ' . $i . ': Expected integer.',
                    ]);
                }
                if (strlen($d[7]) && !filter_var($d[7], FILTER_SANITIZE_NUMBER_INT)) {
                    return json_encode([
                        'error_code' => 0,
                        'message' => 'Data format error in column 8, row ' . $i . ': Expected integer.',
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
            isset($d[4]) && $d[4]!= '' ? $arr['bitdepth'] = $d[4] : '';
            isset($d[5]) && $d[5]!= ''  ? $arr['channel_num'] = $d[5] : '';
            isset($d[6]) && $d[6]!= ''  ? $arr['duty_cycle_recording'] = $d[6] : '';
            isset($d[7]) && $d[7]!= ''  ? $arr['duty_cycle_period'] = $d[7] : '';
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
