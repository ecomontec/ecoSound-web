<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\queueProvider;
use BioSounds\Utils\Auth;

class QueueController extends BaseController
{
    const SECTION_TITLE = 'Queues';

    /**
     * @return string
     * @throws \Exception
     */
    public function show()
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        return $this->twig->render('administration/queues.html.twig');
    }

    public function getListByPage()
    {
        $total = count((new queueProvider())->getQueue());
        $start = $_POST['start'];
        $length = $_POST['length'];
        $search = $_POST['search']['value'];
        $column = $_POST['order'][0]['column'];
        $dir = $_POST['order'][0]['dir'];
        $data = (new queueProvider())->getListByPage($start, $length, $search, $column, $dir);
        if (count($data) == 0) {
            $data = [];
        }
        $result = [
            'draw' => $_POST['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => (new queueProvider())->getFilterCount($search),
            'data' => $data,
        ];
        return json_encode($result);
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
        $queueProvider = new queueProvider();
        $queueProvider->delete($id);
        return json_encode([
            'errorCode' => 0,
            'message' => 'Queue deleted successfully.',
        ]);
    }

    public function export()
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        $colArr = [];
        $file_name = "queues.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        $columns = (new queueProvider())->getColumns();
        foreach ($columns as $column) {
            if ($column['COLUMN_NAME'] == 'user_id') {
                continue;
            }
            $colArr[] = $column['COLUMN_NAME'];
        }
        $Als[] = $colArr;

        $List = (new queueProvider())->getQueue();
        foreach ($List as $Item) {
            if ($Item['status'] == '-2') {
                if ($Item['stop_time']) {
                    $Item['status'] = 'cancelled';
                } else {
                    $Item['status'] = 'being cancelled';
                }
            } elseif ($Item['status'] == '1') {
                $Item['status'] = 'finished';
            } elseif ($Item['status'] == '-1') {
                $Item['status'] = 'failed';
            } else {
                $Item['status'] = 'ongoing';
            }
            unset($Item['user_id']);
            $Als[] = $Item;
        }
        foreach ($Als as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
        exit();
    }
}
