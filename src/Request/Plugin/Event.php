<?phpnamespace Xajax\Request\Plugin;use Xajax\Plugin\Request as RequestPlugin;/*	File: Event.php	Contains the Event class	Title: Event class	Please see <copyright.php> for a detailed description, copyright	and license information.*//*	@package Xajax	@version $Id: Event.php 362 2007-05-29 15:32:24Z calltoconstruct $	@copyright Copyright (c) 2005-2007 by Jared White & J. Max Wilson	@copyright Copyright (c) 2008-2009 by Joseph Woolley, Steffen Konerow, Jared White  & J. Max Wilson	@license http://www.xajaxproject.org/bsd_license.txt BSD License*//*	Constant: XAJAX_EVENT		Specifies that the item being registered via the <xajax->register> function		is an event.			Constant: XAJAX_EVENT_HANDLER		Specifies that the item being registered via the <xajax->register> function		is an event handler.*/if(!defined ('XAJAX_EVENT')) define ('XAJAX_EVENT', 'xajax event');if(!defined ('XAJAX_EVENT_HANDLER')) define ('XAJAX_EVENT_HANDLER', 'xajax event handler');/*	Class: Event		Plugin that adds server side event handling capabilities to xajax.  Events can	be registered, then event handlers attached.*/class Event extends RequestPlugin{	use \Xajax\Utils\ContainerTrait;	/*		Array: aEvents	*/	protected $aEvents;	/*		String: sRequestedEvent	*/	protected $sRequestedEvent;	/*		Function: __construct	*/	public function __construct()	{		$this->aEvents = array();		$this->sRequestedEvent = NULL;		if(isset($_GET['xjxevt']))		{			$this->sRequestedEvent = $_GET['xjxevt'];		}		if(isset($_POST['xjxevt']))		{			$this->sRequestedEvent = $_POST['xjxevt'];		}	}	/*		Function: getName	*/	public function getName()	{		return 'Event';	}	/*		Function: register		$sType - (string): type of item being registered		$sEvent - (string): the name of the event		$ufHandler - (function name or reference): a reference to the user function to call		$aConfiguration - (array): an array containing configuration options	*/	public function register($aArgs)	{		if(1 < count($aArgs))		{			$sType = $aArgs[0];			if(XAJAX_EVENT == $sType)			{				$sEvent = $aArgs[1];				if(false === isset($this->aEvents[$sEvent]))				{					$xe = new \Xajax\Support\Event($sEvent);					if(2 < count($aArgs))						if(is_array($aArgs[2]))							foreach($aArgs[2] as $sKey => $sValue)								$xe->configure($sKey, $sValue);					$this->aEvents[$sEvent] = $xe;					return $xe->generateRequest();				}			}			if(XAJAX_EVENT_HANDLER == $sType)			{				$sEvent = $aArgs[1];				if(isset($this->aEvents[$sEvent]))				{					if(isset($aArgs[2]))					{						$xuf =& $aArgs[2];						if(false === ($xuf instanceof \Xajax\Support\UserFunction))							$xuf = new \Xajax\Support\UserFunction($xuf);						$objEvent =& $this->aEvents[$sEvent];						$objEvent->addHandler($xuf);						return true;					}				}			}		}		return false;	}	public function generateHash()	{		$sHash = '';		foreach($this->aEvents as $xEvent)		{			$sHash .= $xEvent->getName();		}		return md5($sHash);	}	/*		Function: getClientScript	*/	public function getClientScript()	{		$code = '';		foreach($this->aEvents as $xEvent)		{			$code .= $xEvent->getClientScript();		}		return $code;	}	/*		Function: canProcessRequest	*/	public function canProcessRequest()	{		// Check the validity of the event name		if(($this->sRequestedEvent) && !$this->validateEvent($this->sRequestedEvent))		{			$this->sRequestedEvent = null;		}		return ($this->sRequestedEvent != NULL);	}	/*		Function: processRequest	*/	public function processRequest()	{		if(!$this->canProcessRequest())			return false;		$aArgs = RequestManager::getInstance()->process();		if(array_key_exists($this->sRequestedEvent, $this->aEvents))		{			$this->aEvents[$this->sRequestedEvent]->fire($aArgs);			return true;		}		// Unable to find the requested event		throw new \Xajax\Exception\Error('errors.events.invalid', array('name' => $this->sRequestedEvent));	}}