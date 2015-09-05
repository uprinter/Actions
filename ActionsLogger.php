<?php
namespace ru\yukosh\actions;

/**
 * ActionsLogger class
 *
 * Implements functionality for logging actions execution
 * Implements observer pattern
 *
 * USAGE
 * $oAction = $oFactory->get('Utils/RemoveNonAsciiSymbols');  // Get action instance
 * $oAction->setDebugMode(true);                              // Set debug mode
 * $oAction->call('Français');                                // Call action
 * $aLog = ActionsLogger::getLog();                           // Get log array
 *
 * @package Actions
 */
class ActionsLogger implements \SplObserver {
    /**
     * @var array Log array
     */
    private static $_aLog = array();

    /**
     * Gets data from $_oSubject object
     *
     * @param \SplSubject $_oSubject Action object
     * @return void
     */
    function update(\SplSubject $_oSubject) {
        $iMicroSec = $_oSubject->getEndTime() - $_oSubject->getStartTime();
        $iSec = number_format($iMicroSec, 10, '.', ' ');

        $aArguments = $_oSubject->getArgs();
        $xResult = $_oSubject->getResult();

        array_unshift(self::$_aLog, array
        (
            'action'   => get_class($_oSubject),
            'time_msec' => $iMicroSec,
            'time_sec'  => $iSec,
            'args'      => $aArguments,
            'result'    => $xResult
        ));
    }

    /**
     * Returns log array
     *
     * @static
     * @return array Log array
     */
    public static function getLog() {
        return self::$_aLog;
    }
}