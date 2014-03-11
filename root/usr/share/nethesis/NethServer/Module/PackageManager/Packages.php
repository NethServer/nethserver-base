<?php namespace NethServer\Module\PackageManager;

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
 * TODO: add component description here
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class Packages extends \Nethgui\Controller\AbstractController
{
    private $adapter;

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);
        $platform = $this->getPlatform();
        $log = $this->getLog();

        $loaderFunction = function () use ($platform, $log) {
            $loader = new \ArrayObject();

            $process = $platform->exec("/bin/rpm -qa --queryformat '%{NAME}\t%{VERSION}\t%{RELEASE}\t%{URL}\n'");
            if ($process->getExitCode() !== 0) {
                $log->error($process->getOutput());
                return $loader;
            }
            
            foreach ($process->getOutputArray() as $line) {
                list($name, $version, $release, $url) = explode("\t", trim($line, "\n"));

                if ( ! preg_match('/\.ns6/', $release)) {
                    continue;
                }

                if ($url === '(none)') {
                    $url = '#';
                }

                $loader[] = array(
                    'name' => array($name, $url),
                    'version' => $version,
                    'release' => $release,
                );
            }
            
            return $loader;
        };

        $this->adapter = new \Nethgui\Adapter\LazyLoaderAdapter($loaderFunction);
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        if ($this->adapter) {
            $view['packages'] = array();
            $values = iterator_to_array($this->adapter);
            usort($values, function($a, $b) {
                return strcmp($a['name'][0], $b['name'][0]);
            });
            $view['packages'] = $values;
        }
    }

}