<?php

namespace BioSounds\Controller;

use BioSounds\Entity\Collection;
use BioSounds\Entity\Recording;
use BioSounds\Entity\Site;
use BioSounds\Entity\Sound;
use BioSounds\Exception\NotAuthenticatedException;
use BioSounds\Provider\CollectionProvider;
use BioSounds\Provider\RecordingProvider;
use BioSounds\Service\RecordingService;
use BioSounds\Utils\Auth;

class CollectionController extends BaseController
{
    const GALLERY_TEMPLATE = 'collection/views/gallery.html.twig';
    const LIST_TEMPLATE = 'collection/views/list.html.twig';
    const ITEMS_PAGE = 9;
    const SECTION_TITLE = 'Collections';

    private $page;
    private $colId;
    private $recordingNum;
    private $pageNum;
    private $openCollection = false;
    private $collection;
    private $recordings = [];
    private $filter = [];

    protected $view;

    /**
     * @return string
     * @throws \Exception
     */
    public function index(int $page = 1): string
    {
        $collProvider = new CollectionProvider();

        $collNum = $collProvider->countCollections();
        $pages = $collNum > 0 ? ceil($collNum / self::ITEMS_PAGE) : 1;

        return $this->twig->render('collection/collections.html.twig', [
            'collections' => $collProvider->getCollectionPages(
                $this::ITEMS_PAGE,
                $this::ITEMS_PAGE * ($page - 1)
            ),
            'currentPage' => ($page > $pages) ?: $page,
            'pages' => $pages
        ]);
    }

