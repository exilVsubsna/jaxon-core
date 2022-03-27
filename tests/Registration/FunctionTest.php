<?php

namespace Jaxon\Tests\Registration;

require_once __DIR__ . '/../defs/functions.php';

use Jaxon\Jaxon;
use Jaxon\Request\Plugin\CallableFunction\CallableFunction;
use Jaxon\Request\Plugin\CallableFunction\CallableFunctionPlugin;
use Jaxon\Exception\SetupException;
use Jaxon\Utils\Http\UriException;
use PHPUnit\Framework\TestCase;
use Sample;

use function strlen;
use function file_get_contents;
use function jaxon;

final class FunctionTest extends TestCase
{
    /**
     * @var CallableFunctionPlugin
     */
    protected $xPlugin;

    public function setUp(): void
    {
        jaxon()->setOption('core.prefix.function', 'jxn_');
        // Register a function
        jaxon()->register(Jaxon::CALLABLE_FUNCTION, 'my_first_function',
            __DIR__ . '/../defs/first.php');
        // Register a function with an alias
        jaxon()->register(Jaxon::CALLABLE_FUNCTION, 'my_second_function', [
            'alias' => 'my_alias_function',
            'upload' => "'html_field_id'",
        ]);
        // Register a class method as a function
        jaxon()->register(Jaxon::CALLABLE_FUNCTION, 'myMethod', [
            'alias' => 'my_third_function',
            'class' => Sample::class,
            'include' => __DIR__ . '/../defs/classes.php',
        ]);

        $this->xPlugin = jaxon()->di()->getCallableFunctionPlugin();
    }

    public function tearDown(): void
    {
        jaxon()->reset();
        parent::tearDown();
    }

    public function testPluginName()
    {
        $this->assertEquals(Jaxon::CALLABLE_FUNCTION, $this->xPlugin->getName());
    }

    public function testPHPFunction()
    {
        // No callable for standard PHP functions.
        $this->assertEquals(null, $this->xPlugin->getCallable('file_get_contents'));
    }

    public function testNonCallableFunction()
    {
        // No callable for aliased functions.
        $this->assertEquals(null, $this->xPlugin->getCallable('my_second_function'));
    }

    public function testCallableFunctionClass()
    {
        $xFirstCallable = $this->xPlugin->getCallable('my_first_function');
        $xAliasCallable = $this->xPlugin->getCallable('my_alias_function');
        $xThirdCallable = $this->xPlugin->getCallable('my_third_function');
        // Test callables classes
        $this->assertEquals(CallableFunction::class, get_class($xFirstCallable));
        $this->assertEquals(CallableFunction::class, get_class($xAliasCallable));
        $this->assertEquals(CallableFunction::class, get_class($xThirdCallable));
    }

    public function testCallableFunctionName()
    {
        $xFirstCallable = $this->xPlugin->getCallable('my_first_function');
        $xAliasCallable = $this->xPlugin->getCallable('my_alias_function');
        $xThirdCallable = $this->xPlugin->getCallable('my_third_function');
        // Test callables classes
        $this->assertEquals('my_first_function', $xFirstCallable->getName());
        $this->assertEquals('my_alias_function', $xAliasCallable->getName());
        $this->assertEquals('my_third_function', $xThirdCallable->getName());
    }

    public function testCallableFunctionJsName()
    {
        $xFirstCallable = $this->xPlugin->getCallable('my_first_function');
        $xAliasCallable = $this->xPlugin->getCallable('my_alias_function');
        $xThirdCallable = $this->xPlugin->getCallable('my_third_function');
        // Test callables classes
        $this->assertEquals('jxn_my_first_function', $xFirstCallable->getJsName());
        $this->assertEquals('jxn_my_alias_function', $xAliasCallable->getJsName());
        $this->assertEquals('jxn_my_third_function', $xThirdCallable->getJsName());
    }

    public function testCallableFunctionOptions()
    {
        $xFirstCallable = $this->xPlugin->getCallable('my_first_function');
        $xAliasCallable = $this->xPlugin->getCallable('my_alias_function');
        $xThirdCallable = $this->xPlugin->getCallable('my_third_function');
        // Test callables classes
        $this->assertCount(0, $xFirstCallable->getOptions());
        $this->assertCount(1, $xAliasCallable->getOptions());
        $this->assertCount(0, $xThirdCallable->getOptions());
    }

    public function testCallableFunctionJsCode()
    {
        $this->assertEquals(32, strlen($this->xPlugin->getHash()));
        // $this->assertEquals('34608e208fda374f8761041969acf96e', $this->xPlugin->getHash());
        $this->assertEquals(403, strlen($this->xPlugin->getScript()));
        // $this->assertEquals(file_get_contents(__DIR__ . '/../script/function.js'), $this->xPlugin->getScript());
    }

    /**
     * @throws UriException
     */
    public function testLibraryJsCode()
    {
        // This URI will be parsed by the URI detector
        $_SERVER['REQUEST_URI'] = 'http://example.test/path';
        $sJsCode = jaxon()->getScript(true, false);
        $this->assertEquals(1359, strlen($sJsCode));
        // $this->assertEquals(file_get_contents(__DIR__ . '/../script/lib.js'), $sJsCode);
        unset($_SERVER['REQUEST_URI']);
    }

    public function testCallableFunctionIncorrectName()
    {
        // Register a function with incorrect name
        $this->expectException(SetupException::class);
        jaxon()->register(Jaxon::CALLABLE_FUNCTION, 'my function');
    }

    public function testCallableFunctionIncorrectOption()
    {
        // Register a function with incorrect option
        $this->expectException(SetupException::class);
        jaxon()->register(Jaxon::CALLABLE_FUNCTION, 'my_function', true);
    }
}
