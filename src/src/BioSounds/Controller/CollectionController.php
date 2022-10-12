<?php

namespace BioSounds\Controller;

use BioSounds\Entity\Collection;
use BioSounds\Entity\Recording;
use BioSounds\Entity\Site;
use BioSounds\Entity\Sound;
use BioSounds\Exception\NotAuthenticatedException;
use BioSounds\Provider\CollectionProvider;
use BioSounds\Provider\ProjectProvider;
use BioSounds\Provider\RecordingProvider;
use BioSounds\Provider\SiteProvider;
use BioSounds\Service\RecordingService;
use BioSounds\Utils\Auth;

class CollectionController extends BaseController
{
    const GALLERY_TEMPLATE = 'collection/views/gallery.html.twig';
    const LIST_TEMPLATE = 'collection/views/list.html.twig';
    const ITEMS_PAGE = 9;
    const SECTION_TITLE = 'Collection';

    private $colId;
    private $collection;
    private $recordings = [];

    protected $view;

    /**
     * @return string
     * @throws \Exception
     */
    public function index(int $projectId): string
    {
        $collections = (new CollectionProvider())->getCollectionPagesByPermission($projectId);
        $sites = (new SiteProvider())->getList($projectId);
        $this->leaflet = $this->getProjectLeaflet($sites);
        return $this->twig->render('collection/collections.html.twig', [
            'project' => (new ProjectProvider())->get($projectId),
            'collections' => $collections,
            'sites' => $sites,
            'leaflet' => $this->leaflet
        ]);
    }

    /**
     * @param int $id
     * @param string|null $view
     * @return string
     * @throws \Exception
     */
    public function show(int $id, string $view = null)
    {
        $this->colId = $id;
        $isAccessed = $this->checkPermissions();
        $isAccessed &= $this->isAccessible();
        $this->collection = (new CollectionProvider())->get($this->colId);
        $display = $view == null ? $this->collection->getView() : $view;
        $this->recordings = (new RecordingService())->getListWithImages(
            $this->colId,
            (Auth::getUserID() == null) ? 0 : Auth::getUserID()
        );
        $this->leaflet = $this->getLeaflet($this->recordings);
        if ($isAccessed || $this->collection->getPublic()) {
            return $this->twig->render('collection/collection.html.twig', [
                'project' => (new ProjectProvider())->get($this->collection->getProject()),
                'collection' => $this->collection,
                'list' => $this->recordings,
                'template' => $display == Collection::LIST_VIEW ? self::LIST_TEMPLATE : self::GALLERY_TEMPLATE,
                'display' => $display,
                'leaflet' => $this->leaflet,
                'none' => (new RecordingProvider())->getNullCount($id),
            ]);
        } else {
            return $this->twig->render('collection/noaccess.html.twig');
        }
    }

    /**
     * @param int $id
     * @param string|null $view
     * @return string
     * @throws \Exception
     */
    public function showjs(int $id, string $view = null, string $sites = null)
    {
        $this->colId = $id;
        $isAccessed = $this->checkPermissions();
        $isAccessed &= $this->isAccessible();
        $this->collection = (new CollectionProvider())->get($this->colId);
        $display = $view == null ? $this->collection->getView() : $view;
        $this->recordings = (new RecordingService())->getListWithImages(
            $this->colId,
            (Auth::getUserID() == null) ? 0 : Auth::getUserID(),
            $sites
        );
        $old = (new RecordingService())->getListWithImages(
            $this->colId,
            (Auth::getUserID() == null) ? 0 : Auth::getUserID()
        );
        $this->leaflet = $this->getLeaflet($this->recordings);
        if ($isAccessed || $this->collection->getPublic()) {
            return $this->twig->render('collection/collectionjs.html.twig', [
                'project' => (new ProjectProvider())->get($this->collection->getProject()),
                'old' => $old,
                'collection' => $this->collection,
                'list' => $this->recordings,
                'template' => $display == Collection::LIST_VIEW ? self::LIST_TEMPLATE : self::GALLERY_TEMPLATE,
                'display' => $display,
                'leaflet' => $this->leaflet,
                'none_count' => (new RecordingProvider())->getNullCount($id),
            ]);
        } else {
            return "No results";
        }
    }

    /**
     * @throws \Exception
     */
    private function checkPermissions(): bool
    {
        if (!Auth::isUserLogged()) {
            // throw new NotAuthenticatedException();
            return false;
        }

        if (empty($this->colId)) {
            // throw new \Exception(ERROR_EMPTY_ID);
            return false;
        }
        return true;
    }

    private function isAccessible(): bool
    {
        $visibleCollObjs = Auth::isUserAdmin() ? (new CollectionProvider())->getList() : (new CollectionProvider())->getAccessedList((Auth::getUserID() == null) ? 0 : Auth::getUserID());

        $vCollIDs = array();
        foreach ($visibleCollObjs as $vCollObj) {
            $vCollIDs[] = $vCollObj->getId();
        }

        if (!in_array($this->colId, $vCollIDs)) {
            // throw new \Exception(ERROR_EMPTY_ID);
            return false;
        }
        return true;
    }

