<?php

namespace BioSounds\Service;

use BioSounds\Entity\Recording;
use BioSounds\Entity\User;
use BioSounds\Utils\Auth;
use BioSounds\Utils\Utils;
use Symfony\Component\Process\Process;

/**
 * Class ImageService
 * @package BioSounds\Service
 */
class ImageService
{
    const SOUNDS_PATH = ABSOLUTE_DIR . 'sounds/sounds/%s/%s/%s';
    const IMAGES_PATH = ABSOLUTE_DIR . 'sounds/images/%s/%s';
    const VIEWPORT_WIDTH = 150;

    private $fftSize;

    /**
     * @var ImageService
     */
    private $spectrogramService;

    /**
     * ImageService constructor.
     * @param int $fftSize
     */
    public function __construct(int $fftSize = 1024)
    {
        $this->spectrogramService = new SpectrogramService();
        $user = new User();
        if ($user->getFFT(Auth::getUserID())) {
            $fftSize = $user->getFFT(Auth::getUserID());
        } elseif (Utils::getSetting('fft')) {
            $fftSize = Utils::getSetting('fft');
        }
        $this->fftSize = $fftSize;
    }

    /**
     * @param array $sound
     * @throws \Exception
     */
    public function generateImages(array $sound): void
    {
        $maxFrequency = floor((int)$sound[Recording::SAMPLING_RATE] / 2);

        $wavFilePath = sprintf(
            self::SOUNDS_PATH,
            $sound[Recording::COL_ID],
            $sound[Recording::DIRECTORY],
            pathinfo($sound[Recording::FILENAME])['filename'] . '.wav'
        );

        $imagesPath = sprintf(
            self::IMAGES_PATH,
            $sound[Recording::COL_ID],
            $sound[Recording::DIRECTORY]
        );

        if (!is_file($wavFilePath)) {
            Utils::generateWavFile(sprintf(
                self::SOUNDS_PATH,
                $sound[Recording::COL_ID],
                $sound[Recording::DIRECTORY],
                $sound[Recording::FILENAME]
            ));
        }

        if (!is_dir($imagesPath)) {
            mkdir($imagesPath, 0777, true);
        }

        $this->spectrogramService->deleteByRecording($sound[Recording::ID]);

        $stereo = $sound[Recording::CHANNEL_NUM] == 2 ? true : false;
        $this->generateThumbnailImage(
            $imagesPath . '/' . $sound[Recording::ID] . '-small_s.png',
            $wavFilePath,
            $maxFrequency,
            $stereo
        );
        $this->generatePlayerImage(
            $imagesPath . '/' . $sound[Recording::ID] . '-player_s.png',
            $wavFilePath,
            $maxFrequency,
            $stereo
        );

        $this->spectrogramService->insert(
            $sound[Recording::ID],
            $sound[Recording::ID] . '-small_s.png',
            'spectrogram-small',
            $maxFrequency
        );

        $this->spectrogramService->insert(
            $sound[Recording::ID],
            $sound[Recording::ID] . '-player_s.png',
            'spectrogram-player',
            $maxFrequency
        );
    }

    /**
     * @param string $destinationFilePath
     * @param string $originalWavFilePath
     * @param int $maxFrequency
     * @param bool $stereo
     */
    public function generateThumbnailImage(
        string $destinationFilePath,
        string $originalWavFilePath,
        int $maxFrequency,
        bool $stereo
    )
    {
        if ($stereo) {
            //Left channel
            (new Process(ABSOLUTE_DIR . 'bin/svt.py -c 1 -s ' . TMP_DIR . '/sl.png -w 300 -h 75 -m '
                . $maxFrequency . ' -f 4096 ' . $originalWavFilePath))->mustRun();
            //Right channel
            (new Process(ABSOLUTE_DIR . 'bin/svt.py -c 2 -s ' . TMP_DIR . '/sr.png -w 300 -h 75 -m '
                . $maxFrequency . ' -f 4096 ' . $originalWavFilePath))->mustRun();
            //Combine channels
            (new Process('montage -tile 1x2 -mode Concatenate ' . TMP_DIR . '/sl.png ' . TMP_DIR . '/sr.png '
                . TMP_DIR . '/sall.png'))->mustRun();

            (new Process("convert -fill white -draw \"text 5,15 '" . $maxFrequency . " Hz '\" -draw \"text 5,85 '"
                . $maxFrequency . " Hz'\" -draw \"text 290,15 'L'\" -draw \"text 290,85 'R'\" "
                . TMP_DIR . '/sall.png -quality 10 ' . $destinationFilePath))->mustRun();

            unlink(TMP_DIR . '/sl.png');
            unlink(TMP_DIR . '/sr.png');
            unlink(TMP_DIR . '/sall.png');

        } else {
            (new Process(ABSOLUTE_DIR . 'bin/svt.py -s ' . TMP_DIR . '/small_s1.png -w 300 -h 150 -m '
                . $maxFrequency . ' -f 4096 ' . $originalWavFilePath))->mustRun();

            (new Process("convert -fill white -draw \"text 5,15 '" . $maxFrequency . " Hz '\" "
                . TMP_DIR . '/small_s1.png -quality 10 ' . $destinationFilePath))->mustRun();

            unlink(TMP_DIR . '/small_s1.png');
        }
    }

