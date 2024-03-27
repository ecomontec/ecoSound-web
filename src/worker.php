<?php

require_once __DIR__ . '/vendor/autoload.php';

use BioSounds\Entity\Queue;
use BioSounds\Provider\IndexLogProvider;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use BioSounds\Service\FileService;


$config = parse_ini_file('config/config.ini');

define('ABSOLUTE_DIR', $config['ABSOLUTE_DIR']);
define('TMP_DIR', $config['TMP_DIR']);
define('DRIVER', $config['DRIVER']);
define('HOST', $config['HOST']);
define('DATABASE', $config['DATABASE']);
define('USER', $config['USER']);
define('PASSWORD', $config['PASSWORD']);
define('QUEUE_NAME', $config['QUEUE_NAME']);
define('QUEUE_HOST', $config['QUEUE_HOST']);
define('QUEUE_PORT', $config['QUEUE_PORT']);
define('QUEUE_USER', $config['QUEUE_USER']);
define('QUEUE_PASSWORD', $config['QUEUE_PASSWORD']);

$connection = new AMQPStreamConnection(QUEUE_HOST, QUEUE_PORT, QUEUE_USER, QUEUE_PASSWORD);

$channel = $connection->channel();
$channel->queue_declare('list', false, true, false, false);

$callback = function ($msg) use ($config) {
    try {
        $headers = $msg->get('application_headers')->getNativeData();
        $data = json_decode($msg->body, true);
        if ($headers['list_type'] == 'index analysis') {
            $id = (new IndexLogProvider())->getId();
        }
        $addedFiles = "";
        $errorFiles = "";
        foreach ($data as $d) {
            $result = (new Queue())->getById($headers['queue_id']);
            if ($result['status'] == '2') {
                $arr['status'] = 0;
            }
            if ($result['error'] == 'being cancelled.') {
                $result['status'] = -2;
                $result['error'] = '';
                $result['stop_time'] = date('Y-m-d H:i:s');
                (new Queue())->update($result);
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                return;
            } else {
                $back = '';
                if ($headers['list_type'] == 'AI model') {
                    if ($d['creator_type'] == 'BirdNET-Analyzer') {
                        $back = (new \BioSounds\Controller\RecordingController())->BirdNETAnalyzer($d);
                    }
                    if ($d['creator_type'] == 'batdetect2') {
                        $back = (new \BioSounds\Controller\RecordingController())->batdetect2($d);
                    }
                }
                if ($headers['list_type'] == 'upload') {
                    $back = (new FileService())->process($d);
                    if (isset(json_decode($back)->fileExists) && json_decode($back)->fileExists != '') {
                        if ($addedFiles != '') {
                            $addedFiles .= ", ";
                        }
                        $addedFiles .= json_decode($back)->fileExists;
                    } elseif (isset(json_decode($back)->formatErrors) && json_decode($back)->formatErrors != '') {
                        if ($errorFiles != '') {
                            $errorFiles .= ", ";
                        }
                        $errorFiles .= json_decode($back)->formatErrors;
                    }
                    if ($addedFiles) {
                        $arr['warning'] = "File " . $addedFiles . " already exists in the system.";
                    }
                    if ($errorFiles) {
                        $arr['warning'] = "File " . $errorFiles . " had an unrecognised date-time format-used 1970-01-01 00:00:00 instead." . $addedFiles ? $arr['warning'] : '';
                    }
                }
                if ($headers['list_type'] == 'index analysis') {
                    $back = (new \BioSounds\Controller\RecordingController())->maads($d, $id);
                }
                if (isset(json_decode($back)->errorCode) && json_decode($back)->errorCode == '0') {
                    $arr['queue_id'] = $headers['queue_id'];
                    if ($result['total'] != (int)$result['completed']) {
                        $arr['completed'] = (int)$result['completed'] + 1;
                    }
                    if ($result['total'] == (int)$result['completed'] + 1) {
                        $arr['stop_time'] = date('Y-m-d H:i:s');
                        $arr['status'] = 1;
                    }
                    (new Queue())->update($arr);
                } elseif ($headers['list_type'] == 'upload') {
                    $arr['queue_id'] = $headers['queue_id'];
                    if ($result['total'] != (int)$result['completed']) {
                        $arr['completed'] = (int)$result['completed'] + 1;
                    }
                    if ($result['total'] == (int)$result['completed'] + 1) {
                        $arr['stop_time'] = date('Y-m-d H:i:s');
                        $arr['status'] = 1;
                    }
                    $arr['error'] .= $back;
                    (new Queue())->update($arr);
                } else {
                    $arr['queue_id'] = $headers['queue_id'];
                    $arr['status'] = -1;
                    $arr['stop_time'] = date('Y-m-d H:i:s');
                    $arr['error'] = $back;
                    (new Queue())->update($arr);
                    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                    return;
                }
            }
        }
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    } catch (\Exception $exception) {
        $arr['queue_id'] = $headers['queue_id'];
        $arr['status'] = -1;
        $arr['stop_time'] = date('Y-m-d H:i:s');
        $arr['error'] = $exception->getMessage();
        (new Queue())->update($arr);
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    }
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('list', '', false, false, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();

