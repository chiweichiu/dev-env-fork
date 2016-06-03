<?php

class ApiController extends Pix_Controller
{
    protected function error($str)
    {
        return $this->json(array(
            'error' => true,
            'message' => $str,
        ));
    }

    public function getfileAction()
    {
        $base_folder = $_REQUEST['base'];

        $file_base = realpath(__DIR__ . '/../../files/') . '/';
        $base_folder = realpath($file_base . $base_folder);
        if (strpos($base_folder, $file_base) !== 0) {
            return $this->error(sprintf("資料夾錯誤: %s", $_REQUEST['base']));
        }
        if (!file_exists($base_folder) or !is_file($base_folder)) {
            return $this->error(sprintf("找不到資料夾或不是檔案: %s", $_REQUEST['base']));
        }
        return $this->json(array(
            'error' => false,
            'body' => file_get_contents($base_folder),
        ));
    }

    public function savefileAction()
    {
        $base_folder = $_REQUEST['base'];

        $file_base = realpath(__DIR__ . '/../../files/') . '/';
        $base_folder = realpath($file_base . $base_folder);
        if (strpos($base_folder, $file_base) !== 0) {
            return $this->error(sprintf("資料夾錯誤: %s", $_REQUEST['base']));
        }
        if (!file_exists($base_folder) or !is_file($base_folder)) {
            return $this->error(sprintf("找不到資料夾或不是檔案: %s", $_REQUEST['base']));
        }
        file_put_contents($base_folder, $_REQUEST['body']);
        return $this->json(array(
            'error' => false,
        ));
    }

    public function listfileAction()
    {
        $base_folder = $_REQUEST['base'];

        $file_base = realpath(__DIR__ . '/../../files/') . '/';
        $base_folder = realpath($file_base . $base_folder) . '/';
        if (strpos($base_folder, $file_base) !== 0) {
            return $this->error(sprintf("資料夾錯誤: %s", $_REQUEST['base']));
        }
        if (!file_exists($base_folder) or !is_dir($base_folder)) {
            return $this->error(sprintf("找不到資料夾或是不是資料夾: %s", $_REQUEST['base']));
        }
        $d = opendir($base_folder);
        $ret = array();
        while ($f = readdir($d)) {
            if (in_array($f, array('.', '..'))) {
                continue;
            }
            $ret[] = array(
                'type' => is_dir($base_folder . $f) ?'dir' : 'file',
                'name' => $f,
                'path' => substr($base_folder . $f, strlen($file_base) - 1),
                'size' => filesize($base_folder . $f),
                'mtime' => date('Y/m/d H:i:s', filemtime($base_folder . $f)),
            );
        }
        return $this->json(array(
            'error' => false,
            'files' => $ret,
        ));
    }

    public function addobjectAction()
    {
        $base_folder = $_REQUEST['base'];
        $name = $_REQUEST['name'];

        $file_base = realpath(__DIR__ . '/../../files/') . '/';
        $base_folder = realpath($file_base . $base_folder) . '/';
        if (strpos($base_folder, $file_base) !== 0) {
            return $this->error(sprintf("資料夾錯誤: %s", $_REQUEST['base']));
        }

        if ('' == trim($name, '.') or strpos($name, '/') !== false) {
            return $this->error(sprintf("新資料夾名稱不正確: %s", $_REQUEST['name']));
        }

        if (file_exists($base_folder . $name)) {
            return $this->error(sprintf("檔案已存在 %s", $_REQUEST['name']));
        }

        if ('dir' == $_REQUEST['type']) {
            mkdir($base_folder . $name);
            $path = substr($base_folder . $name, strlen($file_base) - 1);
        } else {
            touch($base_folder . $name);
            $path = substr($base_folder, strlen($file_base) - 1);
        }
        return $this->json(array(
            'error' => false,
            'message' => sprintf("新增資料夾成功"),
            'path' => $path,
        ));
    }

    public function runcommandAction()
    {
        $base_folder = $_REQUEST['base'];
        $command = $_REQUEST['command'];

        $file_base = realpath(__DIR__ . '/../../files/') . '/';
        $base_folder = realpath($file_base . $base_folder) . '/';
        if (strpos($base_folder, $file_base) !== 0 or !is_dir($base_folder)) {
            return $this->error(sprintf("資料夾錯誤: %s", $_REQUEST['base']));
        }

        $session_id = crc32(uniqid());
        touch(__DIR__ . "/../../sessions/{$session_id}.stdout");
        file_put_contents(__DIR__ . "/../../sessions/{$session_id}.pending", json_encode(array(
            'session_id' => $session_id,
            'command' => $command,
            'base_folder' => $base_folder,
        )));
        return $this->json(array(
            'error' => false,
            'session_id' => $session_id,
        ));
    }

    public function getsessionAction()
    {
        $session_id = intval($_GET['session_id']);
        $session_base = __DIR__ . '/../../sessions/';
        $stdout_offset = intval($_GET['stdout_offset']);
        $stderr_offset = intval($_GET['stderr_offset']);

        if (!file_exists("{$session_base}{$session_id}.stdout")) {
            return $this->error("找不到 Session: $session_id");
        }
        $stdout = file_get_contents("{$session_base}{$session_id}.stdout", false, null, $stdout_offset);
        $stderr = file_get_contents("{$session_base}{$session_id}.stderr", false, null, $stderr_offset);

        if (file_exists("{$session_base}{$session_id}.done")) {
            unlink("{$session_base}{$session_id}.done");
            unlink("{$session_base}{$session_id}.stderr");
            unlink("{$session_base}{$session_id}.stdout");
            error_log("clean {$session_id}");
            $done = true;
        } else {
            $done = false;
        }

        return $this->json(array(
            'stdout' => $stdout,
            'stderr' => $stderr,
            'stdout_offset' => $stdout_offset + strlen($stdout),
            'stderr_offset' => $stderr_offset + strlen($stderr),
            'done' => $done,
        ));
    }
}