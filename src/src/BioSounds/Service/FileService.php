<?php

namespace BioSounds\Service;

use BioSounds\Entity\File;
use BioSounds\Entity\Recording;
use BioSounds\Exception\File\FileCopyException;
use BioSounds\Exception\File\FileExistsException;
use BioSounds\Exception\File\FileInvalidException;
use BioSounds\Exception\File\FileNotFoundException;
use BioSounds\Exception\File\FileQueueNotFoundException;
use BioSounds\Exception\File\FolderCreationException;
use BioSounds\Provider\CollectionProvider;
use BioSounds\Provider\FileProvider;
use BioSounds\Provider\RecordingProvider;
use BioSounds\Service\Queue\RabbitQueueService;
use BioSounds\Utils\Auth;
use BioSounds\Utils\Utils;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Class FileService
 * @package BioSounds\Service
 */
class FileService
{
    const SUCCESS_MESSAGE = 'Files successfully sent to upload.';
    const DATETIME_INVALID_MESSAGE = 'File %s has no valid date/time pattern. Inserted without date or time.';
    const FILE_EXISTS_MESSAGE = 'File %s not inserted. It already exists in the system.';
    const DATE_FORMAT = '%d-%d-%d';
    const TIME_FORMAT = '%d:%d:%d';
    const DATE_TIME_PATTERN = '/(\d{4})(\d{2})(\d{2})_(\d{2})(\d{2})(\d{2})/';

    /**
     * @var Recording
     */
    private $recordingProvider;

    /**
     * @var FileProvider
     */
    private $fileProvider;

    /**
     * @var RabbitQueueService
     */
    private $queueService;

    /**
     * FileService constructor.
     */
    public function __construct()
    {
        $this->recordingProvider = new RecordingProvider();
        $this->collectionProvider = new CollectionProvider();
        $this->fileProvider = new FileProvider();
        $this->queueService = new RabbitQueueService();
    }

    /**
     * @param array $request
     * @param string $uploadPath
     * @return array
     * @throws \Exception
     */
    public function upload(array $request, string $uploadPath): string
    {
        $message = '';
        $colID = $request['colId'];
        $site = isset($request['site']) ? $request['site'] : null;
        $site = $site == 0 ? null : $site;
        $recorder = $request['recorder'];
        $microphone = $request['microphone'];
        $dateFromFile = isset($request['dateFromFile']) ? $request['dateFromFile'] : false;
        $time = isset($request['time']) ? $request['time'] : null;
        $date = isset($request['date']) ? $request['date'] : null;
        $doi = isset($request['doi']) && !empty($request['doi']) ? $request['doi'] : null;
        $license = isset($request['license']) ? $request['license'] : null;
        $type = isset($request['type']) && !empty($request['type']) ? $request['type'] : null;
        $medium = isset($request['medium']) && !empty($request['medium']) ? $request['medium'] : null;

        if (!is_dir($uploadPath) || !$handle = opendir($uploadPath)) {
            throw new FileNotFoundException($uploadPath);
        }
        $collection = $this->collectionProvider->get($colID);
        try {
            $list = [];
            while ($fileName = readdir($handle)) {
                $fileDate = $date;
                $fileTime = $time;
                if ($fileName == '.' || $fileName == '..') {
                    continue;
                }

                if (!empty($result = $this->recordingProvider->getByHash(hash_file('md5', $uploadPath . $fileName), $colID))) {
                    $names[] = $fileName;
                    $ids[] = $result['recording_id'];
                    $message = sprintf("Recording " . implode(", ", $names) . " cannot be uploaded because collection " . $collection->getName() . " already contains that recording (ID: " . implode(", ", $ids) . ").");
                    continue;
                }

                if ($dateFromFile) {
                    if (!preg_match($this::DATE_TIME_PATTERN, $fileName, $dateTime)) {
                        $message = sprintf($message . ' ' . $this::DATETIME_INVALID_MESSAGE, $fileName);
                    } else {
                        $fileDate = sprintf($this::DATE_FORMAT, $dateTime[1], $dateTime[2], $dateTime[3]);
                        $fileTime = sprintf($this::TIME_FORMAT, $dateTime[4], $dateTime[5], $dateTime[6]);
                    }
                }

                $file = (new File())
                    ->setPath($uploadPath . $fileName)
                    ->setDate($fileDate)
                    ->setTime($fileTime)
                    ->setCollection($colID)
                    ->setDirectory(rand(1, 100))
                    ->setSite($site)
                    ->setRecorder($recorder)
                    ->setMicrophone($microphone)
                    ->setName($fileName)
                    ->setDoi($doi)
                    ->setLicense($license)
                    ->setUser(Auth::getUserID())
                    ->setType($type)
                    ->setMedium($medium);
                $list[] = $this->fileProvider->insert($file);
            }
            if ($message == '') {
                $this->queueService->queue(json_encode($list), 'upload', $request['file-uploader_count']);
            }
        } catch (\Exception $exception) {
            Utils::deleteDirContents($uploadPath);
            throw $exception;
        } finally {
            closedir($handle);
            $this->queueService->closeConnection();
        }
        return $message;
    }

