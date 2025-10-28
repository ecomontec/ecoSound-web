<?php

namespace BioSounds\Controller;

use BioSounds\Exception\NotAuthenticatedException;
use BioSounds\Utils\Auth;

class HuggingFaceController
{
    /**
     * Search models on Hugging Face Hub.
     * @param string $q
     * @return string JSON
     * @throws \Exception
     */
    public function search($q = '')
    {
        if (!Auth::isUserLogged()) {
            throw new NotAuthenticatedException();
        }

        if (empty($q) && isset($_GET['q'])) {
            $q = $_GET['q'];
        }

        $config = parse_ini_file('config/config.ini');
        $token = isset($config['HUGGINGFACE_API_TOKEN']) ? trim($config['HUGGINGFACE_API_TOKEN']) : null;

        if (empty($token)) {
            return json_encode([
                'error_code' => 1,
                'message' => 'Hugging Face API token not configured.'
            ]);
        }

        $url = 'https://huggingface.co/api/models?search=' . urlencode($q) . '&limit=50';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]);

        $result = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            return json_encode(['error_code' => 1, 'message' => 'Request error: ' . $err]);
        }
        if ($code >= 400) {
            return json_encode(['error_code' => $code, 'message' => 'Hugging Face API returned HTTP ' . $code, 'body' => $result]);
        }

        // Forward HF response as JSON
        return $result;
    }

    /**
     * Get model metadata
     * @param string $modelId
     * @return string
     * @throws \Exception
     */
    public function model($modelId = '')
    {
        if (!Auth::isUserLogged()) {
            throw new NotAuthenticatedException();
        }

        if (empty($modelId) && isset($_GET['id'])) {
            $modelId = $_GET['id'];
        }

        $config = parse_ini_file('config/config.ini');
        $token = isset($config['HUGGINGFACE_API_TOKEN']) ? trim($config['HUGGINGFACE_API_TOKEN']) : null;

        if (empty($token)) {
            return json_encode([
                'error_code' => 1,
                'message' => 'Hugging Face API token not configured.'
            ]);
        }

        $modelId = trim($modelId);
        if ($modelId === '') {
            return json_encode(['error_code' => 1, 'message' => 'Model id is required.']);
        }

        $url = 'https://huggingface.co/api/models/' . urlencode($modelId);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]);

        $result = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            return json_encode(['error_code' => 1, 'message' => 'Request error: ' . $err]);
        }
        if ($code >= 400) {
            return json_encode(['error_code' => $code, 'message' => 'Hugging Face API returned HTTP ' . $code, 'body' => $result]);
        }

        return $result;
    }
}
