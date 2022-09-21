<?php

namespace BioSounds\Entity;


class SoundType
{
    /**
     * @var int
     */
    private $soundTypeId;

    /**
     * @var string
     */
    private $name;

    /**
     * @return int
     */
    public function getSoundTypeId(): int
    {
        return $this->soundTypeId;
    }

    /**
     * @param int $soundTypeId
     * @return SoundType
     */
    public function setSoundTypeId(int $soundTypeId): SoundType
    {
        $this->soundTypeId = $soundTypeId;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return SoundType
     */
    public function setName(string $name): SoundType
    {
        $this->name = $name;
        return $this;
    }
    /**
     * @return string
     */
    public function getTaxonOrder(): string
    {
        return $this->taxon_order;
    }

    /**
     * @param string $taxon_order
     * @return SoundType
     */
    public function setTaxonOrder(string $taxon_order): SoundType
    {
        $this->taxon_order = $taxon_order;
        return $this;
    }
    /**
     * @return string
     */
    public function getTaxonClass(): string
    {
        return $this->taxon_class;
    }

    /**
     * @param string $taxon_class
     * @return SoundType
     */
    public function setTaxonClass(string $taxon_class): SoundType
    {
        $this->taxon_class = $taxon_class;
        return $this;
    }
}