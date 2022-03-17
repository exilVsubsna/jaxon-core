<?php

/**
 * CallableClassPlugin.php - Jaxon callable class plugin
 *
 * This class registers user defined callable classes, generates client side javascript code,
 * and calls their methods on user request
 *
 * @package jaxon-core
 * @author Jared White
 * @author J. Max Wilson
 * @author Joseph Woolley
 * @author Steffen Konerow
 * @author Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @copyright Copyright (c) 2005-2007 by Jared White & J. Max Wilson
 * @copyright Copyright (c) 2008-2010 by Joseph Woolley, Steffen Konerow, Jared White  & J. Max Wilson
 * @copyright 2016 Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link https://github.com/jaxon-php/jaxon-core
 */

namespace Jaxon\Request\Plugin\CallableClass;

use Jaxon\Jaxon;
use Jaxon\CallableClass;
use Jaxon\Plugin\RequestPlugin;
use Jaxon\Request\Handler\RequestHandler;
use Jaxon\Request\Target;
use Jaxon\Request\Validator;
use Jaxon\Response\ResponseManager;
use Jaxon\Utils\Config\Config;
use Jaxon\Utils\Template\Engine as TemplateEngine;
use Jaxon\Utils\Translation\Translator;
use Jaxon\Exception\RequestException;
use Jaxon\Exception\SetupException;

use ReflectionClass;
use ReflectionMethod;
use ReflectionException;

use function is_array;
use function is_string;
use function is_subclass_of;
use function md5;
use function strlen;
use function trim;
use function uksort;

class CallableClassPlugin extends RequestPlugin
{
    /**
     * @var Config
     */
    protected $xConfig;

    /**
     * The request handler
     *
     * @var RequestHandler
     */
    protected $xRequestHandler;

    /**
     * The response manager
     *
     * @var ResponseManager
     */
    protected $xResponseManager;

    /**
     * The callable registry
     *
     * @var CallableRegistry
     */
    protected $xRegistry;

    /**
     * The callable repository
     *
     * @var CallableRepository
     */
    protected $xRepository;

    /**
     * The request data validator
     *
     * @var Validator
     */
    protected $xValidator;

    /**
     * @var TemplateEngine
     */
    protected $xTemplateEngine;

    /**
     * @var Translator
     */
    protected $xTranslator;

    /**
     * The value of the class parameter of the incoming Jaxon request
     *
     * @var string
     */
    protected $sRequestedClass = '';

    /**
     * The value of the method parameter of the incoming Jaxon request
     *
     * @var string
     */
    protected $sRequestedMethod = '';

    /**
     * The methods that must not be exported to js
     *
     * @var array
     */
    protected $aProtectedMethods = [];

    /**
     * The class constructor
     *
     * @param Config  $xConfig
     * @param RequestHandler  $xRequestHandler
     * @param ResponseManager  $xResponseManager
     * @param CallableRegistry $xRegistry    The callable class registry
     * @param CallableRepository $xRepository    The callable object repository
     * @param TemplateEngine  $xTemplateEngine
     * @param Translator  $xTranslator
     * @param Validator  $xValidator
     */
    public function __construct(Config $xConfig, RequestHandler $xRequestHandler,
        ResponseManager $xResponseManager, CallableRegistry $xRegistry, CallableRepository $xRepository,
        TemplateEngine  $xTemplateEngine, Translator $xTranslator, Validator $xValidator)
    {
        $this->xConfig = $xConfig;
        $this->xRequestHandler = $xRequestHandler;
        $this->xResponseManager = $xResponseManager;
        $this->xRegistry = $xRegistry;
        $this->xRepository = $xRepository;
        $this->xTemplateEngine = $xTemplateEngine;
        $this->xTranslator = $xTranslator;
        $this->xValidator = $xValidator;

        if(isset($_GET['jxncls']))
        {
            $this->sRequestedClass = trim($_GET['jxncls']);
        }
        if(isset($_GET['jxnmthd']))
        {
            $this->sRequestedMethod = trim($_GET['jxnmthd']);
        }
        if(isset($_POST['jxncls']))
        {
            $this->sRequestedClass = trim($_POST['jxncls']);
        }
        if(isset($_POST['jxnmthd']))
        {
            $this->sRequestedMethod = trim($_POST['jxnmthd']);
        }

        // The methods of the CallableClass class must not be exported
        $xCallableClass = new ReflectionClass(CallableClass::class);
        foreach($xCallableClass->getMethods(ReflectionMethod::IS_PUBLIC) as $xMethod)
        {
            $this->aProtectedMethods[] = $xMethod->getName();
        }
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return Jaxon::CALLABLE_CLASS;
    }

    /**
     * @inheritDoc
     */
    public function getTarget(): ?Target
    {
        if(!$this->sRequestedClass || !$this->sRequestedMethod)
        {
            return null;
        }
        return Target::makeClass($this->sRequestedClass, $this->sRequestedMethod);
    }

    /**
     * @inheritDoc
     * @throws SetupException
     */
    public function checkOptions(string $sCallable, $xOptions): array
    {
        if(!$this->xValidator->validateClass(trim($sCallable)))
        {
            throw new SetupException($this->xTranslator->trans('errors.objects.invalid-declaration'));
        }
        if(is_string($xOptions))
        {
            $xOptions = ['include' => $xOptions];
        }
        elseif(!is_array($xOptions))
        {
            throw new SetupException($this->xTranslator->trans('errors.objects.invalid-declaration'));
        }
        return $xOptions;
    }

