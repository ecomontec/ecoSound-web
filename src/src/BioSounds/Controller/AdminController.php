<?php

namespace BioSounds\Controller;

use BioSounds\Controller\Administration\IndexLogController;
use BioSounds\Controller\Administration\UserController;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Utils\Auth;
use BioSounds\Controller\Administration\CollectionController as CollectionController;
use BioSounds\Controller\Administration\SettingController as SettingController;
use BioSounds\Controller\Administration\RecordingController as RecordingController;
use BioSounds\Controller\Administration\SiteController as SiteController;
use BioSounds\Controller\Administration\TagController as TagController;
use BioSounds\Controller\Administration\ProjectController as ProjectController;

class AdminController extends BaseController
{
    /**
     * AdminController constructor.
     * @throws \Exception
     */
    public function create()
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }

        return $this->settings();
    }

    /**
     * @param string|null $action
     * @return false|string
     * @throws \Exception
     */
    public function settings(?string $action = null)
    {
        if (!empty($action)) {
            return (new SettingController($this->twig))->$action();
        }
        return (new SettingController($this->twig))->show();
    }

    /**
     * @return false|string
     * @throws \Exception
     */
    public function selfService()
    {
        return (new UserController($this->twig))->selfService(Auth::getUserID());
    }

    /**
     * @throws \Exception
     */
    public function collections()
    {
        return (new CollectionController($this->twig))->show();
    }

    /**
     * @throws \Exception
     */
    public function collectionMgr(?string $action = null, int $id = null)
    {
        return (new CollectionController($this->twig))->$action($id);
    }

    /**
     * @throws \Exception
     */
    public function projects()
    {
        return (new ProjectController($this->twig))->show();
    }

    /**
     * @throws \Exception
     */
    public function projectMgr(?string $action = null, int $id = null)
    {
        return (new ProjectController($this->twig))->$action($id);
    }

    /**
     * @param string|null $action
     * @return false|string
     * @throws \Exception
     */
    public function users()
    {
        return (new UserController($this->twig))->show();
    }

    /**
     * @param string $action
     * @param int|null $id
     * @return mixed
     */
    public function userMgr(string $action, int $id = null)
    {
        return (new UserController($this->twig))->$action($id);
    }

    /**
     * @param mixed ...$args
     * @return mixed
     * @throws \Exception
     */
    public function recordings(...$args)
    {
        return (new RecordingController($this->twig))->show(empty($args[0]) ? null : $args[0]);
    }

    /**
     * @param string $action
     * @param int|null $id
     * @return mixed
     */
    public function recordingManager(string $action, int $id = null)
    {
        return (new RecordingController($this->twig))->$action($id);
    }

    /**
     * @param string|null $action
     * @return false|string
     * @throws \Exception
     */
    public function sites()
    {
        return (new SiteController($this->twig))->show();
    }

    /**
     * @param string $action
     * @param int|null $id
     * @return mixed
     */
    public function siteManager(string $action, int $id = null)
    {
        return (new SiteController($this->twig))->$action($id);
    }

    /**
     * @return false|string
     * @throws \Exception
     */
    public function getExplore(int $id = 0)
    {
        return (new SiteController($this->twig))->getExplore($id);
    }

    /**
     * @param string|null $action
     * @return false|string
     * @throws \Exception
     */
    public function tags()
    {
        return (new TagController($this->twig))->show();
    }

    /**
     * @param string $action
     * @param int|null $id
     * @return mixed
     */
    public function tagMgr(string $action, int $id = null)
    {
        return (new TagController($this->twig))->$action($id);
    }

    /**
     * @param string|null $action
     * @return false|string
     * @throws \Exception
     */
    public function indexLogs()
    {
        return (new IndexLogController($this->twig))->show();
    }

    /**
     * @param string $action
     * @param int|null $id
     * @return mixed
     */
    public function indexLogMgr(string $action, int $id = null)
    {
        return (new IndexLogController($this->twig))->$action($id);
    }
}
