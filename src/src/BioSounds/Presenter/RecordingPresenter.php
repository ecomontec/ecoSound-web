<?php

namespace BioSounds\Presenter;

/**
 * Class RecordingPresenter
 * @package BioSounds\Presenter
 */
class RecordingPresenter
{
    /**
     * @var bool
     */
    private $showTags = true;

    /**
     * @var bool
     */
    private $continuousPlay = false;

    /**
     * @var bool
     */
    private $filter = false;

    /**
     * @var array
     */
    private $recording = [];

    /**
     * @var int|null
     */
    private $estimateDistID;

    /**
     * @var int
     */
    private $channel;

    /**
     * @var int
     */
    private $spectrogramHeight;

    /**
     * @var int
     */
    private $spectrogramWidth;

    /**
     * @var array
     */
    private $tags = [];

    /**
     * @var FrequencyScalePresenter
     */
    private $frequencyScaleData;

    private $minTime;

    private $maxTime;

    private $minFrequency;

    private $maxFrequency;

    private $duration;

    private $fileFreqMax;

    private $filePath;

    private $imageFilePath;

    private $user;

    private $viewPortFilePath;

    /**
     * @var array
     */
    private $timeScaleSeconds = [];

    /**
     * @return bool
     */
    public function isShowTags(): bool
    {
        return $this->showTags;
    }

    /**
     * @param bool $showTags
     * @return RecordingPresenter
     */
    public function setShowTags(bool $showTags): RecordingPresenter
    {
        $this->showTags = $showTags;
        return $this;
    }

    /**
     * @return array
     */
    public function getRecording(): array
    {
        return $this->recording;
    }

    /**
     * @param array $recording
     * @return RecordingPresenter
     */
    public function setRecording(array $recording): RecordingPresenter
    {
        $this->recording = $recording;
        return $this;
    }

    /**
     * @return bool
     */
    public function isContinuousPlay(): bool
    {
        return $this->continuousPlay;
    }

    /**
     * @param bool $continuousPlay
     * @return RecordingPresenter
     */
    public function setContinuousPlay(bool $continuousPlay): RecordingPresenter
    {
        $this->continuousPlay = $continuousPlay;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFilter(): bool
    {
        return $this->filter;
    }

    /**
     * @param bool $filter
     * @return RecordingPresenter
     */
    public function setFilter(bool $filter): RecordingPresenter
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getEstimateDistID(): ?int
    {
        return $this->estimateDistID;
    }

    /**
     * @param int $estimateDistID
     * @return RecordingPresenter
     */
    public function setEstimateDistID(int $estimateDistID): RecordingPresenter
    {
        $this->estimateDistID = $estimateDistID;
        return $this;
    }

    /**
     * @return int
     */
    public function getChannel(): int
    {
        return $this->channel;
    }

    /**
     * @param int $channel
     * @return RecordingPresenter
     */
    public function setChannel(int $channel): RecordingPresenter
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * @return int
     */
    public function getSpectrogramHeight(): int
    {
        return $this->spectrogramHeight;
    }

    /**
     * @param int $spectrogramHeight
     * @return RecordingPresenter
     */
    public function setSpectrogramHeight(int $spectrogramHeight): RecordingPresenter
    {
        $this->spectrogramHeight = $spectrogramHeight;
        return $this;
    }

    /**
     * @return int
     */
    public function getSpectrogramWidth(): int
    {
        return $this->spectrogramWidth;
    }

    /**
     * @param int $spectrogramWidth
     * @return RecordingPresenter
     */
    public function setSpectrogramWidth(int $spectrogramWidth): RecordingPresenter
    {
        $this->spectrogramWidth = $spectrogramWidth;
        return $this;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     * @return RecordingPresenter
     */
    public function setTags(array $tags): RecordingPresenter
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * @return FrequencyScalePresenter
     */
    public function getFrequencyScaleData(): FrequencyScalePresenter
    {
        return $this->frequencyScaleData;
    }

    /**
     * @param FrequencyScalePresenter $frequencyScaleData
     * @return RecordingPresenter
     */
    public function setFrequencyScaleData(FrequencyScalePresenter $frequencyScaleData): RecordingPresenter
    {
        $this->frequencyScaleData = $frequencyScaleData;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMinTime()
    {
        return $this->minTime;
    }

    /**
     * @param mixed $minTime
     * @return RecordingPresenter
     */
    public function setMinTime($minTime)
    {
        $this->minTime = $minTime;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMaxTime()
    {
        return $this->maxTime;
    }

    /**
     * @param mixed $maxTime
     * @return RecordingPresenter
     */
    public function setMaxTime($maxTime)
    {
        $this->maxTime = $maxTime;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMinFrequency()
    {
        return $this->minFrequency;
    }

    /**
     * @param mixed $minFrequency
     * @return RecordingPresenter
     */
    public function setMinFrequency($minFrequency)
    {
        $this->minFrequency = $minFrequency;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMaxFrequency()
    {
        return $this->maxFrequency;
    }

    /**
     * @param mixed $maxFrequency
     * @return RecordingPresenter
     */
    public function setMaxFrequency($maxFrequency)
    {
        $this->maxFrequency = $maxFrequency;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param mixed $duration
     * @return RecordingPresenter
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFileFreqMax()
    {
        return $this->fileFreqMax;
    }

    /**
     * @param mixed $fileFreqMax
     * @return RecordingPresenter
     */
    public function setFileFreqMax($fileFreqMax)
    {
        $this->fileFreqMax = $fileFreqMax;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @param mixed $filePath
     * @return RecordingPresenter
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getImageFilePath()
    {
        return $this->imageFilePath;
    }

    /**
     * @param mixed $imageFilePath
     * @return RecordingPresenter
     */
    public function setImageFilePath($imageFilePath)
    {
        $this->imageFilePath = $imageFilePath;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     * @return RecordingPresenter
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getViewPortFilePath()
    {
        return $this->viewPortFilePath;
    }

    /**
     * @param mixed $viewPortFilePath
     * @return RecordingPresenter
     */
    public function setViewPortFilePath($viewPortFilePath)
    {
        $this->viewPortFilePath = $viewPortFilePath;
        return $this;
    }

    /**
     * @return array
     */
    public function getTimeScaleSeconds(): array
    {
        return $this->timeScaleSeconds;
    }

    /**
     * @param array $timeScaleSeconds
     * @return RecordingPresenter
     */
    public function setTimeScaleSeconds(array $timeScaleSeconds): RecordingPresenter
    {
        $this->timeScaleSeconds = $timeScaleSeconds;
        return $this;
    }

    /**
     * @param int $second
     * @return RecordingPresenter
     */
    public function addTimeScaleSecond(int $second): RecordingPresenter
    {
        $this->timeScaleSeconds[] = $second;
        return $this;
    }
}