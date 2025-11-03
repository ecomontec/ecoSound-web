<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\SpeciesProvider;
use BioSounds\Utils\Auth;

class SpeciesController extends BaseController
{
    const SECTION_TITLE = 'Species';

    /**
     * @return string
     * @throws \Exception
     */
    public function show()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }

        $speciesProvider = new SpeciesProvider();
        
        return $this->twig->render('administration/species.html.twig', [
            'species' => $speciesProvider->getSpeciesStatistics(),
            'classes' => $speciesProvider->getDistinctClasses(),
            'orders' => $speciesProvider->getDistinctOrders(),
            'families' => $speciesProvider->getDistinctFamilies(),
        ]);
    }

    /**
     * Get paginated species list for DataTables
     * @return string
     * @throws \Exception
     */
    public function getListByPage()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }

        $speciesProvider = new SpeciesProvider();
        
        $start = $_POST['start'];
        $length = $_POST['length'];
        $search = $_POST['search']['value'];
        $column = $_POST['order'][0]['column'];
        $dir = $_POST['order'][0]['dir'];

        // Get filter values if provided
        $classFilter = isset($_POST['classFilter']) ? $_POST['classFilter'] : '';
        $orderFilter = isset($_POST['orderFilter']) ? $_POST['orderFilter'] : '';
        $familyFilter = isset($_POST['familyFilter']) ? $_POST['familyFilter'] : '';

        // Check if user is admin for editable fields
        $isEditable = Auth::isAdmin();

        $total = $speciesProvider->getTotalCount();
        $data = $speciesProvider->getListByPage($start, $length, $search, $column, $dir, $classFilter, $orderFilter, $familyFilter, $isEditable);
        $filteredCount = $speciesProvider->getFilteredCount($search, $classFilter, $orderFilter, $familyFilter);

        if (count($data) == 0) {
            $data = [];
        }

        $result = [
            'draw' => $_POST['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => $filteredCount,
            'data' => $data,
        ];

        return json_encode($result);
    }

    /**
     * Save species data
     * @return string
     * @throws \Exception
     */
    public function save()
    {
        if (!Auth::isAdmin()) {
            throw new ForbiddenException();
        }

        $speciesProvider = new SpeciesProvider();
        
        $speciesId = isset($_POST['species_id']) ? (int)$_POST['species_id'] : null;
        $binomial = $_POST['binomial'] ?? '';
        $commonName = $_POST['common_name'] ?? '';
        $genus = $_POST['genus'] ?? '';
        $family = $_POST['family'] ?? '';
        $order = $_POST['taxon_order'] ?? '';
        $class = $_POST['class'] ?? '';

        if (empty($binomial)) {
            return json_encode([
                'errorCode' => 1,
                'message' => 'Binomial name is required.',
            ]);
        }

        $data = [
            'binomial' => $binomial,
            'common_name' => $commonName,
            'genus' => $genus,
            'family' => $family,
            'taxon_order' => $order,
            'class' => $class,
        ];

        if ($speciesId) {
            // Update existing species
            $speciesProvider->update($speciesId, $data);
            $message = 'Species updated successfully.';
        } else {
            // Insert new species
            $speciesProvider->insert($data);
            $message = 'Species added successfully.';
        }

        return json_encode([
            'errorCode' => 0,
            'message' => $message,
        ]);
    }

    /**
     * Delete species
     * @return string
     * @throws \Exception
     */
    public function delete()
    {
        if (!Auth::isAdmin()) {
            throw new ForbiddenException();
        }

        $id = $_POST['id'] ?? [];
        
        if (empty($id)) {
            return json_encode([
                'errorCode' => 1,
                'message' => 'No species selected.',
            ]);
        }

        $speciesProvider = new SpeciesProvider();
        
        foreach ($id as $speciesId) {
            $speciesProvider->delete((int)$speciesId);
        }

        return json_encode([
            'errorCode' => 0,
            'message' => 'Species deleted successfully.',
        ]);
    }

    /**
     * Export species to CSV
     * @throws \Exception
     */
    public function export()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }

        $speciesProvider = new SpeciesProvider();
        $species = $speciesProvider->getSpeciesStatistics();

        $fileName = "species_" . date('Y-m-d') . ".csv";
        $fp = fopen('php://output', 'w');
        
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $fileName);

        // CSV headers
        $headers = ['ID', 'Binomial Name', 'Common Name', 'Genus', 'Family', 'Order', 'Class', 'Tags', 'Recordings'];
        fputcsv($fp, $headers);

        // CSV data
        foreach ($species as $item) {
            fputcsv($fp, [
                $item['species_id'],
                $item['binomial'],
                $item['common_name'] ?? '',
                $item['genus'] ?? '',
                $item['family'] ?? '',
                $item['taxon_order'] ?? '',
                $item['class'] ?? '',
                $item['tag_count'],
                $item['recording_count'],
            ]);
        }

        fclose($fp);
        exit;
    }
}
