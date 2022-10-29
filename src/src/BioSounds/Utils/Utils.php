<?php

namespace BioSounds\Utils;

use BioSounds\Exception\File\Mp3ProcessingException;
use BioSounds\Exception\File\WavProcessingException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Utils
{
    /**
     * @param string $filePath
     * @return string
     */
    public static function getFileFormat(string $filePath): string
    {
        return trim(self::executeCommand('soxi -t ' . $filePath));
    }

    /**
     * @param string $filePath
     * @return null|string
     */
    public static function getFileChannels(string $filePath)
    {
        return self::executeCommand('soxi -c ' . $filePath);
    }

    /**
     * @param string $filePath
     * @return null|string
     */
    public static function getFileDuration(string $filePath)
    {
        return self::executeCommand('soxi -D ' . $filePath);
    }

    /**
     * @param string $filePath
     * @return null|string
     */
    public static function getFileBitRate(string $filePath)
    {
        return self::executeCommand('soxi -b ' . $filePath);
    }

    /**
     * @param string $filePath
     * @return null|string
     */
    public static function getFileSamplingRate(string $filePath)
    {
        return self::executeCommand('soxi -r ' . $filePath);
    }

    /**
     * @param string $originalFilePath
     * @param string $destinationFilePath
     * @param int $startTime
     * @param int $durationTime
     */
    public static function generateSoundFileSelection(
        string $originalFilePath,
        string $destinationFilePath,
        int $startTime,
        int $durationTime
    )
    {
        $command = 'sox ' . $originalFilePath . ' ' . $destinationFilePath . ' ';
        $command .= 'trim ' . $startTime . 's ' . $durationTime . 's';
        self::executeCommand($command);
    }

    /**
     * @param string $originalFilePath
     * @param string $destinationFilePath
     * @param int $minimumFrequency
     * @param int $maximumFrequency
     */
    public static function filterFrequenciesSound(
        string $originalFilePath,
        string $destinationFilePath,
        int $minimumFrequency,
        int $maximumFrequency
    )
    {
        $command = 'sox ' . $originalFilePath . ' ' . $destinationFilePath . ' ';
        $command .= 'sinc ' . $minimumFrequency . '-' . $maximumFrequency;
        self::executeCommand($command);
    }

    /**
     * @param string $filePath
     * @return null|string
     * @throws WavProcessingException
     */
    public static function generateWavFile(string $filePath): ?string
    {
        $pathInfo = pathinfo($filePath);
        $resultFilePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.wav';

        if ($filePath === $resultFilePath) {
            return $resultFilePath;
        }

        try {
            $process = new Process("sox $filePath $resultFilePath");
//            if (self::getFileFormat($filePath) === 'flac') {
//                $process = new Process("flac -dFf  $filePath -o $wavFilePath");
//            }
            $process->mustRun();
            return $resultFilePath;
        } catch (ProcessFailedException $exception) {
            throw new WavProcessingException('', $filePath);
        }
    }

    /**
     * @param string $filePath
     * @return null|string
     * @throws WavProcessingException
     */
    public static function convertToOgg(string $filePath): ?string
    {
        $pathInfo = pathinfo($filePath);
        $resultFilePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.ogg';

        if ($filePath === $resultFilePath) {
            return $resultFilePath;
        }

        try {
            //$process = new Process("oggenc $filePath -q 10 -o $resultFilePath");
            $process = new Process("sox $filePath -C 10 $resultFilePath");
//            if (self::getFileFormat($filePath) === 'flac') {
//                //$process = new Process("flac -Ff --best $filePath -o $resultFilePath");
//                $process = new Process("oggenc $filePath -q 10 -o $resultFilePath");
//            }
            $process->mustRun();
            return $resultFilePath;
        } catch (ProcessFailedException $exception) {
            throw new WavProcessingException($filePath, $exception->getMessage());
        }
    }

    /**
     * @param string $filePath
     * @return string
     * @throws Mp3ProcessingException
     */
    public static function convertToMp3(string $filePath)
    {
        $pathInfo = pathinfo($filePath);
        $resultFilePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.mp3';

        if ($filePath === $resultFilePath) {
            return $resultFilePath;
        }

        try {
            $process = new Process("lame --noreplaygain -f -b 128 $filePath $resultFilePath");
//            if (self::getFileFormat($filePath) === 'flac') {
//                //$process = new Process("flac -Ff --best $filePath -o $resultFilePath");
            $process->mustRun();
            return $resultFilePath;
        } catch (ProcessFailedException $exception) {
            throw new Mp3ProcessingException($filePath, $exception->getMessage());
        }
    }

    /**
     * @param string $command
     * @return string
     */
    public static function executeCommand(string $command)
    {
        return (new Process($command))
            ->mustRun()
            ->getOutput();
    }

    public static function deleteOldTmpFiles()
    {
        self::deleteOldFiles('tmp/', 1);
    }

    public static function getSoundExtensions()
    {
        $soxExtensions = exec('sox -h | grep "FILE FORMATS:"', $out, $retval);
        $soxExtensions = substr($soxExtensions, 20, strlen($soxExtensions));
        return str_replace(" ", ",", $soxExtensions);
    }

    public static function getSetting($name)
    {
        if (isset($_SESSION["settings"])) {
            $settings = $_SESSION["settings"];
            if (!isset($settings[$name]))
                return null;
            return $settings[$name];
        }
    }

    public static function encodePasswordHash($password)
    {
        return base64_encode(password_hash($password, PASSWORD_BCRYPT));
    }

    public static function decodePasswordHash($password)
    {
        return base64_decode($password);
    }

    public static function checkPasswords($plainPwd, $encodedPassword)
    {
        if ($encodedPassword != null && password_verify($plainPwd, self::decodePasswordHash($encodedPassword))) {
            return true;
        }
        return false;
    }

    public static function deleteDirContents($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::deleteDirContents("$dir/$file") : unlink("$dir/$file");
        }
    }

    private static function deleteOldFiles($dir, $days)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        $now = time();

        foreach ($files as $file) {
            if (is_dir($dir . $file)) {
                self::deleteOldFiles($dir . $file . "/", $days);
                if ($now - filemtime($dir . $file) >= 60 * 60 * 24 * $days)
                    rmdir($dir . $file);
            } else {
                if ($now - filemtime($dir . $file) >= 60 * 60 * 24 * $days)
                    unlink($dir . $file);
            }
        }
    }
}
