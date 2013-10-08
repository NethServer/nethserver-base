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
 * TODO: add component description here
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class Read extends \Nethgui\Controller\AbstractController
{
    private $logFile;
    private $offset;
    private $query;
    private $isRegexp = FALSE;

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);
        
        $this->logFile = join('/', $request->getPath());

        // Prepend / to logFile path
        if ($this->logFile) {
            $this->logFile = '/' . $this->logFile;
        }

        // Obtain the innermost request values:
        $subrequest = $request;
        foreach($request->getPath() as $path) {
            $subrequest = $subrequest->spawnRequest($path);
        }

        $this->offset = $subrequest->hasParameter('o') ? $subrequest->getParameter('o') : 0;
        $this->query = $subrequest->hasParameter('q') ? $subrequest->getParameter('q') : NULL;
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);

        $logFileValidator = $this->createValidator()->platform('logfile');

        if ( ! $logFileValidator->evaluate($this->logFile)) {
            throw new \Nethgui\Exception\HttpException(sprintf("%s: resource not found", __CLASS__), 404, 1366643514);
        }

        $offsetValidator = $this->createValidator(\Nethgui\System\PlatformInterface::NONNEGATIVE_INTEGER);            
        $queryValidator = $this->createValidator(\Nethgui\System\PlatformInterface::ANYTHING);

        if ( ! $offsetValidator->evaluate($this->offset)) {
            $report->addValidationError($this, 'o', $offsetValidator);
        }

        if ( ! $queryValidator->evaluate($this->query)) {
            $report->addValidationError($this, 'q', $queryValidator);
        }
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view['logFile'] = '';
        
        if ($this->getRequest()->isValidated()) {
            $view['logFile'] = $this->logFile;
            $args = array($this->logFile);

            if($this->offset) {
                array_unshift($args, '-o', $this->offset);
            }

            if($this->query) {
                array_unshift($args, '-p', $this->query);
            }

            if($this->isRegexp) {
                array_unshift($args, '-r');
            }

            $command = $this->prepareCommand('/usr/bin/sudo /sbin/e-smith/logviewer', $args);

            //$this->getLog()->notice($command);

            $view->getCommandList('/Main')->setDecoratorTemplate(function (\Nethgui\View\ViewInterface $view) use ($command) {
                    // Discard any output buffer:
                    while (ob_get_level() > 0) {
                        ob_end_clean();
                    }
                    header(sprintf('Content-Type: %s', 'text/plain; charset=UTF-8'));
                    passthru($command);
                    exit(0);
                });
        } 
    }

    private function prepareCommand($cmd, $args = array())
    {
        return escapeshellcmd($cmd) . ' ' . join(' ', array_map('escapeshellarg', $args));
    }

}