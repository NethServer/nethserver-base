<?php
namespace Test\Unit\NethServer\Module;
class UserTest extends \Test\Tool\ModuleTestCase
{

    /**
     * @var NethServer\Module\User
     */
    protected $module;

    /**
     * @var Test\Tool\ModuleTestEnvironment
     */
    protected $env;

    protected function setUp()
    {
        $this->module = new \NethServer\Module\User;
        $this->env = new \Test\Tool\ModuleTestEnvironment();

        $ldapKey = array(
            'defaultCity' => 'Pesaro',
            'defaultPhoneNumber' => '1234',
            'defaultDepartment' => 'Sviluppo',
            'defaultCompany' => 'Nethesis',
            'defaultStreet' => 'Via degli Olmi'
        );

        $groups = array(
            'g1' => array(
                'type' => 'group',
                'Description' => 'Group1',
                'Members' => 'u1,u2,u3'
            ),
            'g2' => array(
                'type' => 'group',
                'Description' => 'Group2',
                'Members' => 'u3,u2'
            ),
        );

        $users = array(
            'u1' => array(
                'type' => 'user',
                'FirstName' => 'Utente1',
                'LastName' => 'Test1',
            ),
            'u2' => array(
                'type' => 'user',
                'FirstName' => 'Utente2',
                'LastName' => 'Test2',
            ),
            'u3' => array(
                'type' => 'user',
                'FirstName' => 'Utente3',
                'LastName' => 'Test3',
            ),
        );

        $cs = new \Test\Tool\DB();
        $cs->set($cs::getKey('ldap'), $ldapKey);

        $this->env->setDatabase('configuration', $cs);

        $ac = new \Test\Tool\DB();
        $ac->set($ac::getAll('group'), $groups);
        $ac->set($ac::getProp('g1', 'Members'), $groups['g1']['Members']);
        $ac->set($ac::getProp('g2', 'Members'), $groups['g2']['Members']);
        $ac->set($ac::getAll('user'), $users);

        $this->env->setDatabase('accounts', $ac);
    }

    public function testCreate1()
    {
        $expectedView = array(
            'username' => '',
            'FirstName' => '',
            'LastName' => '',
        );

        $this->env->getDatabase('configuration')->setFinal();
        $this->env->getDatabase('accounts')->setFinal();

        $this->env
            ->setArguments(array('create'))
            ->setView($expectedView)
            ->setCommand('|^/usr/bin/id|', 1);
        ;

        $this->runModuleTest($this->module, $this->env);
    }

    public function testCreate2()
    {
        $request = array(
            'create' => array(
                'username' => 'nu',
                'FirstName' => 'New',
                'LastName' => 'User',
                'Groups' => array('g1', 'g2')
            )
        );

        $this->env
            ->setRequest($request)
            ->setArguments(array('create'))
            ->setEvents(array(array('user-create', array('nu'))))
            ->setCommand('|^/usr/bin/id|', 1);
        ;

        $this->env->getDatabase('configuration')->setFinal();
        $ac = $this->env->getDatabase('accounts');

        $ac
            ->transition($ac::setProp('g1', 'Members', 'u1,u2,u3,nu'), TRUE)
            ->transition($ac::setProp('g2', 'Members', 'u3,u2,nu'), TRUE)
            ->transition($ac::setKey('nu', 'user', array('FirstName' => 'New')), TRUE)
            ->transition($ac::setProp('nu', array('FirstName' => 'New', 'LastName' => 'User')), TRUE)
            ->setFinal();

        $this->runModuleTest($this->module, $this->env);
    }

    public function testModify1()
    {
        $request = array(
            'update' => array(
                'username' => 'u1',
                'FirstName' => 'USER',
                'LastName' => 'ONE',
                'Groups' => array('g1', 'g2')
            )
        );

        $this->env
            ->setRequest($request)
            ->setArguments(array('update'))
            ->setEvents(array(array('user-modify', array('u1'))))
            ->setCommand('|^/usr/bin/id|', 0);
        ;

        $this->env->getDatabase('configuration')->setFinal();
        $ac = $this->env->getDatabase('accounts');

        $ac
            ->transition($ac::setProp('g2', 'Members', 'u3,u2,u1'), TRUE)
            ->transition($ac::setProp('u1', array('FirstName' => 'USER', 'LastName' => 'Test1')), TRUE)
            ->transition($ac::setProp('u1', array('FirstName' => 'USER', 'LastName' => 'ONE')), TRUE)
            ->setFinal();

        $this->runModuleTest($this->module, $this->env);
    }

    public function testDelete1()
    {
        $request = array(
            'delete' => array(
                'username' => 'u1',
            )
        );

        $this->env
            ->setRequest($request)
            ->setArguments(array('delete'))
            ->setEvents(array(array('user-delete', array('u1'))))
            ->setCommand('|^/usr/bin/id|', 0);
        ;

        $this->env->getDatabase('configuration')->setFinal();

        $ac = $this->env->getDatabase('accounts');
        $ac
            ->transition($ac::setType('u1', 'user-deleted'), TRUE)
            ->transition($ac::deleteKey('u1'), TRUE)
            //->transition($ac::setProp('g1', 'Members', 'u2,u3'), TRUE)
            ->setFinal();


        $this->runModuleTest($this->module, $this->env);
    }
}

