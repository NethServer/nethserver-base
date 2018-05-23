<?php

namespace NethServer\Module;

use Nethgui\System\PlatformInterface as Validate;

/**
 * @author Stephane de Labrusse <stephdl@de-labrusse.fr> 2017
 */
class YumCron extends \Nethgui\Controller\Collection\AbstractAction //implements \Nethgui\Component\DependencyConsumer
{


    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $attributes)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($attributes, 'Administration', 16);
    }


    public function initialize()
    {
        parent::initialize();
        $validation = $this->createValidator()->memberOf('yes','no');

        $this->declareParameter('messages', $validation, array('configuration', 'yum-cron', 'messages'));
        $this->declareParameter('download', $validation, array('configuration', 'yum-cron', 'download'));
        $this->declareParameter('applyUpdate', $validation, array('configuration', 'yum-cron', 'applyUpdate'));
        $this->declareParameter('customMail', Validate::ANYTHING, array('configuration', 'yum-cron', 'customMail'));
        $this->declareParameter('status', Validate::SERVICESTATUS, array('configuration', 'yum-cron', 'status'));
    }


    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);
        if($request->isMutation() && $request->hasParameter('customMail')) {
            $this->parameters['customMail'] = implode(",", self::splitLines($request->getParameter('customMail')));
        }
    }


    public static function splitLines($text)
    {
        return array_filter(preg_split("/[,;\s]+/", $text));
    }


    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);
            $forwards = $this->parameters['customMail'];
            if($forwards) {
                $emailValidator = $this->createValidator(Validate::EMAIL);
                foreach(explode(',', $forwards) as $email) {
                    if( !$emailValidator->evaluate($email)) {
                        $report->addValidationErrorMessage($this, 'customMail',
                            'valid_mailforward_address', array($email));
                    }
                }
            }
   }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);

        if(isset($this->parameters['customMail'])) {
            $view['customMail'] = implode("\r\n", explode(',', $this->parameters['customMail']));
        }
    }

    public function onParametersSaved($changes)
    {
        $this->getPlatform()->signalEvent('nethserver-yum-cron-save@post-process');
    }
}