    /**
     * @param int $id
     * @param int $page
     * @param string|null $view
     * @return string
     * @throws \Exception
     */
    public function show(int $id, int $page = 1, string $view = null)
    {
        $this->colId = $id;

        //TODO: Set open collections in administration (not hard-coded)
        if ($id == 1 || $id == 3 || $id == 18 || $id == 31 || $id == 19 || $id == 39) {
            $this->openCollection = true;
        }

        $isAccessed = $this->checkPermissions();

        $isAccessed &= $this->isAccessible();

        $this->page = $page;

        $this->collection = (new CollectionProvider())->get($this->colId);

        $display = $view == null ? $this->collection->getView() : $view;

        if (isset($_POST['species']) && !empty($_POST['species'])) {
            $this->filter[Sound::SPECIES_ID] = filter_var($_POST['species'], FILTER_VALIDATE_INT);
        }

        if (isset($_POST['rating']) && !empty($_POST['rating'])) {
            $this->filter[Sound::RATING] = filter_var($_POST['rating'], FILTER_SANITIZE_STRING);
        }

        if (isset($_POST['site']) && !empty($_POST['site'])) {
            $this->filter[Site::PRIMARY_KEY] = filter_var($_POST['site'], FILTER_VALIDATE_INT);
        }

        if (isset($_POST['doi']) && !empty($_POST['doi'])) {
            $this->filter[Recording::DOI] = filter_var($_POST['doi'], FILTER_SANITIZE_STRING);
        }

        $this->recordingNum = (new RecordingProvider())->countReady($this->colId, $this->filter);
        if ($this->recordingNum > 0) {
            $this->pageNum = ceil($this->recordingNum / self::ITEMS_PAGE);

            if ($this->page > $this->pageNum) {
                $this->page = 1;
            }
        }

        $this->recordings = (new RecordingService())->getListWithImages(
            $this->colId,
            (Auth::getUserID() == null) ? 0 : Auth::getUserID(),
            self::ITEMS_PAGE,
            self::ITEMS_PAGE * ($this->page - 1),
            $this->filter
        );
        $allRecordings = (new RecordingService())->getAllListWithImages(
            $this->colId,
            (Auth::getUserID() == null) ? 0 : Auth::getUserID(),
            $this->filter
        );
        $this->leaflet = $this->getLeaflet($allRecordings);

        if (isset($_POST['site-name'])) {
            $this->filter['siteName'] = filter_var($_POST['site-name'], FILTER_SANITIZE_STRING);
        }

        if (isset($_POST['species-name'])) {
            $this->filter['speciesName'] = filter_var($_POST['species-name'], FILTER_SANITIZE_STRING);
        }

        if ($isAccessed) {
            return $this->twig->render('collection/collection.html.twig', [
                'collection' => $this->collection,
                'pageNum' => $this->pageNum,
                'currentPage' => $this->page,
                'recordingNum' => $this->recordingNum,
                'list' => $this->recordings,
                'template' => $display == Collection::LIST_VIEW ? self::LIST_TEMPLATE : self::GALLERY_TEMPLATE,
                'display' => $display,
                'filter' => $this->filter,
                'leaflet' => $this->leaflet
            ]);
        } else {
            return $this->twig->render('collection/noaccess.html.twig');
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
            $longitude[] = $r->getLongitude();
            $latitude[] = $r->getLatitude();
            if (in_array([$r->getLatitude(), $r->getLongitude()], $location)) {
                $k = array_search([$r->getLatitude(), $r->getLongitude()], $location);
                $array[$k][4] = $array[$k][4] . '!br!' . $r->getName();
            } else {
                $location[] = [$r->getLatitude(), $r->getLongitude()];
                $array[$i] = [$site, $siteName, $r->getLatitude(), $r->getLongitude()];
                $array[$i][4] = $r->getName();
                if ($sites != '') {
                    $sites = $sites . ',' . $site;
                } else {
                    $sites = $site;
                }
                $i = $i + 1;
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

    /**
     * @param int $id
     * @param int $page
     * @param string|null $view
     * @return string
     * @throws \Exception
     */
    public function showjs(int $id, int $page = 1, string $view = null, string $sites = null)
    {
        $this->colId = $id;

        //TODO: Set open collections in administration (not hard-coded)
        if ($id == 1 || $id == 3 || $id == 18 || $id == 31 || $id == 19 || $id == 39) {
            $this->openCollection = true;
        }

        $isAccessed = $this->checkPermissions();

        $isAccessed &= $this->isAccessible();

        $this->page = $page;

        $this->collection = (new CollectionProvider())->get($this->colId);

        $display = $view == null ? $this->collection->getView() : $view;

        if (isset($_POST['species']) && !empty($_POST['species'])) {
            $this->filter[Sound::SPECIES_ID] = filter_var($_POST['species'], FILTER_VALIDATE_INT);
        }

        if (isset($_POST['rating']) && !empty($_POST['rating'])) {
            $this->filter[Sound::RATING] = filter_var($_POST['rating'], FILTER_SANITIZE_STRING);
        }

        if (isset($_POST['site']) && !empty($_POST['site'])) {
            $this->filter[Site::PRIMARY_KEY] = filter_var($_POST['site'], FILTER_VALIDATE_INT);
        }

        if (isset($_POST['doi']) && !empty($_POST['doi'])) {
            $this->filter[Recording::DOI] = filter_var($_POST['doi'], FILTER_SANITIZE_STRING);
        }

        $this->recordingNum = (new RecordingProvider())->countReady($this->colId, $this->filter, $sites);
        if ($this->recordingNum > 0) {
            $this->pageNum = ceil($this->recordingNum / self::ITEMS_PAGE);

            if ($this->page > $this->pageNum) {
                $this->page = 1;
            }
        }
        $this->recordings = (new RecordingService())->getListWithImages(
            $this->colId,
            (Auth::getUserID() == null) ? 0 : Auth::getUserID(),
            self::ITEMS_PAGE,
            self::ITEMS_PAGE * ($this->page - 1),
            $this->filter,
            $sites
        );
        $allRecordings = (new RecordingService())->getAllListWithImages(
            $this->colId,
            (Auth::getUserID() == null) ? 0 : Auth::getUserID(),
            $this->filter,
            $sites
        );
        $this->leaflet = $this->getLeaflet($allRecordings);
        if (isset($_POST['site-name'])) {
            $this->filter['siteName'] = filter_var($_POST['site-name'], FILTER_SANITIZE_STRING);
        }

        if (isset($_POST['species-name'])) {
            $this->filter['speciesName'] = filter_var($_POST['species-name'], FILTER_SANITIZE_STRING);
        }

        if ($isAccessed) {
            return $this->twig->render('collection/collectionjs.html.twig', [
                'collection' => $this->collection,
                'pageNum' => $this->pageNum,
                'currentPage' => $this->page,
                'recordingNum' => $this->recordingNum,
                'list' => $this->recordings,
                'template' => $display == Collection::LIST_VIEW ? self::LIST_TEMPLATE : self::GALLERY_TEMPLATE,
                'display' => $display,
                'filter' => $this->filter,
                'leaflet' => $this->leaflet
            ]);
        } else {
            return "No results";
        }
    }
}