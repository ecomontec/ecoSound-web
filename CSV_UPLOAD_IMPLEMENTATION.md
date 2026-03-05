# CSV Upload Implementation for Species and Sites

## Summary
Batch CSV upload functionality has been added to the Species and Sites administration pages, following the same pattern as the recording metadata upload.

## Completed

### Frontend (Templates)
✅ **Species Page** (`src/templates/administration/species.html.twig`):
- Added dropdown menu with "Upload Species CSV" and "CSV Instructions" options
- Added hidden form for CSV file upload
- Added instructions modal explaining CSV format and required columns
- Added JavaScript handlers for file upload and template download

✅ **Sites Page** (`src/templates/administration/sites.html.twig`):
- Added dropdown menu with "Upload Sites CSV" and "CSV Instructions" options
- Added hidden form for CSV file upload (includes projectId and collectionId)
- Added instructions modal explaining CSV format and required columns
- Added JavaScript handlers for file upload and template download

### Backend Controllers
✅ **Species Controller** (`src/src/BioSounds/Controller/Administration/SpeciesController.php`):
- Added `uploadCSV()` method with full validation and insertion logic

⚠️ **Sites Controller** (`src/src/BioSounds/Controller/Administration/SiteController.php`):
- **NEEDS MANUAL ADDITION**: Add the `uploadCSV()` method before the closing `}` of the class

## To Complete

### 1. Add uploadCSV Method to SiteController
Add this method to `src/src/BioSounds/Controller/Administration/SiteController.php` before the final `}`:

```php
    /**
     * Upload sites from CSV file
     * @return string
     * @throws \Exception
     */
    public function uploadCSV()
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }

        if (!isset($_FILES['sitesCSVFile']) || $_FILES['sitesCSVFile']['error'] != UPLOAD_ERR_OK) {
            return json_encode([
                'error_code' => 1,
                'message' => 'No file uploaded or upload error occurred.',
            ]);
        }

        $projectId = $_POST['projectId'] ?? null;
        $collectionId = $_POST['collectionId'] ?? null;

        $handle = fopen($_FILES['sitesCSVFile']['tmp_name'], "rb");
        if (!$handle) {
            return json_encode([
                'error_code' => 1,
                'message' => 'Unable to open uploaded file.',
            ]);
        }

        $data = [];
        $rowNum = 1;
        $headers = null;
        
        while (!feof($handle)) {
            $row = fgetcsv($handle);
            
            if (!$row || empty(array_filter($row))) {
                $rowNum++;
                continue;
            }

            if ($headers === null) {
                $headers = array_map('trim', $row);
                
                if (!in_array('name', $headers)) {
                    fclose($handle);
                    return json_encode([
                        'error_code' => 1,
                        'message' => "Missing required column: name",
                    ]);
                }
                $rowNum++;
                continue;
            }

            $rowData = array_combine($headers, $row);
            
            if (empty($rowData['name'])) {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: name is required.",
                ]);
            }
            
            $hasCoords = !empty($rowData['longitude_WGS84_dd_dddd']) && !empty($rowData['latitude_WGS84_dd_dddd']);
            $hasGadm = !empty($rowData['gadm0']);
            
            if (!$hasCoords && !$hasGadm) {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: Either coordinates (longitude AND latitude) OR gadm0 must be provided.",
                ]);
            }
            
            if (!empty($rowData['longitude_WGS84_dd_dddd']) && !is_numeric($rowData['longitude_WGS84_dd_dddd'])) {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: longitude must be a number.",
                ]);
            }
            
            if (!empty($rowData['latitude_WGS84_dd_dddd']) && !is_numeric($rowData['latitude_WGS84_dd_dddd'])) {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: latitude must be a number.",
                ]);
            }
            
            $data[] = $rowData;
            $rowNum++;
        }
        fclose($handle);

        if (empty($data)) {
            return json_encode([
                'error_code' => 1,
                'message' => 'No valid data rows found in CSV file.',
            ]);
        }

        $siteProvider = new SiteProvider();
        $inserted = 0;
        
        foreach ($data as $siteData) {
            $insertData = [
                'name' => htmlentities(strip_tags($siteData['name']), ENT_QUOTES),
                'user_id' => Auth::getUserID(),
                'creation_date_time' => date('Y-m-d H:i:s'),
            ];
            
            if (!empty($siteData['longitude_WGS84_dd_dddd'])) {
                $insertData['longitude_WGS84_dd_dddd'] = (float)$siteData['longitude_WGS84_dd_dddd'];
            }
            if (!empty($siteData['latitude_WGS84_dd_dddd'])) {
                $insertData['latitude_WGS84_dd_dddd'] = (float)$siteData['latitude_WGS84_dd_dddd'];
            }
            if (!empty($siteData['topography_m'])) {
                $insertData['topography_m'] = (float)$siteData['topography_m'];
            }
            if (!empty($siteData['freshwater_depth_m'])) {
                $insertData['freshwater_depth_m'] = (float)$siteData['freshwater_depth_m'];
            }
            if (!empty($siteData['gadm0'])) {
                $insertData['gadm0'] = htmlentities(strip_tags($siteData['gadm0']), ENT_QUOTES);
            }
            if (!empty($siteData['gadm1'])) {
                $insertData['gadm1'] = htmlentities(strip_tags($siteData['gadm1']), ENT_QUOTES);
            }
            if (!empty($siteData['gadm2'])) {
                $insertData['gadm2'] = htmlentities(strip_tags($siteData['gadm2']), ENT_QUOTES);
            }
            if (!empty($siteData['iho'])) {
                $insertData['iho'] = htmlentities(strip_tags($siteData['iho']), ENT_QUOTES);
            }
            if (!empty($siteData['realm_id'])) {
                $insertData['realm_id'] = (int)$siteData['realm_id'];
            }
            if (!empty($siteData['biome_id'])) {
                $insertData['biome_id'] = (int)$siteData['biome_id'];
            }
            if (!empty($siteData['functional_type_id'])) {
                $insertData['functional_type_id'] = (int)$siteData['functional_type_id'];
            }
            
            $siteId = $siteProvider->insert($insertData);
            
            if ($collectionId && $siteId) {
                $siteCollection = new SiteCollection();
                $siteCollection->insert([
                    'site_id' => $siteId,
                    'collection_id' => $collectionId,
                ]);
            }
            
            $inserted++;
        }

        return json_encode([
            'error_code' => 0,
            'message' => "Successfully uploaded {$inserted} sites.",
        ]);
    }
```

