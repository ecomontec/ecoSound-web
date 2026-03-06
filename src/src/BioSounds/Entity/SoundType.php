<?php

namespace BioSounds\Entity;

class SoundType
{
    private $sound_type_id;
    private $name;
    private $taxon_class;
    private $taxon_order;

    public function getSoundTypeId()
    {
        return $this->sound_type_id;
    }

    public function setSoundTypeId($sound_type_id)
    {
        $this->sound_type_id = $sound_type_id;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getTaxonClass()
    {
        return $this->taxon_class;
    }

    public function setTaxonClass($taxon_class)
    {
        $this->taxon_class = $taxon_class;
        return $this;
    }

    public function getTaxonOrder()
    {
        return $this->taxon_order;
    }

    public function setTaxonOrder($taxon_order)
    {
        $this->taxon_order = $taxon_order;
        return $this;
    }
}