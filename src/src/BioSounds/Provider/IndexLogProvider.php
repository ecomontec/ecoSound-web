<?php

namespace BioSounds\Provider;

use BioSounds\Entity\IndexLog;
use BioSounds\Utils\Auth;

class IndexLogProvider extends BaseProvider
{
    /**
     * @return array
     * @throws \Exception
     */
    public function getList(): array
    {
        $list = [];

        $sql = "SELECT i.*,r.`name` AS recordingName,u.`name` AS userName,it.`name` AS indexName FROM index_log i 
            LEFT JOIN recording r ON r.recording_id = i.recording_id
            LEFT JOIN user u ON u.user_id = i.user_id
            LEFT JOIN index_type it ON it.index_id = i.index_id ";
        if (!Auth::isUserAdmin()) {
            $sql = $sql . ' WHERE i.user_id = ' . Auth::getUserLoggedID();
        }
        $sql = $sql . " ORDER BY i.log_id";
        $this->database->prepareQuery($sql);
        if (!empty($result = $this->database->executeSelect())) {
            foreach ($result as $indexLog) {
                $list[] = (new IndexLog())
                    ->setLogId($indexLog['log_id'])
                    ->setRecordingId($indexLog['recording_id'])
                    ->setRecordingName($indexLog['recordingName'])
                    ->setUserId($indexLog['user_id'])
                    ->setUserName($indexLog['userName'])
                    ->setIndexId($indexLog['index_id'])
                    ->setIndexName($indexLog['indexName'])
                    ->setMinTime($indexLog['minTime'])
                    ->setMaxTime($indexLog['maxTime'])
                    ->setMinFrequency($indexLog['minFrequency'])
                    ->setMaxFrequency($indexLog['maxFrequency'])
                    ->setValue($indexLog['value'])
                    ->setParam($indexLog['param'])
                    ->setDate($indexLog['creation_date']);
            }
        }
        return $list;
    }
}
