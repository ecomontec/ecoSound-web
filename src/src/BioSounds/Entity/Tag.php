<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

class Tag extends BaseProvider
{
    const ID = "tag_id";
    const SPECIES_ID = "species_id";
    const RECORDING_ID = "recording_id";
    const USER_ID = "user_id";
    const MIN_TIME = "min_time";
    const MAX_TIME = "max_time";
    const MIN_FREQ = "min_freq";
    const MAX_FREQ = "max_freq";
    const UNCERTAIN = "uncertain";
    const REFERENCE_CALL = "reference_call";
    const CALL_DISTANCE = "sound_distance_m";
    const DISTANCE_NOT_ESTIMABLE = "distance_not_estimable";
    const NUMBER_INDIVIDUALS = "individuals";
    const COMMENTS = "comments";
    const TYPE = 'animal_sound_type';
    const SOUNDSCAPE_COMPONENT = 'soundscape_component';
    const SOUND_ID = 'sound_id';

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $species;

    /**
     * @var integer
     */
    private $recording;

    /**
     * @var integer
     */
    private $user;

    /**
     * @var float
     */
    private $minTime;

    /**
     * @var float
     */
    private $maxTime;

    /**
     * @var float
     */
    private $minFrequency;

    /**
     * @var float
     */
    private $maxFrequency;

    /**
     * @var bool
     */
    private $uncertain = false;

    /**
     * @var bool
     */
    private $referenceCall = false;

    /**
     * @var integer
     */
    private $callDistance;

    /**
     * @var bool
     */
    private $distanceNotEstimable = false;

    /**
     * @var integer
     */
    private $numberIndividuals = 1;

    /**
     * @var string
     */
    private $comments;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int
     */
    private $sound_id;

    /**
     * @var integer
     */
    private $reviewNumber;

    /**
     * @var string
     * //TODO: Remove variable when species entity is added
     */
    private $speciesName;

    /**
     * @var string
     * //TODO: Remove variable when user entity is added
     */
    private $userName;
    private $creator_type;
    private $public_tags;
    private $confidence;
    private $type_id;
    private $species_id;
    private $recordingName;
    private $time;
    private $frequency;
    private $creationDate;
    private $taxon_class;
    private $taxon_order;
    private $soundscape_component;
    private $sound_type;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return Tag
     */
    public function setId(?int $id): Tag
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSpecies(): ?int
    {
        return $this->species;
    }

