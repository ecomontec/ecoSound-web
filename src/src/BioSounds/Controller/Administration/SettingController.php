<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\Api;
use BioSounds\Entity\Setting;
use BioSounds\Entity\User;
use BioSounds\Utils\Auth;
use BioSounds\Utils\Utils;
use Cassandra\Varint;

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

        if (!isset($_SESSION['syncApi']) || $_SESSION['syncApi'] < strtotime('today')) {
            $data = (new Api())->getApis();
            $this->synchronize($data);
            $_SESSION['syncApi'] = strtotime('today');
        }

        return $this->twig->render('administration/settings.html.twig', [
            'user' => (new User)->getFftValue(Auth::getUserID()),
            'projectFft' => Utils::getSetting('fft'),
            'ffts' => [4096, 2048, 1024, 512, 256, 128,],
            'api_key' => base64_encode(APP_URL),
            'setting' => (new Setting())->getList(),
        ]);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function save()
    {
        $setting = new Setting();
        $data['itemID'] = Auth::getUserID();
        $data['fft'] = $_POST['fft'];
        (new User())->updateUser($data);
        unset($_POST['itemID']);
        unset($_POST['fft']);
        $_POST['shared'] = isset($_POST['shared']) ? 1 : 0;
        foreach ($_POST as $key => $value) {
            if (strrpos($key, '_')) {
                $key = substr($key, 0, strrpos($key, '_'));
            }
            (new Setting())->update($key, $value);
            $setting_data[$key] = $value;
        }

        $_SESSION['settings'] = $setting->getList();
        if (!$this->isLocalAddress(APP_URL)) {
            $url = HOST_URL . "/api/admin/settings/api";
            $data = http_build_query([
                'api' => base64_encode(APP_URL),
                'server_name' => $setting_data['server_name'],
                'last_updated' => date('Y-m-d H:i:s'),
                'latitude' => $setting_data['latitude'],
                'longitude' => $setting_data['longitude'],
                'shared' => $setting_data['shared'],
            ]);

            $options = [
                'http' => [
                    'header' => [
                        "Host: " . parse_url(HOST_URL)['host'],
                        "Content-Type: application/x-www-form-urlencoded",
                        "Content-Length: " . strlen($data),
                    ],
                    'method' => 'POST',
                    'content' => $data,
                ],
            ];

            $context = stream_context_create($options);
            $contents = file_get_contents($url, false, $context);
            $apis = json_decode($contents, true);
            $this->synchronize($apis);
        }

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

    public function view()
    {
        return json_encode([
            'errorCode' => 0,
            'data' => $this->twig->render('administration/apis.html.twig', [
                'apis' => (new Api())->getApis(),
            ]),
        ]);
    }

    public function api()
    {
        $apiProvider = new Api();

        if (!empty($_POST['api'])) {
            foreach ($_POST as $key => $value) {
                $data[$key] = $value;
            }

            if ($apiProvider->isValid($data['api'])) {
                $apiProvider->updateApi($data);
            } else {
                $apiProvider->insertApi($data);
            }
        }

        return json_encode($apiProvider->getApis());
    }

    public function synchronize($apis)
    {
        $apiProvider = new Api();
        foreach ($apis as $api) {
            if ($apiProvider->isValidById($api['api_id'])) {
                $apiProvider->updateApi($api);
            } else {
                $apiProvider->insertApi($api);
            }
        }
    }

    function isLocalAddress($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) return false;

        $localNames = ['localhost', '127.0.0.1', '::1'];
        if (in_array($host, $localNames)) return true;

        $ip = gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)) {
            return $this->isPrivateIP($ip);
        }

        return false;
    }

    function isPrivateIP($ip)
    {
        return
            preg_match('/^10\./', $ip) ||
            preg_match('/^192\.168\./', $ip) ||
            preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ip) ||
            preg_match('/^127\./', $ip) ||
            $ip === '::1';
    }
}
