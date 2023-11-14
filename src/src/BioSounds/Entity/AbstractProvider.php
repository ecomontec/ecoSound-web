<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

abstract class AbstractProvider extends BaseProvider
{
    const TABLE_NAME = '';
    const PRIMARY_KEY = '';
    const NAME = '';

    /**
     * @return array|int
     * @throws \Exception
     */
    public function getBasicList()
    {
        $this->database->prepareQuery(
            'SELECT ' . static::PRIMARY_KEY . ', ' . static::NAME . ' FROM ' . static::TABLE_NAME . ' ORDER BY ' . static::NAME
        );
        return $this->database->executeSelect();
    }

    /**
     * @return array|int
     * @throws \Exception
     */
    public function getColumns()
    {
        $sql = "SELECT column_name FROM information_schema.columns WHERE table_name = '" . static::TABLE_NAME . "' ORDER BY ordinal_position";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }
}
