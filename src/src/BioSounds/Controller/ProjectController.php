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
            'projects' =>  $projectProvider->getList(),
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
}
