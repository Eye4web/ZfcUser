<?php

namespace ZfcUserTest\Form;

use ZfcUser\Form\ChangePasswordFilter as Filter;

class ChangePasswordFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $options = $this->getMock('ZfcUser\Options\ModuleOptions');
        $options->expects($this->once())
                ->method('getAuthIdentityFields')
                ->will($this->returnValue(array('email')));

        $filter = new Filter($options);

        $inputs = $filter->getInputs();
        $this->assertArrayHasKey('identity', $inputs);
        $this->assertArrayHasKey('credential', $inputs);
        $this->assertArrayHasKey('newCredential', $inputs);
        $this->assertArrayHasKey('newCredentialVerify', $inputs);

        $this->assertEquals(0, $inputs['identity']->getValidatorChain()->count());
    }

    /**
     * @dataProvider providerTestConstructIdentityEmail
     */
    public function testConstructIdentityEmail($onlyEmail)
    {
        $options = $this->getMock('ZfcUser\Options\ModuleOptions');
        $options->expects($this->once())
                ->method('getAuthIdentityFields')
                ->will($this->returnValue($onlyEmail ? array('email') : array()));

        $filter = new Filter($options);

        $inputs = $filter->getInputs();
        $this->assertArrayHasKey('identity', $inputs);
        $this->assertArrayHasKey('credential', $inputs);
        $this->assertArrayHasKey('newCredential', $inputs);
        $this->assertArrayHasKey('newCredentialVerify', $inputs);

        $identity = $inputs['identity'];

        if ($onlyEmail === false) {
            $this->assertEquals(0, $inputs['identity']->getValidatorChain()->count());
        } else {
            // @todo remove this test skip if #383 is fixed
            if ($identity instanceof \Zend\InputFilter\Input && $identity->getValidatorChain()->count() == 0) {
                $this->markTestSkipped("currently we have a bug in this validator, pls fix #383");
            }

            // test email as identity
            $validators = $identity->getValidatorChain()->getValidators();
            $this->assertArrayHasKey('instance', $validators[0]);
            $this->assertInstanceOf('\Zend\Validator\EmailAddress', $validators[0]['instance']);
        }
    }

    public function providerTestConstructIdentityEmail()
    {
        return array(
            array(true),
            array(false)
        );
    }
}
