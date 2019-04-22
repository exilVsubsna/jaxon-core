<?php
namespace Jaxon\Tests\CallableClass\Request;

use Jaxon\Jaxon;
use PHPUnit\Framework\TestCase;

/**
 * @covers Jaxon\Request
 */
final class RequestTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        jaxon()->register(Jaxon::CALLABLE_OBJECT, 'Test');
    }

    public function testRequestToJaxonClass()
    {
        $this->assertEquals(
            "JaxonTest.method()",
            rq('Test')->call('method')->getScript()
        );
    }

    public function testRequestToJaxonClassWithParameter()
    {
        $this->assertEquals(
            "JaxonTest.method('string', 2, true)",
            rq('Test')->call('method', 'string', 2, true)->getScript()
        );
    }

    public function testRequestToJaxonClassWithFormParameter()
    {
        $this->assertEquals(
            "JaxonTest.method(jaxon.getFormValues('elt_id'))",
            rq('Test')->call('method', rq()->form('elt_id'))->getScript()
        );
    }

    public function testRequestToJaxonClassWithInputParameter()
    {
        $this->assertEquals(
            "JaxonTest.method(jaxon.$('elt_id').value)",
            rq('Test')->call('method', rq()->input('elt_id'))->getScript()
        );
    }

    public function testRequestToJaxonClassWithCheckedParameter()
    {
        $this->assertEquals(
            "JaxonTest.method(jaxon.$('check_id').checked)",
            rq('Test')->call('method', rq()->checked('check_id'))->getScript()
        );
    }

    public function testRequestToJaxonClassWithSelectParameter()
    {
        $this->assertEquals(
            "JaxonTest.method(jaxon.$('select_id').value)",
            rq('Test')->call('method', rq()->select('select_id'))->getScript()
        );
    }

    public function testRequestToJaxonClassWithInnerHTMLParameter()
    {
        $this->assertEquals(
            "JaxonTest.method(jaxon.$('elt_id').innerHTML)",
            rq('Test')->call('method', rq()->html('elt_id'))->getScript()
        );
    }

    public function testRequestToJaxonClassWithMultipleParameter()
    {
        $this->assertEquals(
            "JaxonTest.method(jaxon.$('check_id').checked, jaxon.$('select_id').value, jaxon.$('elt_id').innerHTML)",
            rq('Test')->call('method', rq()->checked('check_id'), rq()->select('select_id'), rq()->html('elt_id'))->getScript()
        );
    }

    public function testRequestToJaxonClassWithConfirmation()
    {
        $this->assertEquals(
            "if(confirm('Really?')){JaxonTest.method(jaxon.$('elt_id').innerHTML);}",
            rq('Test')->call('method', rq()->html('elt_id'))->confirm("Really?")->getScript()
        );
    }

    public function testRequestToJaxonClassWithConfirmationAndSubstitution()
    {
         $this->assertEquals(
            "if(confirm('Really M. {1}?'.supplant({'1':jaxon.$('name_id').innerHTML}))){JaxonTest.method(jaxon.$('elt_id').innerHTML);}",
            rq('Test')->call('method', rq()->html('elt_id'))->confirm("Really M. {1}?", rq()->html('name_id'))->getScript()
        );
    }

    public function testRequestToJaxonClassWithConditionWhen()
    {
        $this->assertEquals(
            "if(jaxon.$('cond_id').checked){JaxonTest.method(jaxon.$('elt_id').innerHTML);}",
            rq('Test')->call('method', rq()->html('elt_id'))->when(rq()->checked('cond_id'))->getScript()
        );
    }

    public function testRequestToJaxonClassWithConditionWhenAndMessage()
    {
        $this->assertEquals(
            "if(jaxon.$('cond_id').checked){JaxonTest.method(jaxon.$('elt_id').innerHTML);}" .
                "else{alert('Please check the option');}",
            rq('Test')->call('method', rq()->html('elt_id'))
                ->when(rq()->checked('cond_id'))
                ->elseShow("Please check the option")->getScript()
        );
    }

    public function testRequestToJaxonClassWithConditionWhenAndMessageAndSubstitution()
    {
        $this->assertEquals(
            "if(jaxon.$('cond_id').checked){JaxonTest.method(jaxon.$('elt_id').innerHTML);}else" .
                "{alert('M. {1}, please check the option'.supplant({'1':jaxon.$('name_id').innerHTML}));}",
            rq('Test')->call('method', rq()->html('elt_id'))
                ->when(rq()->checked('cond_id'))
                ->elseShow("M. {1}, please check the option", rq()->html('name_id'))->getScript()
        );
    }

    public function testRequestToJaxonClassWithConditionUnless()
    {
         $this->assertEquals(
            "if(!(jaxon.$('cond_id').checked)){JaxonTest.method(jaxon.$('elt_id').innerHTML);}",
            rq('Test')->call('method', rq()->html('elt_id'))
                ->unless(rq()->checked('cond_id'))->getScript()
        );
    }

    public function testRequestToJaxonClassWithConditionUnlessAndMessage()
    {
         $this->assertEquals(
            "if(!(jaxon.$('cond_id').checked)){JaxonTest.method(jaxon.$('elt_id').innerHTML);}" .
                "else{alert('Please uncheck the option');}",
            rq('Test')->call('method', rq()->html('elt_id'))
                ->unless(rq()->checked('cond_id'))
                ->elseShow("Please uncheck the option")->getScript()
        );
    }

    public function testRequestToJaxonClassWithConditionUnlessAndMessageAndSubstitution()
    {
         $this->assertEquals(
            "if(!(jaxon.$('cond_id').checked)){JaxonTest.method(jaxon.$('elt_id').innerHTML);}" .
                "else{alert('M. {1}, please uncheck the option'.supplant({'1':jaxon.$('name_id').innerHTML}));}",
            rq('Test')->call('method', rq()->html('elt_id'))
                ->unless(rq()->checked('cond_id'))
                ->elseShow("M. {1}, please uncheck the option", rq()->html('name_id'))->getScript()
        );
    }
}
