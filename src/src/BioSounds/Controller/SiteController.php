<?php

namespace BioSounds\Controller;

use BioSounds\Entity\Site;

class SiteController
{
    /**
     * @return string
     * @throws \Exception
     */
    public function getList(): string
    {
        $data = [];

        $term = isset($_POST['term']) ? $_POST['term'] : null;

        if (!empty($term)) {

            $site = new Site();
            $result = $site->getList($term);

            if (!empty($result)) {
                $data = [];
                foreach ($result as $row) {
                    $data[] = [
                        'label' => $row[Site::NAME],
                        'value' => $row[Site::PRIMARY_KEY]
                    ];
                }
            }
        }
        return json_encode($data);
    }
}