    /**
     * @param int $fileId
     * @throws \Exception
     */
    public function process(int $fileId)
    {
        $soundId = null;
        try {
            if (empty($file = $this->fileProvider->get($fileId))) {
                throw new FileQueueNotFoundException($fileId);
            }

            $file->setStatus(File::STATUS_IN_PROGRESS);
            $this->fileProvider->update($file);

            if (!file_exists($file->getPath())) {
                throw new FileNotFoundException($file->getPath());
            }

            if (!is_file($file->getPath())) {
                throw new FileInvalidException($file->getPath());
            }

            $fileHash = hash_file('md5', $file->getPath());
            if (!empty($this->recordingProvider->getByHash($fileHash, $file->getCollection()))) {
                throw new FileExistsException($file->getName());
            }

            if (empty($fileFormat = Utils::getFileFormat($file->getPath()))) {
                return;
            }

            $wavFilePath = $file->getPath();
            if ($fileFormat !== 'wav') {
                $wavFilePath = Utils::generateWavFile($file->getPath());
            }

            $sound = [
                Recording::COL_ID => $file->getCollection(),
                Recording::FILE_DATE => $file->getDate(),
                Recording::DIRECTORY => $file->getDirectory(),
                Recording::FILE_TIME => $file->getTime(),
                Recording::SITE_ID => $file->getSite(),
                Recording::RECORDER_ID => $file->getRecorder(),
                Recording::MICROPHONE_ID => $file->getMicrophone(),
                Recording::FILENAME => $file->getName(),
                Recording::CHANNEL_NUM => Utils::getFileChannels($wavFilePath),
                Recording::FILE_SIZE => filesize($wavFilePath),
                Recording::SAMPLING_RATE => Utils::getFileSamplingRate($wavFilePath),
                Recording::BITRATE => Utils::getFileBitRate($wavFilePath),
                Recording::NAME => $file->getName(),
                Recording::DURATION => floatval(Utils::getFileDuration($wavFilePath)),
                Recording::MD5_HASH => $fileHash,
                Recording::DOI => $file->getDoi(),
                Recording::LICENSE_ID => $file->getLicense(),
                Recording::USER_ID => $file->getUser(),
                Recording::Type => $file->getType(),
                Recording::Medium => $file->getMedium(),
            ];

            $sound[Recording::ID] = (new RecordingProvider())->insert($sound);
            $soundId = $sound[Recording::ID];

            $path = ABSOLUTE_DIR . 'sounds/sounds/' . $file->getCollection() . '/' . $file->getDirectory();
            if (!is_dir(ABSOLUTE_DIR)) {
                throw new FileNotFoundException(ABSOLUTE_DIR);
            }

            if (
                !is_dir(ABSOLUTE_DIR . 'sounds/sounds/' . $file->getCollection()) &&
                !mkdir(ABSOLUTE_DIR . 'sounds/sounds/' . $file->getCollection(), 0755, true)
            ) {
                throw new FolderCreationException(ABSOLUTE_DIR . 'sounds/sounds/' . $file->getCollection());
            }

            if (!is_dir($path) && !mkdir($path, 0755, true)) {
                throw new FolderCreationException($path);
            }

            if (!rename($file->getPath(), $path . '/' . $file->getName())) {
                throw new FileCopyException($file->getPath(), $path);
            }

            if ($fileFormat !== 'wav') {
                $pathInfo = pathinfo($wavFilePath);
                if (!rename($wavFilePath, $path . '/' . $pathInfo['filename'] . '.wav')) {
                    throw new FileCopyException($wavFilePath, $path);
                }
            }

            (new ImageService())->generateImages($sound);

            $this->updateFileStatus($file, File::STATUS_SUCCESS, $sound[Recording::ID]);
            return json_encode([
                'errorCode' => 0,
            ]);
        } catch (FileQueueNotFoundException $exception) {
            error_log($exception);
            return $exception;
        } catch (ProcessFailedException $exception) {
            error_log($exception);
            if (!empty($file)) {
                $this->updateFileStatus(
                    $file,
                    File::STATUS_ERROR,
                    $soundId,
                    'Command failed : ' . $exception->getProcess()->getCommandLine()
                );
                return 'Command failed : ' . $exception->getProcess()->getCommandLine();
            }
        } catch (\Exception $exception) {
            error_log($exception);
            if (!empty($file)) {
                $this->updateFileStatus($file, File::STATUS_ERROR, $soundId, $exception->getMessage());
                return $exception->getMessage();
            }
        }
    }

    /**
     * @param File $file
     * @param int $status
     * @param int|null $recordingId
     * @param string|null $errorMessage
     * @throws \Exception
     */
    private function updateFileStatus(
        File $file,
        int $status,
        int $recordingId = null,
        string $errorMessage = null
    )
    {
        $file->setError(empty($errorMessage) ? '' : $errorMessage);
        $file->setRecording($recordingId);
        $file->setStatus($status);
        $this->fileProvider->update($file);
    }
}
