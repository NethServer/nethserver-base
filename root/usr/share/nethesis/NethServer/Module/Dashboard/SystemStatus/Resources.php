<?php
namespace NethServer\Module\Dashboard\SystemStatus;

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
 * Retrieve statistics abaout server resources:
 * - load
 * - memory
 * - disk space
 * - uptime
 *
 * @author Giacomo Sanchietti
 */
class Resources extends \Nethgui\Controller\AbstractController
{
 
    private $load = array();
    private $memory = array();
    private $uptime = array();
    private $df = array();

    private function readMemory()
    {
        $fields = array();
        $f = file('/proc/meminfo');
        foreach ($f as $line) {
            $tmp = explode(':',$line);
            $tmp2 = explode(' ', trim($tmp[1]));
            $mb = $tmp2[0] % 1024; # kB -> MB
            $fields[trim($tmp[0])] = $mb;
        }
        return $fields; 
    }

    private function readUptime() {
        $data = file_get_contents('/proc/uptime');
        $upsecs = (int)substr($data, 0, strpos($data, ' '));
        $uptime = array (
            'days' => floor($data/60/60/24),
            'hours' => $data/60/60%24,
            'minutes' => $data/60%60,
            'seconds' => $data%60
        );
        return $uptime;
    }

   private function readDF($fs='/') {
       $cmd = '/usr/bin/perl -e \'
           use Filesys::DiskFree; 
           $handle = new Filesys::DiskFree; 
           $handle->df();
           print $handle->total("'.$fs.'").",".$handle->used("'.$fs.'").",".$handle->avail("'.$fs.'");
           \'';
        exec($cmd,$output);
        return explode(",",$output[0]);
   }

    public function process()
    {
        $this->load = sys_getloadavg();
        $this->memory = $this->readMemory();
        $this->uptime = $this->readUptime();
        $this->df = $this->readDF();
    }
 
    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        if (!$this->load) {
            $this->load = sys_getloadavg();
        }

        $view['load1'] = $this->load[0];
        $view['load5'] = $this->load[1];
        $view['load15'] = $this->load[2];

        if (!$this->memory) {
            $this->memory = $this->readMemory();
        }
 
        $view['MemTotal'] = $this->memory['MemTotal'];
        $view['MemFree'] = $this->memory['MemFree'];
        $view['SwapTotal'] = $this->memory['SwapTotal'];
        $view['SwapFree'] = $this->memory['SwapFree'];

        if (!$this->uptime) {
            $this->uptime = $this->readUptime();
        }

        $view['days'] = $this->uptime['days'];
        $view['hours'] = $this->uptime['hours'];
        $view['minutes'] = $this->uptime['minutes'];
        $view['seconds'] = $this->uptime['seconds'];
        
        if (!$this->df) {
            $this->df = $this->readDF();
        }

        $view['root_total'] = $this->df[0];
        $view['root_used'] = $this->df[1];
        $view['root_avail'] = $this->df[2];
     
    }
  

}
