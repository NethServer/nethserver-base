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
    public $sortId = 10;
 
    private $load = array();
    private $memory = array();
    private $uptime = array();
    private $df = array();
    private $cpu = array();
    private $sys_vendor = '';
    private $product_name = '';

    private function readMemory()
    {
        $fields = array();
        $f = file('/proc/meminfo');
        foreach ($f as $line) {
            $tmp = explode(':',$line);
            $tmp2 = explode(' ', trim($tmp[1]));
            $mb = $tmp2[0] / 1024; # kB -> MB
            $fields[trim($tmp[0])] = ceil($mb);
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

    private function readDF() {
        $out = array();
        $ret = array();
        exec('/bin/df -P /', $out);
        # Filesystem Size  Used Avail Use% Mount
        for ($i=0; $i<count($out); $i++) {
            if ($i == 0) {
                continue;
            }
            $tmp = explode(" ", preg_replace( '/\s+/', ' ', $out[$i]));
            // skip fs ($tmp[0]) and perc_used ($tmp[4])
            $ret[$tmp[5]] = array('total' => intval($tmp[1]), 'used' => intval($tmp[2]), 'free' => intval($tmp[3]));
        }
        return $ret;
    }

    private function readCPU() 
    {
        $ret = 0;
        $f = file('/proc/cpuinfo');
        foreach ($f as $line) {
            if (strpos($line, 'processor') === 0) {
                $ret++;
            }
        }
        $tmp = explode(':',$f[4]);

        return array('model' => trim($tmp[1]), 'n' => $ret);
    }

    private function readDMI($id)
    {
        if (file_exists("/sys/devices/virtual/dmi/id/$id")) {
            return file_get_contents("/sys/devices/virtual/dmi/id/$id");
        // also try to fetch info for devicetree based (arm)devices
        } elseif (file_exists("/sys/firmware/devicetree/base/model")) {
            return file_get_contents("/sys/firmware/devicetree/base/model");
        } else {
            return "-";
        }
    }

    public function process()
    {
        $this->load = sys_getloadavg();
        $this->memory = $this->readMemory();
        $this->uptime = $this->readUptime();
        $this->df = $this->readDF();
        $this->cpu = $this->readCPU();
        $this->sys_vendor = $this->readDMI('sys_vendor');
        $this->product_name = $this->readDMI('product_name');
    }
 
    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        if (!$this->load) {
            $this->load = sys_getloadavg();
        }

        $view['load1'] = $this->load[0];
        $view['load5'] = $this->load[1];
        $view['load15'] = $this->load[2];
        
        if (!$this->cpu) {
            $this->cpu = $this->readCPU();
        }
        $view['cpu_num'] = $this->cpu['n']; 
        $view['cpu_model'] = $this->cpu['model']; 

        if (!$this->memory) {
            $this->memory = $this->readMemory();
            $tmp["total"] = $this->memory['MemTotal'];
            $tmp["used"] = $this->memory['MemTotal']-$this->memory['MemFree'];
            $tmp["free"] = $this->memory['MemFree'];
            $view['memory'] = $tmp;
 
            $tmp = array();
            $tmp["total"] = $this->memory['SwapTotal'];
            $tmp["used"] =  $this->memory['SwapTotal']-$this->memory['SwapFree'];
            $tmp["free"] = $this->memory['SwapFree'];
            $view['swap'] = $tmp;
        }


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
        $view['df']  = $this->df; 
        $view['time'] = $this->getPlatform()->exec('/bin/date +"%a %d %b %Y - %H:%M"')->getOutput();
        if (!$this->product_name) {
            $view['product_name'] = $this->readDMI('product_name');
        }
        if (!$this->sys_vendor) {
            $view['sys_vendor'] = $this->readDMI('sys_vendor');
        }
    }
  

}
