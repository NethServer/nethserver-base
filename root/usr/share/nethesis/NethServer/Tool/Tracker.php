<?php
namespace NethServer\Tool;

/*
 * Copyright (C) 2013 Nethesis S.r.l.
 *
 * This script is part of NethServer.
 *
 * NethServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * NethServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NethServer.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Connect to a ptrack process through a unix socket
 * and query for progress state
 *
 * EXPERIMENTAL
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class Tracker extends \Nethgui\Controller\AbstractController
{
    const PTRACK_PATH_TEMPLATE = '/var/run/ptrack/%s.sock';
    const PTRACK_DUMP_PATH = '/var/spool/ptrack/%.16s.dump';
    const TY_DECLARE = 0x01;
    const TY_DONE = 0x02;
    const TY_QUERY = 0x03;
    const TY_PROGRESS = 0x04;
    const TY_ERROR = 0x40;
    const TY_RESPONSE = 0x80;

    private $taskId = FALSE;
    private $progress = FALSE;
    private $tasks = FALSE;
    private $exitCode = FALSE;

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);
        $this->taskId = \Nethgui\array_head($request->getPath());
        if ( ! $this->taskId) {
            return;
        }

        $socketPath = sprintf(self::PTRACK_PATH_TEMPLATE, $this->taskId);
        $dumpPath = sprintf(self::PTRACK_DUMP_PATH, md5($socketPath));
        $previousException = NULL;

        if ($this->getPhpWrapper()->file_exists($dumpPath)) {
            $tmp = json_decode($this->getPhpWrapper()->file_get_contents($dumpPath), TRUE);
            if (is_array($tmp)) {
                $this->progress = $tmp['progress'];
                $this->tasks = $tmp['tasks'];
                $this->exitCode = $tmp['exit_code'];
            }
            //$this->getPhpWrapper()->unlink($dumpPath);
        } elseif (in_array($socketPath, $this->getPhpWrapper()->glob(sprintf(self::PTRACK_PATH_TEMPLATE, '*')))) {
            try {
                $this->progress = $this->queryState($socketPath, 'progress');
                $this->exitCode = FALSE;
            } catch (\Exception $e) {
                $previousException = $e;
            }
        }

        if ($this->getProgress() === FALSE) {
            // Check if we have the detached process object:
            $process = $this->getPlatform()->getDetachedProcess($this->getTaskId());
            if ( ! $process instanceof \Nethgui\System\ProcessInterface) {
                // No dump, no socket, no process: this is a permanent error!
                throw new \Nethgui\Exception\HttpException('Gone', 410, 1383644833);
            }

            if ($process->readExecutionState() === \Nethgui\System\ProcessInterface::STATE_EXITED) {
                $process->dispose();
            }

            // Process object exists: temporary error condition.
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1383145261, $previousException);
        }
    }

    public function getTaskId()
    {
        return $this->taskId;
    }

    public function getProgress()
    {
        return $this->progress;
    }

    public function getTasks()
    {
        return $this->tasks;
    }

    public function getExitCode()
    {
        return $this->exitCode;
    }

    private function queryState($socketPath)
    {
        $progress = FALSE;

        $socket = $this->getPhpWrapper()->fsockopen('unix://' . $socketPath);

        if ($socket === FALSE) {
            throw new \Exception(sprintf('Socket %s open error', $socketPath), 1383145262);
        }

        if ($this->sendMessage($socket, self::TY_QUERY)) {
            $progress = $this->recvMessage($socket);
        }

        $this->getPhpWrapper()->fclose($socket);
        return $progress;
    }

    private function sendMessage($socket, $type, $args = array())
    {
        $payload = json_encode($args);
        $data = pack('Cn', (int) $type, strlen($payload)) . $payload;
        $written = $this->getPhpWrapper()->fwrite($socket, $data);
        if ($written !== strlen($data)) {
            throw new \Exception('Socket write error', 1383145263);
        }
        return TRUE;
    }

    private function recvMessage($socket)
    {
        $buf = $this->getPhpWrapper()->fread($socket, 3);
        if ($buf === FALSE) {
            throw new \Exception('Socket read error', 1383145266);
        }

        $header = unpack('Ctype/nsize', $buf);
        if ( ! is_array($header)) {
            throw new \Exception('Socket read error', 1383145264);
        }

        $message = NULL;
        if ($header['type'] & self::TY_RESPONSE) {
            $message = $this->getPhpWrapper()->fread($socket, $header['size']);
            if ($message === FALSE) {
                throw new \Exception('Socket read error', 1383145265);
            }
        }
        return json_decode($message, TRUE);
    }

}