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
    public function show()
    {
        $userId = explode(',', $_POST['id']);
        if (count($userId) == 1) {
            $listProjects = (new ProjectProvider())->getWithPermission($userId[0]);
        } else {
            $listProjects = (new ProjectProvider())->getWithPermission();
        }
        $permission = new Permission();
        return json_encode([
            'errorCode' => 0,
            'data' => $this->twig->render('administration/userPermission.html.twig', [
                'projects' => $listProjects,
                'username' => count($userId) == 1 ? (new User())->getFullName($userId[0]) : '',
                'userId' => $_POST['id'],
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
    public function view()
    {
        $userId = $_POST['id'];
        $listProjects = (new ProjectProvider())->getWithPermission($userId, 0);
        $permission = new Permission();
        return json_encode([
            'errorCode' => 0,
            'data' => $this->twig->render('administration/userPermissionView.html.twig', [
                'projects' => $listProjects,
                'username' => (new User())->getFullName($userId),
                'userId' => $_POST['id'],
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
        $delete = implode(',', $_POST['c']);
        $userProvider->delete($_POST['user_id'], $delete);
        foreach ($_POST['c'] as $key => $value) {
            if ($key != 0) {
                $row['user_id'] = $_POST['user_id'];
                $row['collection_id'] = $value;
                $row['permission_id'] = $key;
                $userProvider->insert($row);
            }
        }
        return json_encode([
            'errorCode' => 0,
            'message' => 'successfully changed user permissions',
        ]);
    }
}