    /**
     * @param int|null $species
     * @return Tag
     */
    public function setSpecies(?int $species): Tag
    {
        $this->species = $species;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSoundId(): ?int
    {
        return $this->sound_id;
    }

    /**
     * @param int|null $sound_id
     * @return Tag
     */
    public function setSoundId(?int $sound_id): Tag
    {
        $this->sound_id = $sound_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getRecording(): int
    {
        return $this->recording;
    }

    /**
     * @param int $recording
     * @return Tag
     */
    public function setRecording(int $recording): Tag
    {
        $this->recording = $recording;
        return $this;
    }

    /**
     * @return int
     */
    public function getUser(): int
    {
        return $this->user;
    }

    /**
     * @param int $user
     * @return Tag
     */
    public function setUser(int $user): Tag
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return float
     */
    public function getMinTime(): float
    {
        return $this->minTime;
    }

    /**
     * @param float $minTime
     * @return Tag
     */
    public function setMinTime(float $minTime): Tag
    {
        $this->minTime = $minTime;
        return $this;
    }

    /**
     * @return float
     */
    public function getMaxTime(): float
    {
        return $this->maxTime;
    }

    /**
     * @param float $maxTime
     * @return Tag
     */
    public function setMaxTime(float $maxTime): Tag
    {
        $this->maxTime = $maxTime;
        return $this;
    }

    /**
     * @return float
     */
    public function getMinFrequency(): float
    {
        return $this->minFrequency;
    }

    /**
     * @param float $minFrequency
     * @return Tag
     */
    public function setMinFrequency(float $minFrequency): Tag
    {
        $this->minFrequency = $minFrequency;
        return $this;
    }

    /**
     * @return float
     */
    public function getMaxFrequency(): float
    {
        return $this->maxFrequency;
    }

    /**
     * @param float $maxFrequency
     * @return Tag
     */
    public function setMaxFrequency(float $maxFrequency): Tag
    {
        $this->maxFrequency = $maxFrequency;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUncertain(): bool
    {
        return $this->uncertain;
    }

    /**
     * @param bool $uncertain
     * @return Tag
     */
    public function setUncertain(bool $uncertain): Tag
    {
        $this->uncertain = $uncertain;
        return $this;
    }

    /**
     * @return bool
     */
    public function isReferenceCall(): bool
    {
        return $this->referenceCall;
    }

    /**
     * @param bool|null $referenceCall
     * @return Tag
     */
    public function setReferenceCall(?bool $referenceCall): Tag
    {
        $this->referenceCall = $referenceCall;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCallDistance(): ?int
    {
        return $this->callDistance;
    }

    /**
     * @param int|null $callDistance
     * @return Tag
     */
    public function setCallDistance(?int $callDistance): Tag
    {
        $this->callDistance = $callDistance;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDistanceNotEstimable(): bool
    {
        return $this->distanceNotEstimable;
    }

    /**
     * @param bool $distanceNotEstimable
     * @return Tag
     */
    public function setDistanceNotEstimable(bool $distanceNotEstimable): Tag
    {
        $this->distanceNotEstimable = $distanceNotEstimable;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getNumberIndividuals(): ?int
    {
        return $this->numberIndividuals;
    }

    /**
     * @param int|null $numberIndividuals
     * @return Tag
     */
    public function setNumberIndividuals(?int $numberIndividuals): Tag
    {
        $this->numberIndividuals = $numberIndividuals;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getComments(): ?string
    {
        return $this->comments;
    }

    /**
     * @param string|null $comments
     * @return Tag
     */
    public function setComments(?string $comments): Tag
    {
        $this->comments = $comments;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getTypeId(): ?int
    {
        return $this->type_id;
    }

    /**
     * @param int|null $type_id
     * @return Tag
     */
    public function setTypeId(?int $type_id): Tag
    {
        $this->type_id = $type_id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     * @return Tag
     */
    public function setType(?string $type): Tag
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getReviewNumber(): ?int
    {
        return $this->reviewNumber;
    }

    /**
     * @param int|null $reviewNumber
     * @return Tag
     */
    public function setReviewNumber(?int $reviewNumber): Tag
    {
        $this->reviewNumber = $reviewNumber;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSpeciesId(): ?int
    {
        return $this->species_id;
    }

    /**
     * @param int|null $species_id
     * @return Tag
     */
    public function setSpeciesId(?int $species_id): Tag
    {
        $this->species_id = $species_id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSpeciesName(): ?string
    {
        return $this->speciesName;
    }

    /**
     * @param string|null $speciesName
     * @return Tag
     */
    public function setSpeciesName(?string $speciesName): Tag
    {
        $this->speciesName = $speciesName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRecordingName(): ?string
    {
        return $this->recordingName;
    }

    /**
     * @param string|null $recordingName
     * @return Tag
     */
    public function setRecordingName(?string $recordingName): Tag
    {
        $this->recordingName = $recordingName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUserName(): ?string
    {
        return $this->userName;
    }

    /**
     * @param string|null $userName
     * @return Tag
     */
    public function setUserName(?string $userName): Tag
    {
        $this->userName = $userName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTime(): ?string
    {
        return $this->time;
    }

    /**
     * @param string|null $time
     * @return Tag
     */
    public function setTime(?string $time): Tag
    {
        $this->time = $time;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    /**
     * @param string|null $frequency
     * @return Tag
     */
    public function setFrequency(?string $frequency): Tag
    {
        $this->frequency = $frequency;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCreationDate(): ?string
    {
        return $this->creationDate;
    }

    /**
     * @param string|null $creationDate
     * @return Tag
     */
    public function setCreationDate(?string $creationDate): Tag
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTaxonOrder(): ?string
    {
        return $this->taxon_order ? $this->taxon_order : '';
    }

    /**
     * @param string|null $taxon_order
     * @return Tag
     */
    public function setTaxonOrder(?string $taxon_order): Tag
    {
        $this->taxon_order = $taxon_order;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTaxonClass(): ?string
    {
        return $this->taxon_class ? $this->taxon_class : '';
    }

    /**
     * @param string|null $taxon_class
     * @return Tag
     */
    public function setTaxonClass(?string $taxon_class): Tag
    {
        $this->taxon_class = $taxon_class;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSoundscapeComponent(): ?string
    {
        return $this->soundscape_component;
    }

    /**
     * @param string|null $soundscape_component
     * @return Tag
     */
    public function setSoundscapeComponent(?string $soundscape_component): Tag
    {
        $this->soundscape_component = $soundscape_component;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSoundType(): ?string
    {
        return $this->sound_type;
    }

    /**
     * @param string|null $sound_type
     * @return Tag
     */
    public function setSoundType(?string $sound_type): Tag
    {
        $this->sound_type = $sound_type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCreatorType(): ?string
    {
        return $this->creator_type;
    }

    /**
     * @param string|null $creator_type
     * @return Tag
     */
    public function setCreatorType(?string $creator_type): Tag
    {
        $this->creator_type = $creator_type;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPublicTags(): ?int
    {
        return $this->public_tags;
    }

    /**
     * @param int|null $public_tags
     * @return Tag
     */
    public function setPublicTags(?int $public_tags): Tag
    {
        $this->public_tags = $public_tags;
        return $this;
    }

    /**
     * @return float
     */
    public function getConfidence(): ?float
    {
        return $this->confidence;
    }

    /**
     * @param float $confidence
     * @return Tag
     */
    public function setConfidence(?float $confidence): Tag
    {
        $this->confidence = $confidence;
        return $this;
    }

    /**
     * @param array $values
     * @return $this
     */
    public function createFromValues(array $values)
    {
        $this->setId($values['tag_id']);
        $this->setRecording($values['recording_id']);
        $this->setSpecies(isset($values['species_id']) ? $values['species_id'] : null);
        $this->setUser($values['user_id']);
        $this->setMinTime($values['min_time']);
        $this->setMaxTime($values['max_time']);
        $this->setMinFrequency($values['min_freq']);
        $this->setMaxFrequency($values['max_freq']);
        $this->setUncertain(isset($values['uncertain']) ? $values['uncertain'] : 0);
        $this->setCallDistance($values['sound_distance_m']);
        $this->setReferenceCall(isset($values['reference_call']) ? $values['reference_call'] : null);
        $this->setDistanceNotEstimable($values['distance_not_estimable'] ?: false);
        $this->setNumberIndividuals(isset($values['individuals']) ? $values['individuals'] : null);
        $this->setComments(isset($values['individuals']) ? $values['comments'] : null);
        $this->setType(isset($values['animal_sound_type']) ? $values['animal_sound_type'] : null);
        $this->setReviewNumber(isset($values['review_number']) ? $values['review_number'] : null);
        $this->setSpeciesName($values['species_name']);
        $this->setUserName(isset($values['name']) ? $values['name'] : null);
        $this->setCreationDate($values['creation_date']);
        $this->setTaxonOrder(isset($values['taxon_order']) ? $values['taxon_order'] : null);
        $this->setTaxonClass(isset($values['class']) ? $values['class'] : null);
        $this->setSoundId(isset($values['sound_id']) ? $values['sound_id'] : null);
        $this->setSoundscapeComponent(isset($values['soundscape_component']) ? $values['soundscape_component'] : null);
        $this->setSoundType(isset($values['sound_type']) ? $values['sound_type'] : null);
        $this->setPublicTags($values['public_tags']);
        $this->setCreatorType(isset($values['creator_type']) ? $values['creator_type'] : null);
        $this->setConfidence(isset($values['confidence']) ? $values['confidence'] : null);
        return $this;
    }
}
