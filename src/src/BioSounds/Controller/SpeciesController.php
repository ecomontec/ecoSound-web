<?php

namespace BioSounds\Controller;

use BioSounds\Entity\SoundType;
use BioSounds\Entity\Species;
use BioSounds\Provider\SoundTypeProvider;

class SpeciesController
{
    /**
     * @return string
     * @throws \Exception
     */
    public function getList(): string
    {
        $data = [];

        $terms = isset($_POST['term']) ? $_POST['term'] : null;

        if (!empty($terms)) {
            $words = preg_split("/[\s,]+/", $terms);

            $animal = new Species;
            $result = $animal->getList($words);

            if (!empty($result)) {
                foreach ($result as $row) {
                    $data[] = [
                        'label' => $row[Species::BINOMIAL] . ' ( ' . $row[Species::NAME] . ' ) ',
                        'value' => $row[Species::ID],
                        'taxon_order' => $row['taxon_order'],
                        'class' => $row['class']
                    ];
                }
            }
        }
        return json_encode($data);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getSoundType(): string
    {
        $data = [];
        $taxon_class = $_POST['taxon_class'];
        $taxon_order = $_POST['taxon_order'];
        $soundTypeProvider = new SoundTypeProvider;
        $result = $soundTypeProvider->getList($taxon_class, $taxon_order);
        if (!empty($result)) {
            foreach ($result as $row) {
                $data[] = [
                    'sound_type_id' => $row->getSoundTypeId(),
                    'name' => $row->getName(),
                ];
            }
        }else{
            return '';
        }
        return json_encode($data);
    }
}
