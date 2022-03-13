<?php

/**
 * DirPlugin.php - Jaxon callable dir plugin
 *
 * This class registers directories containing user defined callable classes,
 * and generates client side javascript code.
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
use Jaxon\Plugin\Request as RequestPlugin;
use Jaxon\Utils\Translation\Translator;
use Jaxon\Exception\SetupException;

use function is_array;
use function is_dir;
use function is_string;
use function realpath;
use function rtrim;
use function trim;

class DirPlugin extends RequestPlugin
{
    /**
     * The callable registrar
     *
     * @var Registry
     */
    protected $xRegistry;

    /**
     * @var Translator
     */
    protected $xTranslator;

    /**
     * The class constructor
     *
     * @param Registry  $xRegistry
     * @param Translator  $xTranslator
     */
    public function __construct(Registry $xRegistry, Translator $xTranslator)
    {
        $this->xRegistry = $xRegistry;
        $this->xTranslator = $xTranslator;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return Jaxon::CALLABLE_DIR;
    }

    /**
     * Check the directory
     *
     * @param string $sDirectory    The path of teh directory being registered
     *
     * @return string
     * @throws SetupException
     */
    private function checkDirectory(string $sDirectory): string
    {
        $sDirectory = rtrim(trim($sDirectory), '/\\');
        if(!is_dir($sDirectory))
        {
            throw new SetupException($this->xTranslator->trans('errors.objects.invalid-declaration'));
        }
        return realpath($sDirectory);
    }

    /**
     * @inheritDoc
     * @throws SetupException
     */
    public function checkOptions(string $sCallable, $xOptions): array
    {
        if(is_string($xOptions))
        {
            $xOptions = ['namespace' => $xOptions];
        }
        if(!is_array($xOptions))
        {
            throw new SetupException($this->xTranslator->trans('errors.objects.invalid-declaration'));
        }
        // Check the directory
        $xOptions['directory'] = $this->checkDirectory($sCallable);
        // Check the namespace
        $sNamespace = $xOptions['namespace'] ?? '';
        if(!($xOptions['namespace'] = trim($sNamespace, ' \\')))
        {
            $xOptions['namespace'] = '';
        }

        // Change the keys in $xOptions to have "\" as separator
        $_aOptions = [];
        foreach($xOptions as $sName => $aOption)
        {
            $sName = trim(str_replace('.', '\\', $sName), ' \\');
            $_aOptions[$sName] = $aOption;
        }
        return $_aOptions;
    }

    /**
     * Register a callable class
     *
     * @param string $sType    The type of request handler being registered
     * @param string $sCallable    The path of the directory being registered
     * @param array $aOptions    The associated options
     *
     * @return bool
     */
    public function register(string $sType, string $sCallable, array $aOptions): bool
    {
        if(($aOptions['namespace']))
        {
            $this->xRegistry->addNamespace($aOptions['namespace'], $aOptions);
            return true;
        }
        $this->xRegistry->addDirectory($aOptions['directory'], $aOptions);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canProcessRequest(): bool
    {
        return false;
    }

    /**
     * Process the incoming Jaxon request
     *
     * @return bool
     */
    public function processRequest(): bool
    {
        return false;
    }
}