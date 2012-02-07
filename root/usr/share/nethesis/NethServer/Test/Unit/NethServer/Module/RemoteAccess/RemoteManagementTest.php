<?php
namespace Test\Unit\NethServer\Module\RemoteAccess;
class RemoteManagementTest extends \Test\Tool\ModuleTestCase
{

    protected function setUp()
    {
        $this->object = new \NethServer\Module\RemoteAccess\RemoteManagement();
    }

    public function testNoInput()
    {
        $env = new \Test\Tool\ModuleTestEnvironment();

        $cs = new \Test\Tool\MockState();
        $cs->set(\Test\Tool\DB::getProp('httpd-admin', 'ValidFrom'), '192.168.1.0/255.255.255.0,10.0.0.0/255.128.0.0');
        $cs->setFinal();

        $env->setDatabase('configuration', $cs);
        $this->runModuleTest($this->object, $env);
    }

    public function testCreate()
    {
        $env = new \Test\Tool\ModuleTestEnvironment();

        $env->setArguments(array('create'));
        $env->setRequest(array(
            'create' => array(
                'address' => '10.111.111.131',
                'mask' => '255.0.0.0'
            )
        ));

        $cs = new \Test\Tool\MockState();
        $cs->set(\Test\Tool\DB::getProp('httpd-admin', 'ValidFrom'), '192.168.1.0/255.255.255.0,10.0.0.0/255.128.0.0')
            ->transition(\Test\Tool\DB::setProp('httpd-admin', 'ValidFrom', '192.168.1.0/255.255.255.0,10.0.0.0/255.128.0.0,10.111.111.131/255.0.0.0'), TRUE)
            ->setFinal();

        $env->setDatabase('configuration', $cs);

        $env->setEvents(array('remoteaccess-update'));

        $this->runModuleTest($this->object, $env);
    }

    public function testDelete()
    {
        $env = new \Test\Tool\ModuleTestEnvironment();

        $env->setArguments(array('delete'));
        $env->setRequest(array(
            'delete' => array(
                'address' => '10.0.0.0'
            )
        ));

        $cs = new \Test\Tool\MockState();
        $cs->set(\Test\Tool\DB::getProp('httpd-admin', 'ValidFrom'), '192.168.1.0/255.255.255.0,10.0.0.0/255.128.0.0')
            ->transition(\Test\Tool\DB::setProp('httpd-admin', 'ValidFrom', '192.168.1.0/255.255.255.0'), TRUE)
            ->setFinal();

        $env->setDatabase('configuration', $cs);

        $env->setEvents(array('remoteaccess-update'));

        $this->runModuleTest($this->object, $env);
    }

}