    /**
     * Register a callable class
     *
     * @param string $sType    The type of request handler being registered
     * @param string $sCallable    The name of the class being registered
     * @param array $aOptions    The associated options
     *
     * @return bool
     */
    public function register(string $sType, string $sCallable, array $aOptions): bool
    {
        $sClassName = trim($sCallable);
        $this->xRepository->addClass($sClassName, $aOptions);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getHash(): string
    {
        $this->xRegistry->parseCallableClasses();
        $aNamespaces = $this->xRepository->getNamespaces();
        $aClasses = $this->xRepository->getClasses();
        $sHash = '';

        foreach($aNamespaces as $sNamespace => $aOptions)
        {
            $sHash .= $sNamespace . $aOptions['separator'];
        }
        foreach($aClasses as $sClassName => $aOptions)
        {
            $sHash .= $sClassName . $aOptions['timestamp'];
        }

        return md5($sHash);
    }

    /**
     * Generate client side javascript code for namespaces
     *
     * @return string
     */
    private function getNamespacesScript(): string
    {
        $sCode = '';
        $sPrefix = $this->xConfig->getOption('core.prefix.class');
        $aJsClasses = [];
        $aNamespaces = array_keys($this->xRepository->getNamespaces());
        foreach($aNamespaces as $sNamespace)
        {
            $offset = 0;
            $sJsNamespace = str_replace('\\', '.', $sNamespace);
            $sJsNamespace .= '.Null'; // This is a sentinel. The last token is not processed in the while loop.
            while(($dotPosition = strpos($sJsNamespace, '.', $offset)) !== false)
            {
                $sJsClass = substr($sJsNamespace, 0, $dotPosition);
                // Generate code for this object
                if(!isset($aJsClasses[$sJsClass]))
                {
                    $sCode .= "$sPrefix$sJsClass = {};\n";
                    $aJsClasses[$sJsClass] = $sJsClass;
                }
                $offset = $dotPosition + 1;
            }
        }
        return $sCode;
    }

    /**
     * Generate client side javascript code for a callable class
     *
     * @param string $sClassName
     * @param CallableObject $xCallableObject The corresponding callable object
     *
     * @return string
     */
    private function getCallableScript(string $sClassName, CallableObject $xCallableObject): string
    {
        $aProtectedMethods = is_subclass_of($sClassName, CallableClass::class) ? $this->aProtectedMethods : [];
        return $this->xTemplateEngine->render('jaxon::callables/object.js', [
            'sPrefix' => $this->xConfig->getOption('core.prefix.class'),
            'sClass' => $xCallableObject->getJsName(),
            'aMethods' => $xCallableObject->getMethods($aProtectedMethods),
        ]);
    }

    /**
     * Generate client side javascript code for the registered callable objects
     *
     * @return string
     * @throws SetupException
     */
    public function getScript(): string
    {
        $this->xRegistry->registerCallableClasses();

        $sCode = $this->getNamespacesScript();

        $aClassNames = $this->xRepository->getClassNames();
        // Sort the options by key length asc
        uksort($aClassNames, function($name1, $name2) {
            return strlen($name1) - strlen($name2);
        });
        foreach($aClassNames as $sClassName)
        {
            $xCallableObject = $this->xRegistry->getCallableObject($sClassName);
            $sCode .= $this->getCallableScript($sClassName, $xCallableObject);
        }

        return $sCode;
    }

    /**
     * @inheritDoc
     */
    public function canProcessRequest(): bool
    {
        // Check the validity of the class name
        if(($this->sRequestedClass !== null && !$this->xValidator->validateClass($this->sRequestedClass)) ||
            ($this->sRequestedMethod !== null && !$this->xValidator->validateMethod($this->sRequestedMethod)))
        {
            $this->sRequestedClass = null;
            $this->sRequestedMethod = null;
        }
        return ($this->sRequestedClass !== null && $this->sRequestedMethod !== null);
    }

    /**
     * @inheritDoc
     * @throws RequestException
     * @throws SetupException
     */
    public function processRequest(): bool
    {
        // Find the requested method
        $xCallableObject = $this->xRegistry->getCallableObject($this->sRequestedClass);
        if(!$xCallableObject || !$xCallableObject->hasMethod($this->sRequestedMethod))
        {
            // Unable to find the requested object or method
            throw new RequestException($this->xTranslator->trans('errors.objects.invalid',
                ['class' => $this->sRequestedClass, 'method' => $this->sRequestedMethod]));
        }

        // Call the requested method
        $aArgs = $this->xRequestHandler->processArguments();
        try
        {
            $xResponse = $xCallableObject->call($this->sRequestedMethod, $aArgs);
            if(($xResponse))
            {
                $this->xResponseManager->append($xResponse);
            }
        }
        catch(ReflectionException $e)
        {
            // Unable to find the requested class
            throw new RequestException($this->xTranslator->trans('errors.objects.invalid',
                ['class' => $this->sRequestedClass, 'method' => $this->sRequestedMethod]));
        }
        return true;
    }
}