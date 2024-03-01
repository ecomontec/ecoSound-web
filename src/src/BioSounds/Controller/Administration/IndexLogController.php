<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\IndexLogProvider;
use BioSounds\Utils\Auth;

class IndexLogController extends BaseController
{
    const SECTION_TITLE = 'IndexLogs';

    /**
     * @return string
     * @throws \Exception
     */
    public function show()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }
        $indexLogProvider = new IndexLogProvider();
        return $this->twig->render('administration/indexLogs.html.twig', [
            'indexLogs' => $indexLogProvider->getList(),
        ]);
    }

    public function getListByPage()
    {
        $total = count((new IndexLogProvider())->getIndexLog());
        $start = $_POST['start'];
        $length = $_POST['length'];
        $search = $_POST['search']['value'];
        $column = $_POST['order'][0]['column'];
        $dir = $_POST['order'][0]['dir'];
        $data = (new IndexLogProvider())->getListByPage($start, $length, $search, $column, $dir);
        if (count($data) == 0) {
            $data = [];
        }
        $result = [
            'draw' => $_POST['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => (new IndexLogProvider())->getFilterCount($search),
            'data' => $data,
        ];
        return json_encode($result);
    }

    /**
     * @throws \Exception
     */
    public function export()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }
        $colArr = [];
        $file_name = "indexLogs.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        $columns = (new IndexLogProvider())->getColumns();
        foreach ($columns as $column) {
            if ($column['COLUMN_NAME'] != 'variable_type' && $column['COLUMN_NAME'] != 'variable_order' && $column['COLUMN_NAME'] != 'variable_name' && $column['COLUMN_NAME'] != 'variable_value') {
                $colArr[] = $column['COLUMN_NAME'];
            }
        }
        array_splice($colArr, 2, 0, 'recording');
        array_splice($colArr, 4, 0, 'user');
        array_splice($colArr, 6, 0, 'index');
        array_splice($colArr, 12, 0, 'output_value_6');
        array_splice($colArr, 12, 0, 'output_name_6');
        array_splice($colArr, 12, 0, 'output_value_5');
        array_splice($colArr, 12, 0, 'output_name_5');
        array_splice($colArr, 12, 0, 'output_value_4');
        array_splice($colArr, 12, 0, 'output_name_4');
        array_splice($colArr, 12, 0, 'output_value_3');
        array_splice($colArr, 12, 0, 'output_name_3');
        array_splice($colArr, 12, 0, 'output_value_2');
        array_splice($colArr, 12, 0, 'output_name_2');
        array_splice($colArr, 12, 0, 'output_value_1');
        array_splice($colArr, 12, 0, 'output_name_1');
        array_splice($colArr, 12, 0, 'input_value_6');
        array_splice($colArr, 12, 0, 'input_name_6');
        array_splice($colArr, 12, 0, 'input_value_5');
        array_splice($colArr, 12, 0, 'input_name_5');
        array_splice($colArr, 12, 0, 'input_value_4');
        array_splice($colArr, 12, 0, 'input_name_4');
        array_splice($colArr, 12, 0, 'input_value_3');
        array_splice($colArr, 12, 0, 'input_name_3');
        array_splice($colArr, 12, 0, 'input_value_2');
        array_splice($colArr, 12, 0, 'input_name_2');
        array_splice($colArr, 12, 0, 'input_value_1');
        array_splice($colArr, 12, 0, 'input_name_1');
        array_splice($colArr, 12, 0, 'channel');
        $Als[] = $colArr;
        $List = (new IndexLogProvider())->getIndexLog();

        foreach ($List as $Item) {
            $valueToMove = $Item['recordingName'] == null ? '' : $Item['recordingName'];
            unset($Item['recordingName']);
            array_splice($Item, 2, 0, $valueToMove);
            $valueToMove = $Item['userName'] == null ? '' : $Item['userName'];
            unset($Item['userName']);
            array_splice($Item, 4, 0, $valueToMove);
            $valueToMove = $Item['indexName'] == null ? '' : $Item['indexName'];
            unset($Item['indexName']);
            array_splice($Item, 6, 0, $valueToMove);

            $Als[] = $Item;
        }
        foreach ($Als as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
        exit();
    }

    public function delete()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }

        $id = $_POST['id'];

        if (empty($id)) {
            throw new \Exception(ERROR_EMPTY_ID);
        }

        $indexLogProvider = new IndexLogProvider();
        $indexLogProvider->delete($id);

        return json_encode([
            'errorCode' => 0,
            'message' => 'Index log deleted successfully.',
        ]);
    }
}