    /**
     * @param string $destinationFilePath
     * @param string $wavFilePath
     * @param int $minFrequency
     * @param int $maxFrequency
     * @param int $channel
     */
    public function generatePlayerImage(
        string $destinationFilePath,
        string $wavFilePath,
        int $maxFrequency,
        int $channel = 0,
        int $minFrequency = 1
    )
    {
        $command = ABSOLUTE_DIR . 'bin/svt.py ';
        if ($channel > 0) {
            $command .= "-c $channel ";
        }
        $command .= "-s $destinationFilePath ";
        $command .= '-w 870 -h 400 ';
        $command .= "-m $maxFrequency ";
        $command .= "-i $minFrequency ";
        $command .= '-f ' . $this->fftSize . ' ' . $wavFilePath;
        (new Process($command))->mustRun();
    }

    /**
     * @param $samplingRate
     * @param $freqMin
     * @param $freqMax
     * @param $timeMin
     * @param $timeMax
     * @param $wavFilePath
     * @param $channel
     * @param $duration
     * @param $destinationDirectory
     * @return string
     */
    public function generateViewPort(
        $samplingRate,
        $freqMin,
        $freqMax,
        $timeMin,
        $timeMax,
        $wavFilePath,
        $channel,
        $duration,
        $destinationDirectory
    )
    {
        $spectrogramWidth = WINDOW_WIDTH - (SPECTROGRAM_LEFT + SPECTROGRAM_RIGHT);
        $viewPortHeight = round((SPECTROGRAM_HEIGHT / $spectrogramWidth) * $this::VIEWPORT_WIDTH);
        $nyQuist = round($samplingRate / 2);

        // Red rectangle for current selection
        $selectionRectangleLow = round($viewPortHeight - ((($freqMin - 10) / $nyQuist) * $viewPortHeight));
        $selectionRectangleHigh = round((($nyQuist - $freqMax) / $nyQuist) * $viewPortHeight);
        $selectionRectangleLeft = round(($timeMin / $duration) * $this::VIEWPORT_WIDTH);
        $selectionRectangleRight = round(($timeMax / $duration) * $this::VIEWPORT_WIDTH);

        // $fileName = explode(".", substr($wavFilePath, strrpos($wavFilePath, "/") + 1, strlen($wavFilePath)));
        $viewPortFilePath = $destinationDirectory . pathinfo($wavFilePath)['filename'] . "_" . $selectionRectangleLow;
        $viewPortFilePath .= '_' . $selectionRectangleHigh . "_" . $selectionRectangleLeft . "_";
        $viewPortFilePath .= $selectionRectangleRight . "_" . $channel . ".png";

        if (!file_exists($viewPortFilePath)) {
            $command = 'bin/svt.py -s ' . $viewPortFilePath . ' -w ' . $this::VIEWPORT_WIDTH;
            $command .= ' -h ' . $viewPortHeight . ' -m ' . $nyQuist . ' -f ' . $this->fftSize;
            $command .= ' -c ' . $channel . ' ' . $wavFilePath;
            Utils::executeCommand($command);

            $command = 'convert -stroke red -fill none -draw "rectangle ' . $selectionRectangleLeft . ',';
            $command .= $selectionRectangleHigh . ' ' . $selectionRectangleRight . ',' . $selectionRectangleLow . '" ';
            $command .= $viewPortFilePath . ' ' . $viewPortFilePath;
            Utils::executeCommand($command);
        }
        return $viewPortFilePath;
    }
}
