<?php
namespace ru\yukosh\actions;

/**
 * AbstractAction class
 *
 * Provides common actions functionality
 *
 * @package Actions
 */
abstract class AbstractAction implements IAction, \SplSubject {
    /**
     * Stores action execution results
     *
     * @var mixed $_xResult
     */
    private $_xResult = null;

    /**
     * Stores action call arguments
     *
     * @var array $_aArgs
     */
    private $_aArgs = null;

    /**
     * Stores SplObjectStorage object
     *
     * @var object $_oObservers
     */
    private $_oObservers;

    /**
     * Stores the time of invoking action
     *
     * @var integer $_iStartTime
     */
    private $_iStartTime;

    /**
     * Stores the end time of invoking action
     *
     * @var integer $_iEndTime
     */
    private $_iEndTime;

    /**
     * Indicates that Action is launched via web
     *
     * @var bool $_bHttpCall
     */
    private $_bHttpCall = false;

    /**
     * Marks the debugging mode
     *
     * @var bool $_bDebug
     */
    private static $_bDebug = false;

    /**
     * $_iStartTime property getter
     *
     * @return integer Time of invoking action
     */
    public function getStartTime() {
        return $this->_iStartTime;
    }

    /**
     * $_iEndTime property getter
     *
     * @return integer End time of invoking action
     */
    public function getEndTime() {
        return $this->_iEndTime;
    }

    /**
     * Sets results of action execution
     *
     * @return mixed False if no arguments passed, otherwise void
     */
    protected final function setResult() {
        $aArguments = func_get_args();

        if (!$aArguments) {
            return false;
        }
        elseif (count($aArguments) == 1) {
            $this->_xResult = $aArguments[0];
        }
        else {
            foreach ($aArguments as $iIndex => $xArg) {
                $bIsParamName = !(($iIndex + 1) % 2 == 0);

                if ($bIsParamName && isset($aArguments[$iIndex + 1])) {
                    $this->_xResult[$xArg] = $aArguments[$iIndex + 1];
                }
            }
        }
    }

    /**
     * Returns http-call status
     *
     * @return bool http-call status
     */
    protected final function isHttpCall() {
        return $this->_bHttpCall;
    }

    /**
     * Clears Action execution result
     *
     * @return void
     */
    protected final function clearResult() {
        $this->_xResult = null;
    }

    /**
     * Returns action execution results
     *
     * @return mixed Action execution results
     */
    public final function getResult() {
        $aArguments = func_get_args();

        if (count($aArguments) > 0) {
            if (count($aArguments) == 1) {
                return (isset($this->_xResult[$aArguments[0]])) ? $this->_xResult[$aArguments[0]] : null;
            }

            if (count($aArguments) > 1) {
                $aResult = [];

                foreach ($aArguments as $sKey) {
                    if (isset($this->_xResult[$sKey])) {
                        $aResult[$sKey] = $this->_xResult[$sKey];
                    }
                }

                return $aResult;
            }
        }

        return $this->_xResult;
    }

    /**
     * $_aArgs property getter
     *
     * @return array Arguments array
     */
    public final function getArgs() {
        return $this->_aArgs;
    }

    /**
     * Sets debugging mode
     *
     * @param bool $_bEnabled Enable or not debugging mode
     *
     * @return void
     */
    public function setDebugMode($_bEnabled = true) {
        self::$_bDebug = $_bEnabled;
    }

    /**
     * Calls action
     *
     * @return Action object
     */
    public final function call() {
        $this->_aArgs = func_get_args();

        if (self::$_bDebug) {
            require_once 'ActionsLogger.php';
            $this->_oObservers = new SplObjectStorage();
            $this->attach(new ActionsLogger());
            $this->_execDebug();
        }
        else {
            $this->clearResult();
            $this->exec();
        }

        return $this;
    }

    /**
     * Calls action and marks call as http
     * @return Action object
     */
    public final function httpCall() {
        $this->_bHttpCall = true;
        call_user_func_array(array($this, 'call'), func_get_args());
    }

    /**
     * Calls action in debug mode
     * @return void
     */
    private final function _execDebug() {
        $this->_iStartTime = microtime(true);
        $this->exec();
        $this->_iEndTime = microtime(true);

        $this->notify();
    }

    /**
     * Attaches observer object
     *
     * @param \SplObserver $_oObserver Observer object
     * @return void
     */
    public function attach(\SplObserver $_oObserver) {
        $this->_oObservers->attach($_oObserver);
    }

    /**
     * Detaches observer object
     *
     * @param \SplObserver $_oObserver Observer object
     * @return void
     */
    public function detach(\SplObserver $_oObserver) {
        $this->_oObservers->detach($_oObserver);
    }

    /**
     * Notifies observers
     * @return void
     */
    public function notify() {
        foreach ($this->_oObservers as $oObserver) {
            $oObserver->update($this);
        }
    }
}