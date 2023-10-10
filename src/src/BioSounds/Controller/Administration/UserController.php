<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\User;
use BioSounds\Entity\Role;
use BioSounds\Entity\UserPermission;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Utils\Auth;
use BioSounds\Utils\Utils;

class UserController extends BaseController
{
    const SECTION_TITLE = 'Users';
    const DEFAULT_TAG_COLOR = '#FFFFFF';

    /**
     * @return false|string
     * @throws \Exception
     */
    public function show()
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        $userProducer = new User();
        return $this->twig->render('administration/users.html.twig', [
            'roles' => (new Role())->getRoles(),
            'users' => $userProducer->getList(),
            'default_color' => self::DEFAULT_TAG_COLOR,
        ]);
    }

    /**
     * @return false|string
     * @throws \Exception
     */
    public function save()
    {
        $userProvider = new User();

        if (isset($_POST['admin_pwd'])) {
            $adminPwd = $_POST['admin_pwd'];
            $bdAdminPwd = $userProvider->getPasswordByUserId(Auth::getUserLoggedID());
            if (!Utils::checkPasswords($adminPwd, $bdAdminPwd)) {
                throw new \Exception('The administrator password is not correct.', 1);
            }
            unset($_POST['admin_pwd']);
        }

        $data = [];

        foreach ($_POST as $key => $value) {
            if (strrpos($key, '_')) {
                $type = substr($key, strrpos($key, '_') + 1, strlen($key));
                $key = substr($key, 0, strrpos($key, '_'));

                switch ($type) {
                    case 'email':
                        $data[$key] = filter_var($value, FILTER_SANITIZE_EMAIL);
                        break;
                    case 'checkbox':
                        $data[$key] = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                        break;
                    case 'select-one':
                        $data[$key] = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                        break;
                    case 'password':
                        $password = $value;
                        $data[$key] = Utils::encodePasswordHash($password);
                        break;
                    default:
                        $data[$key] = $value;
                        break;
                }
            } else {
                $data[$key] = $value;
            }
        }
        if($userProvider->isValid($data['username'])){
            return json_encode([
                'isValid' => 1,
                'message' => 'Username already exists.',
            ]);
        }
        if (isset($data['itemID'])) {
            $userProvider->updateUser($data);
            return json_encode([
                'errorCode' => 0,
                'message' => 'User updated successfully.'
            ]);
        } else if ($user_id = $userProvider->insertUser($data)) {
            if (!Auth::isUserAdmin()) {
                $userProvider = new UserPermission();
                $permission = (new User())->getManageList();
                foreach ($permission as $row) {
                    if (isset($row['collection_id'])) {
                        $row['user_id'] = $user_id;
                        $row['permission_id'] = 3;
                        $userProvider->insert($row);
                    }
                }
            }
            return json_encode([
                'errorCode' => 0,
                'message' => 'User created successfully.',
            ]);
        }
    }


    /**
     * @return false|string
     * @throws \Exception
     */
    public function resetSave()
    {
        $userProvider = new User();

        if (isset($_POST['my_pwd'])) {
            $myPwd = $_POST['my_pwd'];
            $bdMyPwd = $userProvider->getPasswordByUserId(Auth::getUserLoggedID());
            if (!Utils::checkPasswords($myPwd, $bdMyPwd)) {
                throw new \Exception('The old password is not correct.', 1);
            }
            unset($_POST['my_pwd']);
        }

        $data = [];

        foreach ($_POST as $key => $value) {
            if (strrpos($key, '_')) {
                $type = substr($key, strrpos($key, '_') + 1, strlen($key));
                $key = substr($key, 0, strrpos($key, '_'));

                switch ($type) {
                    case 'password':
                        $password = $value;
                        $data[$key] = Utils::encodePasswordHash($password);
                        break;
                    default:
                        $data[$key] = $value;
                        break;
                }
            } else {
                $data[$key] = $value;
            }
        }

        if (isset($data['sscID'])) {
            $userProvider->resetPasswd($data['sscID'], $data['password']);
            return json_encode([
                'errorCode' => 0,
                'message' => 'Password updated successfully.'
            ]);
        } else if (isset($data['itemID'])) {
            $userProvider->updateUser($data);
            return json_encode([
                'errorCode' => 0,
                'message' => 'Profile updated successfully.'
            ]);
        }
    }

    /**
     * @return false|string
     * @throws \Exception
     */
    public function editPassword()
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }

        $userId = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        return json_encode([
            'errorCode' => 0,
            'data' => $this->twig->render('administration/userPassword.html.twig', [
                'userId' => $userId,
            ]),
        ]);
    }

    /**
     * @return false|string
     * @throws \Exception
     */
    public function selfService(int $id = null)
    {
        return $this->twig->render('administration/userAdmin.html.twig', [
            'user' => (new User())->getMyProfile($id),
            'role' => (new Role())->getMyRole($id),
        ]);
    }

    /**
     * @return false|string
     * @throws \Exception
     */
    public function passwordReset()
    {
        $userId = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        return json_encode([
            'errorCode' => 0,
            'data' => $this->twig->render('administration/resetPassword.html.twig', [
                'userId' => $userId,
            ]),
        ]);
    }
}
