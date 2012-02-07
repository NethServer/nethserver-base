<?php
namespace Test\Unit\NethServer\Module\RemoteAccess;
class SshTest extends \Test\Tool\ModuleTestCase
{

    /**
     * @var NethServer\Module\RemoteAccess_Ssh
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new \NethServer\Module\RemoteAccess\Ssh();
    }

    public function testNoParamsEnabledPublicService()
    {
        $env = new \Test\Tool\ModuleTestEnvironment();

        $env->setView(array(
            'status' => 'enabled',
            'port' => '22',
            'passwordAuth' => 'yes',
            'rootLogin' => 'yes',
            'access' => 'public'
        ));

        $cs = new \Test\Tool\MockState;
        $cs->set(\Test\Tool\DB::getProp('sshd', 'status'), 'enabled');
        $cs->set(\Test\Tool\DB::getProp('sshd', 'TCPPort'), '22');
        $cs->set(\Test\Tool\DB::getProp('sshd', 'PasswordAuthentication'), 'yes');
        $cs->set(\Test\Tool\DB::getProp('sshd', 'PermitRootLogin'), 'yes');
        $cs->set(\Test\Tool\DB::getProp('sshd', 'access'), 'public');
        $cs->setFinal();
        $env->setDatabase('configuration', $cs);

        $this->runModuleTest($this->object, $env);
    }

    public function testNoParamsDisabledPrivateService()
    {
        $env = new \Test\Tool\ModuleTestEnvironment();

        $env->setView(array(
            'status' => 'disabled',
            'port' => '22',
            'passwordAuth' => '',
            'rootLogin' => '',
            'access' => 'private'
        ));

        $cs = new \Test\Tool\MockState;
        $cs->set(\Test\Tool\DB::getProp('sshd', 'status'), 'disabled');
        $cs->set(\Test\Tool\DB::getProp('sshd', 'TCPPort'), '22');
        $cs->set(\Test\Tool\DB::getProp('sshd', 'PasswordAuthentication'), '');
        $cs->set(\Test\Tool\DB::getProp('sshd', 'PermitRootLogin'), '');
        $cs->set(\Test\Tool\DB::getProp('sshd', 'access'), 'private');
        $cs->setFinal();
        $env->setDatabase('configuration', $cs);

        $this->runModuleTest($this->object, $env);
    }

    public function testNoParamsEnableService()
    {
        $env = new \Test\Tool\ModuleTestEnvironment();

        $expectedView = $request = array(
            'status' => 'enabled',
            'port' => '23',
            'passwordAuth' => 'yes',
            'rootLogin' => 'yes',
            'access' => 'public'
        );

        $env->setRequest($request);

        $env->setView($expectedView);

        $env->setEvents(array('remoteaccess-update'));

        $cs = new \Test\Tool\MockState;
        $cs->set(\Test\Tool\DB::getProp('sshd', 'status'), 'disabled');
        $cs->set(\Test\Tool\DB::getProp('sshd', 'TCPPort'), '22');
        $cs->set(\Test\Tool\DB::getProp('sshd', 'PasswordAuthentication'), '');
        $cs->set(\Test\Tool\DB::getProp('sshd', 'PermitRootLogin'), '');
        $cs->set(\Test\Tool\DB::getProp('sshd', 'access'), 'private');

        $cs->transition(\Test\Tool\DB::setProp('sshd', 'status', 'enabled'), TRUE)
            ->transition(\Test\Tool\DB::setProp('sshd', 'TCPPort', '23'), TRUE)
            ->transition(\Test\Tool\DB::setProp('sshd', 'PasswordAuthentication', 'yes'), TRUE)
            ->transition(\Test\Tool\DB::setProp('sshd', 'PermitRootLogin', 'yes'), TRUE)
            ->transition(\Test\Tool\DB::setProp('sshd', 'access', 'public'), TRUE)
            ->setFinal();

        $env->setDatabase('configuration', $cs);

        $this->runModuleTest($this->object, $env);
    }

    public function testDisablePublicAccess()
    {
        $env = new \Test\Tool\ModuleTestEnvironment();

        $expectedView = $request = array(
            'status' => 'enabled',
            'port' => '22',
            'passwordAuth' => 'yes',
            'rootLogin' => 'yes',
            'access' => 'private'
        );

        $env->setRequest($request);

        $env->setView($expectedView);

        $env->setEvents(array('remoteaccess-update'));

        $cs = new \Test\Tool\MockState;
        $cs->set(\Test\Tool\DB::getProp('sshd', 'status'), 'enabled');
        $cs->set(\Test\Tool\DB::getProp('sshd', 'TCPPort'), '22');
        $cs->set(\Test\Tool\DB::getProp('sshd', 'PasswordAuthentication'), 'yes');
        $cs->set(\Test\Tool\DB::getProp('sshd', 'PermitRootLogin'), 'yes');
        $cs->set(\Test\Tool\DB::getProp('sshd', 'access'), 'public');

        $cs->transition(\Test\Tool\DB::setProp('sshd', 'access', 'private'), TRUE)
            ->setFinal();

        $env->setDatabase('configuration', $cs);

        $this->runModuleTest($this->object, $env);
    }

}
