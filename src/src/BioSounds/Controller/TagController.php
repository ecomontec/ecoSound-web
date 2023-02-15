<?php

namespace BioSounds\Controller;

use BioSounds\Entity\Tag;
use BioSounds\Entity\Permission;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Exception\NotAuthenticatedException;
use BioSounds\Provider\SoundProvider;
use BioSounds\Provider\SoundTypeProvider;
use BioSounds\Provider\TagProvider;
use BioSounds\Utils\Auth;

class TagController extends BaseController
{
    /**
     * @param int $tagId
     * @return string
     * @throws \Exception
     */
    public function showCallDistance(int $tagId)
    {
        if (!Auth::isUserLogged()) {
            throw new NotAuthenticatedException();
        }

        return json_encode([
            'errorCode' => 0,
            'data' => $this->twig->render('tag/callEstimation.html.twig', [
                'tagId' => $tagId,
            ]),
        ]);
    }

    /**
     * @return false|string
     * @throws \Exception
     */
    public function create()
    {
        if (!Auth::isUserLogged()) {
            throw new NotAuthenticatedException();
        }

        if (!isset($_POST["t_min"]) || !isset($_POST["t_max"]) || !isset($_POST["f_min"]) || !isset($_POST["f_max"])) {
            throw new \Exception('Data not set.');
        }

        $tag = (new Tag())
            ->setRecording(filter_var($_POST["recording_id"], FILTER_SANITIZE_NUMBER_INT))
            ->setMinTime(filter_var($_POST["t_min"], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION))
            ->setMaxTime(filter_var($_POST["t_max"], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION))
            ->setMinFrequency(filter_var($_POST["f_min"], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION))
            ->setMaxFrequency(filter_var($_POST["f_max"], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION))
            ->setUserName(Auth::getUserName())
            ->setUser(Auth::getUserLoggedID());
        return json_encode([
            'errorCode' => 0,
            'data' => $this->twig->render('tag/tag.html.twig', [
                'tag' => $tag,
                'displayDeleteButton' => 'hidden',
                'recordingName' => isset($_POST['recording_name']) ? $_POST['recording_name'] : null,
                'phonys' => (new SoundProvider())->get(),
                'soundTypes' => (new SoundProvider())->getAll(),
                'edit' => 0,
            ]),
        ]);
    }

    /**
     * @param int $tagId
     * @return false|string
     * @throws \Exception
     */
    public function edit(int $tagId)
    {
        $tag = (new TagProvider())->get($tagId);
        if (empty($tagId)) {
            throw new \Exception(ERROR_EMPTY_ID);
        }
        if (!Auth::isManage() && !$tag->getPublicTags() && (!isset($_SESSION["user_col_permission"]) || empty($_SESSION["user_col_permission"]))) {
            throw new ForbiddenException();
        }
        /* TAG USER CONTROL */

        $isUserTagOwner = $tag->getUser() == Auth::getUserLoggedID();
        $isReviewGranted = Auth::isUserLogged();
        $displaySaveButton = Auth::isManage() || $isUserTagOwner ? '' : 'hidden';

        if (!Auth::isManage() && !$isUserTagOwner) {
            $permissionProvider = new Permission();
            $isReviewGranted = $permissionProvider->isReviewPermission($_SESSION["user_col_permission"]);
            $isViewGranted = $permissionProvider->isViewPermission($_SESSION["user_col_permission"]);
            $isManageGranted = $permissionProvider->isManagePermission($_SESSION["user_col_permission"]);
            $displaySaveButton = $isReviewGranted || $isManageGranted ? '' : 'hidden';
        }
        /**********************/
        $tagProvider = new TagProvider();
        if (Auth::isUserAdmin() || $isReviewGranted || $isViewGranted || $isManageGranted) {
            $tags = $tagProvider->getList($tag->getRecording());
        } else {
            $tags = $tagProvider->getList($tag->getRecording(), Auth::getUserLoggedID());
        }
        foreach ($tags as $k => $t) {
            $max = count($tags) - 1;
            if ($t->getId() == $tag->getId()) {
                if ($k == 0) {
                    $previous = 0;
                    $next = $tags[$k + 1];
                } elseif ($k == $max) {
                    $previous = $tags[$k - 1];
                    $next = 0;
                } else {
                    $previous = $tags[$k - 1];
                    $next = $tags[$k + 1];
                }
            }
        }
        return json_encode([
            'errorCode' => 0,
            'data' => $this->twig->render('tag/tag.html.twig', [
                'tag' => $tag,
                'recordingName' => isset($_POST['recording_name']) ? $_POST['recording_name'] : null,
                'displayDeleteButton' => Auth::isManage() || $isUserTagOwner ? '' : 'hidden',
                'displaySaveButton' => $displaySaveButton,
                'disableTagForm' => !Auth::isManage() && !$isUserTagOwner,
                'reviewPanel' => (new TagReviewController($this->twig))->show($tagId, $isReviewGranted || $isManageGranted),
                'animalSoundTypes' => (new SoundTypeProvider())->getList($tag->getTaxonClass(), $tag->getTaxonOrder()),
                'phonys' => (new SoundProvider())->get(),
                'soundTypes' => (new SoundProvider())->getAll(),
                'edit' => 1,
                'previous' => $previous,
                'next' => $next,
            ]),
        ]);
    }

    /**
     * @return false|string
     * @throws \Exception
     */
    public function save()
    {
        if (!Auth::isUserLogged()) {
            throw new NotAuthenticatedException();
        }

        $data[Tag::UNCERTAIN] = 0;
        $data[Tag::REFERENCE_CALL] = 0;
        $data[Tag::DISTANCE_NOT_ESTIMABLE] = 0;

        foreach ($_POST as $key => $value) {
            $data[$key] = htmlentities(strip_tags($value), ENT_QUOTES);

            if ($key === Tag::CALL_DISTANCE && empty($value)) {
                $data[$key] = null;
            }
        }
        if ($data['species_id'] == '') {
            $data[Tag::SPECIES_ID] = null;
        }
        if ($data['phony'] != "biophony") {
            $data['species_id'] = null;
            $data['uncertain'] = null;
            $data['animal_sound_type'] = null;
            $data['distance_not_estimable'] = null;
            $data['sound_distance_m'] = null;
        }
        unset($data[Tag::PHONY]);
        if (isset($data[Tag::ID]) && !empty($data[Tag::ID])) {
            (new TagProvider())->update($data);
            return json_encode([
                'errorCode' => 0,
                'message' => 'Tag updated successfully.',
            ]);
        }

        $data[Tag::USER_ID] = Auth::getUserLoggedID();
        if ($data[Tag::DISTANCE_NOT_ESTIMABLE] != 1) {
            $data[Tag::DISTANCE_NOT_ESTIMABLE] = null;
        }

        unset($data[Tag::ID]);

        return json_encode([
            'errorCode' => 0,
            'message' => 'Tag created successfully.',
            'tagId' => (new TagProvider())->insert($data),
        ]);
    }

    /**
     * @param int $tagId
     * @return array|int
     * @throws \Exception
     */
    public function delete(int $tagId)
    {
        if (!Auth::isUserAdmin() && (new TagProvider())->get($tagId)->getUser() != Auth::getUserLoggedID()) {
            throw new \Exception('The user doesn\'t have permissions to delete this tag.');
        }

        (new TagProvider())->delete($tagId);

        return json_encode([
            'errorCode' => 0,
            'message' => 'Tag deleted successfully.',
        ]);
    }
}
