<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\IndexLogProvider;
use BioSounds\Utils\Auth;

class IndexLogController extends BaseController
{
    const SECTION_TITLE = 'IndexLogs';

    /**
     * @return string
     * @throws \Exception
     */
    public function show()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }
        $indexLogProvider = new IndexLogProvider();
        return $this->twig->render('administration/indexLogs.html.twig', [
            'indexLogs' => $indexLogProvider->getList(),
        ]);
    }

    /**
     * @throws \Exception
     */
    public function export()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }

        $file_name = "indexLogs.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);

        $indexLogList = (new IndexLogProvider())->getList();
        $indexLogAls[] = array('#', 'Recording', 'User', 'Index', 'Time Start', 'Time End', 'Min Frequency', 'Max Frequency', 'Parameter', 'Result', 'Creation Date (UTC)');

        foreach ($indexLogList as $indexLogItem) {
            $value = '';
            foreach (explode('!', $indexLogItem->getValue()) as $v) {
                $value = $value . explode('?', $v)[0] . ': ' . number_format(explode('?', $v)[1], 2, '.', ',') . ' ';
            }
            $indexLogArray = array(
                $indexLogItem->getLogId(),
                $indexLogItem->getRecordingName(),
                $indexLogItem->getUserName(),
                $indexLogItem->getIndexName(),
                $indexLogItem->getMinTime(),
                $indexLogItem->getMaxTime(),
                $indexLogItem->getMinFrequency(),
                $indexLogItem->getMaxFrequency(),
                str_replace('@', ' ', str_replace('?', ': ', $indexLogItem->getParam())),
                $value,
                $indexLogItem->getDate(),
            );
            $indexLogAls[] = $indexLogArray;
        }

        foreach ($indexLogAls as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
        exit();
    }
}
