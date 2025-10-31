<?php

namespace BioSounds\Controller;

use BioSounds\Exception\NotAuthenticatedException;
use BioSounds\Utils\Auth;

class HuggingFaceController extends BaseController
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
        // Allow users to include surrounding quotes in config.ini values; strip them here
        if ($token !== null) {
            $token = trim($token, "'\"");
        }

        if (empty($token)) {
            return json_encode([
                'error_code' => 1,
                'message' => 'Hugging Face API token not configured.'
            ]);
        }

        // Only search for image-segmentation models
        $url = 'https://huggingface.co/api/models?search=' . urlencode($q) . '&pipeline_tag=image-segmentation&limit=50';

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
        // Allow users to include surrounding quotes in config.ini values; strip them here
        if ($token !== null) {
            $token = trim($token, "'\"");
        }

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

    /**
     * Execute model inference via Hugging Face Inference API
     * @return string JSON
     * @throws \Exception
     */
    public function execute()
    {
        if (!Auth::isUserLogged()) {
            throw new NotAuthenticatedException();
        }

        // Get JSON body
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if (!$data) {
            return json_encode(['error' => true, 'message' => 'Invalid JSON body', 'raw_body' => substr($body, 0, 200)]);
        }

        $modelId = $data['modelId'] ?? '';
        $recordingId = $data['recordingId'] ?? '';
        $params = $data['params'] ?? [];
        $pipelineTag = $data['pipelineTag'] ?? '';
        $spectrogramUrl = $data['spectrogramUrl'] ?? '';

        if (empty($modelId)) {
            return json_encode(['error' => true, 'message' => 'Model ID is required', 'received_data' => $data]);
        }

        if (empty($recordingId)) {
            return json_encode(['error' => true, 'message' => 'Recording ID is required', 'received_data' => $data]);
        }

        $config = parse_ini_file('config/config.ini');
        $token = isset($config['HUGGINGFACE_API_TOKEN']) ? trim($config['HUGGINGFACE_API_TOKEN']) : null;
        // Allow users to include surrounding quotes in config.ini values; strip them here
        if ($token !== null) {
            $token = trim($token, "'\"");
        }

        if (empty($token)) {
            return json_encode([
                'error' => true,
                'message' => 'Hugging Face API token not configured.'
            ]);
        }

        // Prepare the request to HuggingFace Inference API
        $apiUrl = 'https://api-inference.huggingface.co/models/' . $modelId;

        // For image-segmentation models, use the spectrogram image
        if ($pipelineTag === 'image-segmentation' && !empty($spectrogramUrl)) {
            // Convert URL to local file path
            // URL format: http://localhost:8080/tmp/1882631996/20230512_191000_1-4000_0-595__1.png
            // Local path: /var/www/html/tmp/1882631996/20230512_191000_1-4000_0-595__1.png
            $urlPath = parse_url($spectrogramUrl, PHP_URL_PATH);
            $localImagePath = ABSOLUTE_DIR . $urlPath;
            
            if (!file_exists($localImagePath)) {
                return json_encode(['error' => true, 'message' => 'Spectrogram image file not found: ' . $localImagePath]);
            }
            
            $imageData = file_get_contents($localImagePath);
            if ($imageData === false) {
                return json_encode(['error' => true, 'message' => 'Failed to read spectrogram image: ' . $localImagePath]);
            }
            
            $fileData = $imageData;
            $contentType = 'image/png';
            $fileSize = strlen($imageData);
        } else {
            // For audio models, get the audio file
            try {
                $recordingProvider = new \BioSounds\Provider\RecordingProvider();
                $recordings = $recordingProvider->get($recordingId);
                
                if (!$recordings || count($recordings) === 0 || !isset($recordings[0]['file_path'])) {
                    return json_encode(['error' => true, 'message' => 'Recording not found', 'recording_id' => $recordingId]);
                }

                $recording = $recordings[0];
                $audioFilePath = ABSOLUTE_DIR . $recording['file_path'];
            } catch (\BioSounds\Exception\NotFoundException $e) {
                return json_encode(['error' => true, 'message' => 'Recording not found: ' . $e->getMessage(), 'recording_id' => $recordingId]);
            }
            
            if (!file_exists($audioFilePath)) {
                return json_encode(['error' => true, 'message' => 'Audio file not found: ' . $audioFilePath]);
            }

            // Read audio file
            $fileData = file_get_contents($audioFilePath);
            $contentType = 'audio/wav';
            $fileSize = strlen($fileData);
        }

        // Send request to HuggingFace
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: ' . $contentType
        ]);

        $result = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            return json_encode([
                'error' => true,
                'message' => 'Request error: ' . $err,
                'debug_file_size_kb' => round($fileSize / 1024, 2)
            ]);
        }

        if ($code >= 400) {
            return json_encode([
                'error' => true,
                'message' => 'Hugging Face API returned HTTP ' . $code,
                'raw_response' => $result,
                'debug_file_size_kb' => round($fileSize / 1024, 2)
            ]);
        }

        // Try to parse JSON response
        $parsed = json_decode($result, true);
        
        if ($parsed === null && json_last_error() !== JSON_ERROR_NONE) {
            return json_encode([
                'error' => true,
                'message' => 'Failed to parse API response',
                'json_error' => json_last_error_msg(),
                'raw_output' => substr($result, 0, 500)
            ]);
        }

        // Return the parsed result
        return json_encode([
            'error' => false,
            'data' => $parsed,
            'model_id' => $modelId,
            'recording_id' => $recordingId
        ]);
    }
}
