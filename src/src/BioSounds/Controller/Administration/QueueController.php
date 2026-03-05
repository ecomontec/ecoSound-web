<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\QueueProvider;
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
        $total = count((new QueueProvider())->getQueue());
        $start = $_POST['start'];
        $length = $_POST['length'];
        $search = $_POST['search']['value'];
        $column = $_POST['order'][0]['column'];
        $dir = $_POST['order'][0]['dir'];
        $data = (new QueueProvider())->getListByPage($start, $length, $search, $column, $dir);
        if (count($data) == 0) {
            $data = [];
        }
        $result = [
            'draw' => $_POST['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => (new QueueProvider())->getFilterCount($search),
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
        $queueProvider = new QueueProvider();
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
        $columns = (new QueueProvider())->getColumns();
        foreach ($columns as $column) {
            if ($column['COLUMN_NAME'] == 'user_id') {
                continue;
            }
            $colArr[] = $column['COLUMN_NAME'];
        }
        $Als[] = $colArr;

        $List = (new QueueProvider())->getQueue();
        foreach ($List as $Item) {
            if ($Item['status'] == '2') {
                $Item['status'] = 'pending';
            } else if ($Item['status'] == '-2') {
                $Item['status'] = 'cancelled';
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

    /**
     * Restart the RabbitMQ worker
     * @return string
     * @throws \Exception
     */
    public function restartWorker()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        try {
            // Kill existing worker processes
            exec('pkill -f "php.*worker.php" 2>&1', $output, $returnCode);
            
            // Wait a moment for processes to terminate
            sleep(1);
            
            // Restart the worker using the start-worker.sh script
            exec('setsid /var/www/html/start-worker.sh < /dev/null > /dev/null 2>&1 &', $output2, $returnCode2);
            
            // Log the restart
            $logMessage = "[" . date('Y-m-d H:i:s') . "] Worker manually restarted by user " . Auth::getUserID() . "\n";
            file_put_contents('/var/www/html/tmp/worker.log', $logMessage, FILE_APPEND);
            
            return json_encode([
                'errorCode' => 0,
                'message' => 'Worker restart initiated. Jobs should resume processing shortly.',
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'errorCode' => 1,
                'message' => 'Failed to restart worker: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if the RabbitMQ worker is running
     * @return string
     * @throws \Exception
     */
    public function workerStatus()
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }

        // Check if worker.php process is running
        exec('pgrep -f "php.*worker.php"', $output, $returnCode);
        
        $running = ($returnCode === 0 && !empty($output));
        
        return json_encode([
            'running' => $running,
            'pid' => $running ? implode(',', $output) : null,
        ]);
    }
}
