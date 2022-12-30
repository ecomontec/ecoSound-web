<?php

namespace BioSounds\Controller;

use BioSounds\Entity\UserPermission;
use BioSounds\Entity\User;
use BioSounds\Entity\Permission;
use BioSounds\Provider\ProjectProvider;
use BioSounds\Utils\Auth;

class UserPermissionController extends BaseController
{
    /**
     * @param int $userId
     * @return false|string
     * @throws \Exception
     */
    public function show(int $userId)
    {
        $listProjects = (new ProjectProvider())->getWithPermission($userId);
        $permission = new Permission();
        return json_encode([
            'errorCode' => 0,
            'data' => $this->twig->render('administration/userPermission.html.twig', [
                'projects' => $listProjects,
                'username' => (new User())->getFullName($userId),
                'userId' => $userId,
                'viewId' => $permission->getViewId(),
                'reviewId' => $permission->getReviewId(),
                'accessId' => $permission->getAccessId(),
                'manageId' => $permission->getManageId(),
            ]),
        ]);
    }

    /**
     * @param int $userId
     * @return false|string
     * @throws \Exception
     */
    public function view(int $userId)
    {
        $listProjects = (new ProjectProvider())->getWithPermission($userId, 0);
        $permission = new Permission();

        return json_encode([
            'errorCode' => 0,
            'data' => $this->twig->render('administration/userPermissionView.html.twig', [
                'projects' => $listProjects,
                'username' => (new User())->getFullName($userId),
                'userId' => $userId,
                'viewId' => $permission->getViewId(),
                'reviewId' => $permission->getReviewId(),
                'accessId' => $permission->getAccessId(),
                'manageId' => $permission->getManageId(),
            ]),
        ]);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function save(): string
    {
        $userProvider = new UserPermission();
        foreach ($_POST['data'] as $row) {
            if (isset($row['collection_id'])) {
                $userProvider->delete($row['user_id'], $row['collection_id']);
                if ($row['permission_id'] > 0) {
                    $userProvider->insert($row);
                }
            }
        }
        return json_encode([
            'errorCode' => 0,
            'message' => 'successfully changed user permissions',
        ]);
    }
}
