<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\TagProvider;
use BioSounds\Utils\Auth;

class TagController extends BaseController
{
    const SECTION_TITLE = 'Tags';

    /**
     * @return string
     * @throws \Exception
     */
    public function show()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }
        $tagProvider = new TagProvider();
        return $this->twig->render('administration/tags.html.twig', [
            'tags' => $tagProvider->getTagPages(),
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

        $file_name = "tags.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);

        $tagList = (new TagProvider())->getListByTags();
        $tagAls[] = array('#', 'Species', 'Recording', 'User', 'Time Start', 'Time End', 'Min Frequency', 'Max Frequency', 'Uncertain', 'Call Distance', 'Distance Not Estimable', 'Number of Individuals', 'Type', 'Reference Call', 'Comments', 'Creation Date(UTC)');
        foreach ($tagList as $tagItem) {
            $tagArray = array(
                $tagItem->getId(),
                $tagItem->getSpeciesName(),
                $tagItem->getRecordingName(),
                $tagItem->getUserName(),
                $tagItem->getMinTime(),
                $tagItem->getMaxTime(),
                $tagItem->getMinFrequency(),
                $tagItem->getMaxFrequency(),
                $tagItem->isUncertain(),
                $tagItem->getCallDistance(),
                $tagItem->isDistanceNotEstimable(),
                $tagItem->getNumberIndividuals(),
                $tagItem->getType(),
                $tagItem->isReferenceCall(),
                $tagItem->getComments(),
                $tagItem->getCreationDate(),
            );
            $tagAls[] = $tagArray;
        }

        foreach ($tagAls as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
        exit();
    }
}
