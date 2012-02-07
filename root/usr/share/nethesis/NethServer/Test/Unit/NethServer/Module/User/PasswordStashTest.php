<?php
namespace Test\Unit\NethServer\Module\User;
class PasswordStashTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var NethServer\Module\User\PasswordStash
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new \NethServer\Module\User\PasswordStash;
    }


    public function testStore()
    {
        $tmpPath = sys_get_temp_dir();
        $this->object->store('password');
        $this->assertEquals('password', file_get_contents($this->object->getFilePath()));
    }


}

