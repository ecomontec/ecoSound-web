<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\ProjectProvider;
use BioSounds\Utils\Auth;

class ProjectController extends BaseController
{
    const SECTION_TITLE = 'Projects';

    /**
     * @return mixed
     * @throws \Exception
     */
    public function show()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }
        $projects = (new ProjectProvider())->getWithPermission(Auth::getUserID());

        return $this->twig->render('administration/projects.html.twig', [
            'projects' => $projects,
        ]);
    }

    /**
     * @return bool|int|null
     * @throws \Exception
     */
    public function save()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }
        if (!is_dir(ABSOLUTE_DIR . 'sounds/projects')) {
            mkdir(ABSOLUTE_DIR . 'sounds/projects', 0777, true);
        }
        $projectProvider = new ProjectProvider();
        $data = [];
        foreach ($_POST as $key => $value) {
            if (strrpos($key, '_')) {
                $key = substr($key, 0, strrpos($key, '_'));
            }
            $data[$key] = $value;
        }
        if (isset($data['projectId'])) {
            if ($_FILES["picture_id_file"]["size"] != 0) {
                $data['picture_id'] = $data['projectId'] . '.' . explode('/', $_FILES["picture_id_file"]['type'])[1];
                move_uploaded_file($_FILES["picture_id_file"]['tmp_name'], ABSOLUTE_DIR . 'sounds/projects/' . $data['picture_id']);
            } else {
                unset($data['picture_id']);
            }
            $projectProvider->update($data);
            return json_encode([
                'errorCode' => 0,
                'message' => 'Project updated successfully.',
            ]);
        } else {
            $data['creator_id'] = Auth::getUserID();
            $data['picture_id'] = null;
            $insert = $projectProvider->insert($data);
            if ($insert > 0) {
                if ($_FILES["picture_id_file"]["size"] != 0) {
                    $data['picture_id'] = $insert . '.' . explode('/', $_FILES["picture_id_file"]['type'])[1];
                    $data['projectId'] = $insert;
                    $projectProvider->update($data);
                    move_uploaded_file($_FILES["picture_id_file"]['tmp_name'], ABSOLUTE_DIR .  'sounds/projects/' . $data['picture_id']);
                } else {
                    unset($data['picture_id']);
                }
                return json_encode([
                    'errorCode' => 0,
                    'message' => 'Project created successfully.',
                ]);
            }
        }
    }

    public function description(int $project_id)
    {
        $project = (new ProjectProvider())->get($project_id);

        return json_encode([
            'errorCode' => 0,
            'data' => $this->twig->render('administration/projectEdit.html.twig', [
                'project' => $project,
            ]),
        ]);

    }
}
