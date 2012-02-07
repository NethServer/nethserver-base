<?php
namespace Test\Unit\NethServer\Module\User;
class ChangePasswordTest extends \Test\Tool\ModuleTestCase
{

    /**
     * @var NethServer\Module\User_ChangePassword
     */
    protected $module;

    /**
     *
     * @var Test\Tool\ModuleTestEnvironment
     */
    protected $env;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $stashMock = $this->getMockBuilder('NethServer\Module\User\PasswordStash')
            ->setMethods(array('store', 'getFilePath'))
            ->getMock();

        $stashMock->expects($this->once())
            ->method('store')
            ->with('password')
            ->will($this->returnValue(NULL));

        $stashMock->expects($this->once())
            ->method('getFilePath')
            ->will($this->returnValue('/file/path'));

        $this->env = new \Test\Tool\ModuleTestEnvironment();

        $this->module = new \NethServer\Module\User\ChangePassword($stashMock, 'change-password');
    }

    public function testChangePassword1()
    {
        $request = array(
            'newPassword' => 'password',
            'confirmPassword' => 'password',
        );

        $this->env
            ->setRequest($request)
            ->setArguments(array('username'))
            ->setEvents(array(array('password-modify', array('username', '/file/path'))))
        ;

        $this->runModuleTest($this->module, $this->env);
    }

}

