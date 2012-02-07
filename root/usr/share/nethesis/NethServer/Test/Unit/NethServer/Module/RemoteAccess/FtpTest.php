<?php
namespace Test\Unit\NethServer\Module\RemoteAccess;

use Test\Tool\DB;

class FtpTest extends \Test\Tool\ModuleTestCase
{

    /**
     * @var NethServer\Module\RemoteAccess_Ftp
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new \NethServer\Module\RemoteAccess\Ftp();
    }

    public function testNoParamsDisabledService()
    {
        $env = new \Test\Tool\ModuleTestEnvironment();

        $env->setView(array(
            'status' => 'disabled',
            'acceptPasswordFromAnyNetwork' => '',
            'statusOptions' => array('disabled', 'localNetwork', 'anyNetwork'),
        ));

        $cs = new \Test\Tool\MockState;
        $cs->set(DB::getProp('ftp', 'status'), 'disabled');
        $cs->set(DB::getProp('ftp', 'LoginAccess'), 'private');
        $cs->set(DB::getProp('ftp', 'access'), 'private');
        $cs->setFinal();
        $env->setDatabase('configuration', $cs);

        $this->runModuleTest($this->object, $env);
    }

    public function testEnablePrivateService()
    {
        $env = new \Test\Tool\ModuleTestEnvironment();
        $env->setRequest(array('status' => 'localNetwork'));

        $env->setView(array(
            'status' => 'localNetwork',
            'acceptPasswordFromAnyNetwork' => '',
        ));

        $cs = new \Test\Tool\MockState;
        $cs->set(DB::getProp('ftp', 'status'), 'disabled');
        $cs->set(DB::getProp('ftp', 'LoginAccess'), 'private');
        $cs->set(DB::getProp('ftp', 'access'), 'private');
        $cs->transition(DB::setProp('ftp', 'status', 'enabled'), TRUE)->setFinal();
        $env->setDatabase('configuration', $cs);

        $env->setEvents(array('remoteaccess-update'));

        $this->runModuleTest($this->object, $env);
    }

    public function testEnableNormalService()
    {

        $env = new \Test\Tool\ModuleTestEnvironment();
        $env->setRequest(array('status' => 'anyNetwork'));

        $env->setView(array(
            'status' => 'anyNetwork',
            'acceptPasswordFromAnyNetwork' => '',
        ));

        $cs = new \Test\Tool\MockState;
        $cs->set(DB::getProp('ftp', 'status'), 'disabled');
        $cs->set(DB::getProp('ftp', 'LoginAccess'), 'private');
        $cs->set(DB::getProp('ftp', 'access'), 'private');
        $cs->transition(DB::setProp('ftp', 'status', 'enabled'), TRUE)
            ->transition(DB::setProp('ftp', 'LoginAccess', 'public'), TRUE)
            ->setFinal();
        $env->setDatabase('configuration', $cs);

        $env->setEvents(array('remoteaccess-update'));

        $this->runModuleTest($this->object, $env);
    }

    public function testDisableService1()
    {
        $env = new \Test\Tool\ModuleTestEnvironment();
        $env->setRequest(array('status' => 'disabled'));

        $env->setView(array(
            'status' => 'disabled',
            'acceptPasswordFromAnyNetwork' => '1',
        ));

        $cs = new \Test\Tool\MockState;
        $cs->set(DB::getProp('ftp', 'status'), 'enabled');
        $cs->set(DB::getProp('ftp', 'LoginAccess'), 'public');
        $cs->set(DB::getProp('ftp', 'access'), 'public');
        $cs->transition(DB::setProp('ftp', 'status', 'disabled'), TRUE)
            ->transition(DB::setProp('ftp', 'LoginAccess', 'private'), TRUE)
            ->setFinal();
        $env->setDatabase('configuration', $cs);

        $env->setEvents(array('remoteaccess-update'));

        $this->runModuleTest($this->object, $env);

    }

    public function testDisableService2()
    {
        $env = new \Test\Tool\ModuleTestEnvironment();
        $env->setRequest(array('status' => 'disabled'));

        $env->setView(array(
            'status' => 'disabled',
            'acceptPasswordFromAnyNetwork' => '',
        ));

        $cs = new \Test\Tool\MockState;
        $cs->set(DB::getProp('ftp', 'status'), 'enabled');
        $cs->set(DB::getProp('ftp', 'LoginAccess'), 'private');
        $cs->set(DB::getProp('ftp', 'access'), 'private');
        $cs->transition(DB::setProp('ftp', 'status', 'disabled'), TRUE)
            ->setFinal();
        $env->setDatabase('configuration', $cs);

        $env->setEvents(array('remoteaccess-update'));

        $this->runModuleTest($this->object, $env);

    }

    public function testEnablePassword()
    {
        $env = new \Test\Tool\ModuleTestEnvironment();
        $env->setRequest(array('status' => 'anyNetwork', 'acceptPasswordFromAnyNetwork' => '1'));

        $env->setView(array(
            'status' => 'anyNetwork',
            'acceptPasswordFromAnyNetwork' => '1'
        ));

        $cs = new \Test\Tool\MockState;
        $cs->set(DB::getProp('ftp', 'status'), 'enabled');
        $cs->set(DB::getProp('ftp', 'LoginAccess'), 'public');
        $cs->set(DB::getProp('ftp', 'access'), 'private');
        $cs->transition(DB::setProp('ftp', 'access', 'public'), TRUE)
            ->setFinal();
        $env->setDatabase('configuration', $cs);

        $env->setEvents(array('remoteaccess-update'));

        $this->runModuleTest($this->object, $env);

    }

    public function testDisablePassword()
    {
        $env = new \Test\Tool\ModuleTestEnvironment();
        $env->setRequest(array('status' => 'anyNetwork', 'acceptPasswordFromAnyNetwork' => ''));

        $env->setView(array(
            'status' => 'anyNetwork',
            'acceptPasswordFromAnyNetwork' => ''
        ));

        $cs = new \Test\Tool\MockState;
        $cs->set(DB::getProp('ftp', 'status'), 'enabled');
        $cs->set(DB::getProp('ftp', 'LoginAccess'), 'public');
        $cs->set(DB::getProp('ftp', 'access'), 'public');
        $cs->transition(DB::setProp('ftp', 'access', 'private'), TRUE)
            ->setFinal();
        $env->setDatabase('configuration', $cs);

        $env->setEvents(array('remoteaccess-update'));

        $this->runModuleTest($this->object, $env);
    }

}

