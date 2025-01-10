<?php

namespace BioSounds\Controller;

use BioSounds\Provider\ProjectProvider;
use BioSounds\Utils\Auth;

class ProjectController extends BaseController
{
    const SECTION_TITLE = 'Project';

    /**
     * @return string
     * @throws \Exception
     */
    public function show()
    {
        $projectProvider = new ProjectProvider();
        return $this->twig->render('project.html.twig', [
            'projects' => $projectProvider->getList(),
        ]);
    }

    public function about()
    {
        return $this->twig->render('about.html.twig', ['title' => 'ecoSound-web - About']);
    }

    public function gsp()
    {
        return $this->twig->render('gsp.html.twig', ['title' => 'ecoSound-web - Global Soundscapes Project']);
    }

    public function search(): string
    {
        $data = [];

        $terms = isset($_POST['term']) ? $_POST['term'] : null;

        if (!empty($terms)) {
            $words = preg_split("/[\s,]+/", $terms);

            $result = (new ProjectProvider())->search($words);

            if (!empty($result)) {
                foreach ($result as $row) {
                    $data[] = [
                        'label' => $row['name'] . ' (' . $row['type'] . ')',
                        'value' => $row['id'],
                        'url' => $row['url'],
                    ];
                }
            }
        }
        return json_encode($data);
    }

    public function cookie_policy()
    {
        return $this->twig->render('cookie_policy.html.twig');
    }
}
