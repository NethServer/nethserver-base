<?php
namespace NethServer\Module\LogViewer;

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
 * Find a log file matching the given query string
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class Find extends \Nethgui\Controller\AbstractController
{
    private $query = NULL;    

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);
        $this->query = $request->hasArgument('q') ? $request->getArgument('q') : NULL;
    }

    private function find($q)
    {
        $command = '/usr/bin/sudo /sbin/e-smith/logviewer ${@}';

        $results = array();
        $args = array();

        if ($q) {
            array_push($args, '-q', $q);
        } else {
            array_push($args, '-l');
        }

        $proc = $this->getPlatform()->exec($command, $args);

        foreach ($proc->getOutputArray() as $line) {
            $fields = explode(':', $line);
            $nameMatch = is_int(strpos($fields[0], $q));
            if (isset($fields[1]) && $fields[1] > 0) {
                $results[] = array('f' => $fields[0], 'm' => intval($fields[1]), 'n' => $nameMatch);
            } else {
                $results[] = array('f' => $fields[0], 'm' => 0, 'n' => $nameMatch);
            }
        }

        // Sort results, giving weight to file name and match count:
        usort($results, function ($a, $b) use ($q) {
                if ($q) {
                    $aNameMatches = $a['n'];
                    $bNameMatches = $b['n'];
                } else {
                    $aNameMatches = FALSE;
                    $bNameMatches = FALSE;
                }

                if ( ! ($aNameMatches xor $bNameMatches)) {
                    $v = $b['m'] - $a['m'];
                } elseif ($aNameMatches) {
                    $v = -1;
                } elseif ($bNameMatches) {
                    $v = 1;
                }

                if ($v === 0) {
                    $v = strcmp($a['f'], $b['f']);
                }

                return $v;
            });

        return $results;
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
         
        $q = $this->query;
        $view['q'] = $q;
        $results = array_filter(array_map(function ($result) use ($view, $q) {
            $result['p'] = $view->getModuleUrl('../Read') . $result['f']; // The log [p]ath
            if($q) {
                $result['q'] = array('q' => $q); // The filter [q]uery
            }
            return $result;
        }, $this->find($this->query)), function ($result) use ($q) {
            return ! $q || $result['n'] || $result['m'];
        });
        
        $view['results'] = $results;

    }

}