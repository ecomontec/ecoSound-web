<?php

namespace BioSounds\Controller;

use BioSounds\Entity\Collection;

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
        $str = '';
        $collections = (new CollectionProvider())->getCollectionPagesByPermission($projectId);
        foreach ($collections as $collection) {
            $str .= $collection->getId() . ',';
        }
        $sites = (new SiteProvider())->getListWithCollection($projectId);
        $this->leaflet = $this->getProjectLeaflet($sites, substr($str, 0, strlen($str) - 1));
        return $this->twig->render('collection/collections.html.twig', [
            'project' => (new ProjectProvider())->get($projectId),
            'collections' => $collections,
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
        if ($isAccessed || $this->collection->getPublicAccess()) {
            return $this->twig->render('collection/collection.html.twig', [
                'project' => (new ProjectProvider())->get($this->collection->getProject()),
                'collection' => $this->collection,
                'list' => $this->recordings,
                'display' => $display,
                'leaflet' => $this->leaflet,
                'none_count' => (new RecordingProvider())->getNullCount($id),
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
        if ($isAccessed || $this->collection->getPublicAccess()) {
            return $this->twig->render('collection/collectionjs.html.twig', [
                'project' => (new ProjectProvider())->get($this->collection->getProject()),
                'old' => $old,
                'collection' => $this->collection,
                'list' => $this->recordings,
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
            $site = $r->getSite();
            $siteName = $r->getSiteName();
            if (strlen($r->getLongitude()) > 0 && strlen($r->getLatitude()) > 0) {
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
            } else if ($site != null) {
                if ($s = (new SiteProvider())->get($site)) {
                    if ($result = $this->gadm($s)) {
                        $latitude[] = $result[1];
                        $longitude[] = $result[0];
                        if (in_array([$result[0], $result[1], $r->getSiteName()], $location)) {
                            $k = array_search([$result[0], $result[1], $r->getSiteName()], $location);
                            $array[$k][4] = $array[$k][4] . '!br!' . $r->getName();
                            $array[$k][5]++;
                        } else {
                            $location[] = [$result[0], $result[1], $r->getSiteName()];
                            $array[$i] = [$site, $siteName, $result[1], $result[0]];
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
        $array = array();
        $arr = array();
        $j = 0;

        foreach ($allSites as $site) {
            if (strlen($site['x']) > 0 && strlen($site['y']) > 0) {
                $latitude[] = $site['y'];
                $longitude[] = $site['x'];
                $array[] = [$site['site_id'], $site['name'], $site['y'], $site['x'], $site['collection']];
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
            $arr['count'] = count($array);
        }
        return $arr;
    }

    public function gadm($site)
    {
        if ($site->getGadm2() != null) {
            $level = 2;
            $name = $site->getGadm2();
        } elseif ($site->getGadm1() != null) {
            $level = 1;
            $name = $site->getGadm1();
        } elseif ($site->getGadm0() != null) {
            $level = 0;
            $name = $site->getGadm0();
        } else {
            return false;
        }
        $result = (new SiteProvider())->getGamd($level, $name);
        return [$result['x'], $result['y']];
    }
}