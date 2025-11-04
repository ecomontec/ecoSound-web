<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\TaxonProvider;
use BioSounds\Utils\Auth;

class TaxonController extends BaseController
{
    const SECTION_TITLE = 'Taxon';

    /**
     * @return string
     * @throws \Exception
     */
    public function show()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }

        $taxonProvider = new TaxonProvider();
        
        return $this->twig->render('administration/taxon.html.twig', [
            'taxa' => $taxonProvider->getTaxonStatistics(),
            'classes' => $taxonProvider->getDistinctClassis(),
            'orders' => $taxonProvider->getDistinctOrdo(),
            'families' => $taxonProvider->getDistinctFamilia(),
        ]);
    }

    /**
     * Get paginated taxon list for DataTables
     * @return string
     * @throws \Exception
     */
    public function getListByPage()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }

        $taxonProvider = new TaxonProvider();
        
        $start = $_POST['start'];
        $length = $_POST['length'];
        $search = $_POST['search']['value'];
        $column = $_POST['order'][0]['column'];
        $dir = $_POST['order'][0]['dir'];

        // Get filter values if provided
        $classisFilter = isset($_POST['classisFilter']) ? $_POST['classisFilter'] : '';
        $ordoFilter = isset($_POST['ordoFilter']) ? $_POST['ordoFilter'] : '';
        $familiaFilter = isset($_POST['familiaFilter']) ? $_POST['familiaFilter'] : '';

        // Check if user is admin for editable fields
        $isEditable = Auth::isUserAdmin();

        $total = $taxonProvider->getTotalCount();
        $data = $taxonProvider->getListByPage($start, $length, $search, $column, $dir, $classisFilter, $ordoFilter, $familiaFilter, $isEditable);
        $filteredCount = $taxonProvider->getFilteredCount($search, $classisFilter, $ordoFilter, $familiaFilter);

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
     * Save taxon data
     * @return string
     * @throws \Exception
     */
    public function save()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        $taxonProvider = new TaxonProvider();
        
        $taxonId = isset($_POST['taxon_id']) ? (int)$_POST['taxon_id'] : null;
        $binomial = $_POST['binomial'] ?? '';
        $commonName = $_POST['common_name'] ?? '';
        $genus = $_POST['genus'] ?? '';
        $familia = $_POST['familia'] ?? '';
        $ordo = $_POST['ordo'] ?? '';
        $classis = $_POST['classis'] ?? '';
        $phylum = $_POST['phylum'] ?? '';
        $source = $_POST['source'] ?? '';

        if (empty($binomial)) {
            return json_encode([
                'errorCode' => 1,
                'message' => 'Binomial name is required.',
            ]);
        }

        if (empty($classis)) {
            return json_encode([
                'errorCode' => 1,
                'message' => 'Class (classis) is required.',
            ]);
        }

        $data = [
            'binomial' => $binomial,
            'common_name' => $commonName,
            'genus' => $genus,
            'familia' => $familia,
            'ordo' => $ordo,
            'classis' => $classis,
            'phylum' => $phylum,
            'source' => $source,
        ];

        if ($taxonId) {
            // Update existing taxon
            $taxonProvider->update($taxonId, $data);
            $message = 'Taxon updated successfully.';
        } else {
            // Insert new taxon
            $taxonProvider->insert($data);
            $message = 'Taxon added successfully.';
        }

        return json_encode([
            'errorCode' => 0,
            'message' => $message,
        ]);
    }

    /**
     * Delete taxon
     * @return string
     * @throws \Exception
     */
    public function delete()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        $id = $_POST['id'] ?? [];
        
        if (empty($id)) {
            return json_encode([
                'errorCode' => 1,
                'message' => 'No taxon selected.',
            ]);
        }

        $taxonProvider = new TaxonProvider();
        
        foreach ($id as $taxonId) {
            $taxonProvider->delete((int)$taxonId);
        }

        return json_encode([
            'errorCode' => 0,
            'message' => 'Taxon deleted successfully.',
        ]);
    }

    /**
     * Export taxa to CSV
     * @throws \Exception
     */
    public function export()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }

        $taxonProvider = new TaxonProvider();
        $taxa = $taxonProvider->getTaxonStatistics();

        $fileName = "taxa_" . date('Y-m-d') . ".csv";
        $fp = fopen('php://output', 'w');
        
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $fileName);

        // CSV headers
        $headers = ['ID', 'Binomial Name', 'Common Name', 'Genus', 'Familia', 'Ordo', 'Classis', 'Phylum', 'Source', 'Tags', 'Recordings'];
        fputcsv($fp, $headers);

        // CSV data
        foreach ($taxa as $item) {
            fputcsv($fp, [
                $item['taxon_id'],
                $item['binomial'],
                $item['common_name'] ?? '',
                $item['genus'] ?? '',
                $item['familia'] ?? '',
                $item['ordo'] ?? '',
                $item['classis'] ?? '',
                $item['phylum'] ?? '',
                $item['source'] ?? '',
                $item['tag_count'],
                $item['recording_count'],
            ]);
        }

        fclose($fp);
        exit;
    }

    /**
     * Get autocomplete suggestions for a field
     * @return string
     * @throws \Exception
     */
    public function autocomplete()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }

        $taxonProvider = new TaxonProvider();
        
        $field = $_GET['field'] ?? '';
        $term = $_GET['term'] ?? '';

        $results = $taxonProvider->getAutocompleteSuggestions($field, $term);
        
        // Format for jQuery UI autocomplete
        $suggestions = array_map(function($row) {
            return $row['value'];
        }, $results);

        return json_encode($suggestions);
    }

    /**
     * Get higher taxonomic ranks for auto-fill
     * @return string
     * @throws \Exception
     */
    public function getHigherRanks()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }

        $taxonProvider = new TaxonProvider();
        
        $field = $_GET['field'] ?? '';
        $value = $_GET['value'] ?? '';

        $ranks = $taxonProvider->getHigherRanks($field, $value);

        return json_encode($ranks ?? []);
    }
}