    public function getLeaflet(array $allRecordings): array
    {
        $location = array();
        $array = array();
        $arr = array();
        $sites = '';
        $i = 0;
        $j = 0;

        foreach ($allRecordings as $recording) {
            $r = $recording->getRecording();
            if (strlen($r->getLongitude()) > 0 && strlen($r->getLatitude()) > 0) {
                $site = $r->getSite();
                $siteName = $r->getSiteName();
                $longitude[] = $r->getLongitude();
                $latitude[] = $r->getLatitude();
                if (in_array([$r->getLatitude(), $r->getLongitude(), $r->getSiteName()], $location)) {
                    $k = array_search([$r->getLatitude(), $r->getLongitude(), $r->getSiteName()], $location);
                    $array[$k][4] = $array[$k][4] . '!br!' . $r->getName();
                    $array[$k][5]++;
                } else {
                    $location[] = [$r->getLatitude(), $r->getLongitude(), $r->getSiteName()];
                    $array[$i] = [$site, $siteName, $r->getLatitude(), $r->getLongitude()];
                    $array[$i][4] = $r->getName();
                    if ($sites != '') {
                        $sites = $sites . ',' . $site;
                    } else {
                        $sites = $site;
                    }
                    $array[$i][5] = 1;
                    $i = $i + 1;
                }
            }
        }
        $max = 0;
        if ($longitude & $latitude) {
            sort($longitude);
            sort($latitude);
            for ($i = 0; $i < count($longitude); $i++) {
                if ($i == count($longitude) - 1) {
                    $plus = $longitude[$i] + $longitude[0];
                    $minus = $longitude[$i] - $longitude[0];
                } else {
                    $plus = $longitude[$i + 1] + $longitude[$i];
                    $minus = $longitude[$i + 1] - $longitude[$i];
                }
                if ($minus > 180) {
                    $minus = 360 - $minus;
                    $j = 1;
                }
                if ($minus >= $max) {
                    $max = $minus;
                    $arr['longitude_center'] = $plus / 2;
                }
            }
            if ($j == 1) {
                $arr['longitude_center'] = $arr['longitude_center'] + 180;
                foreach ($array as $key => $value) {
                    if (abs($value[3] - $arr['longitude_center']) > 180) {
                        $array[$key][3] = $array[$key][3] + 360;
                    }
                }
            }
            $arr['latitude_center'] = (max($latitude) + min($latitude)) / 2;
            $arr['arr'] = $array;
            $arr['sites'] = $sites;
            $arr['count'] = count($array);
        }
        return $arr;
    }

    public function getProjectLeaflet(array $allSites): array
    {
        $location = array();
        $array = array();
        $arr = array();
        $sites = '';
        $i = 0;
        $j = 0;
        foreach ($allSites as $site) {
            if (strlen($site->getLongitude()) > 0 && strlen($site->getLatitude()) > 0) {
                $longitude[] = $site->getLongitude();
                $latitude[] = $site->getLatitude();
                if (!in_array([$site->getLatitude(), $site->getLongitude(), $site->getName()], $location)) {
                    $location[] = [$site->getLatitude(), $site->getLongitude(), $site->getName()];
                    $array[$i] = [$site->getId(), $site->getName(), $site->getLatitude(), $site->getLongitude()];
                    if ($sites != '') {
                        $sites = $sites . ',' . $site->getId();
                    } else {
                        $sites = $site->getId();
                    }
                    $i = $i + 1;
                }
            }
        }
        $max = 0;
        if ($longitude & $latitude) {
            sort($longitude);
            sort($latitude);
            for ($i = 0; $i < count($longitude); $i++) {
                if ($i == count($longitude) - 1) {
                    $plus = $longitude[$i] + $longitude[0];
                    $minus = $longitude[$i] - $longitude[0];
                } else {
                    $plus = $longitude[$i + 1] + $longitude[$i];
                    $minus = $longitude[$i + 1] - $longitude[$i];
                }
                if ($minus > 180) {
                    $minus = 360 - $minus;
                    $j = 1;
                }
                if ($minus >= $max) {
                    $max = $minus;
                    $arr['longitude_center'] = $plus / 2;
                }
            }
            if ($j == 1) {
                $arr['longitude_center'] = $arr['longitude_center'] + 180;
                foreach ($array as $key => $value) {
                    if (abs($value[3] - $arr['longitude_center']) > 180) {
                        $array[$key][3] = $array[$key][3] + 360;
                    }
                }
            }
            $arr['latitude_center'] = (max($latitude) + min($latitude)) / 2;
            $arr['arr'] = $array;
            $arr['sites'] = $sites;
            $arr['count'] = count($array);
        }
        return $arr;
    }
}