### 2. Add Route Mappings
Check your routing configuration (likely in `src/index.php` or a router file) and add these routes:

```php
// Species CSV upload
$app->post('/api/admin/speciesMgr/uploadCSV', 'BioSounds\\Controller\\Administration\\SpeciesController:uploadCSV');

// Sites CSV upload  
$app->post('/api/admin/siteManager/uploadCSV', 'BioSounds\\Controller\\Administration\\SiteController:uploadCSV');
```

## CSV Format Specifications

### Species CSV Format
**Required columns:**
- `binomial` - Scientific name (max 100 chars)
- `common_name` - Common name (max 200 chars)
- `level` - Confidence level (0-100)
- `source` - Data source (max 50 chars)

**Optional columns:**
- `genus` - Genus name (max 100 chars)
- `family` - Family name (max 100 chars)
- `taxon_order` - Taxonomic order (max 100 chars)
- `class` - Taxonomic class (max 100 chars)

**Template example:**
```csv
binomial,common_name,genus,family,taxon_order,class,level,source
Passer domesticus,House Sparrow,Passer,Passeridae,Passeriformes,Aves,100,example
```

### Sites CSV Format
**Required:**
- `name` - Site name (max 100 chars)
- Either coordinates (longitude AND latitude) OR gadm0 must be provided

**Optional columns:**
- `longitude_WGS84_dd_dddd` - Longitude in decimal degrees
- `latitude_WGS84_dd_dddd` - Latitude in decimal degrees
- `topography_m` - Elevation/topography in meters
- `freshwater_depth_m` - Depth in meters
- `gadm0` - Country/region (max 100 chars)
- `gadm1` - Admin region level 1 (max 100 chars)
- `gadm2` - Admin region level 2 (max 100 chars)
- `iho` - IHO sea area (max 100 chars)
- `realm_id` - Realm ID (integer)
- `biome_id` - Biome ID (integer)
- `functional_type_id` - Functional type ID (integer)

**Template example:**
```csv
name,longitude_WGS84_dd_dddd,latitude_WGS84_dd_dddd,topography_m,freshwater_depth_m,gadm0,gadm1,gadm2,iho,realm_id,biome_id,functional_type_id
Example Site 1,12.4964,41.9028,50.5,,Italy,Lazio,Roma,,,1,5
Example Site 2,-0.1278,51.5074,,,United Kingdom,England,London,,,2,6
```

## Features Implemented
- CSV file validation with detailed error messages
- Row-by-row data validation
- Automatic ID generation for species
- Site-collection association support
- Downloadable CSV templates
- Instructions modal for both species and sites
- Follows same UX pattern as recording metadata upload

## Testing
1. Go to Admin > Species
2. Click "Add" dropdown > "Upload Species CSV"
3. Upload a CSV file with species data
4. Verify success message and page reload

5. Go to Admin > Sites
6. Select a project and collection
7. Click "Add" dropdown > "Upload Sites CSV"
8. Upload a CSV file with sites data
9. Verify sites are created and linked to the collection

## Notes
- Species IDs are auto-incremented based on max existing ID
- Sites are automatically linked to the selected collection if one is chosen
- Both uploads provide detailed validation errors with row numbers
- HTML entities are properly escaped for security
