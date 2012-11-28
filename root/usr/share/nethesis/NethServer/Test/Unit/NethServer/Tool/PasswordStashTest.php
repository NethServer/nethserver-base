<?php
namespace Test\Unit\NethServer\Tool;

class PasswordStashTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NethServer\Tool\PasswordStash
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new \NethServer\Tool\PasswordStash;
    }

    public function testStore()
    {
        $tmpPath = sys_get_temp_dir();
        $this->object->store('password');
        $this->assertEquals('password', file_get_contents($this->object->getFilePath()));
    }

}
