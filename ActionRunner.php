<?php
namespace ru\yukosh\actions;

require_once __DIR__ . '/ActionsFactory.php';
require_once __DIR__ . '/vendor/autoload.php';

/**
 * ActionRunner class
 *
 * Action runner via HTTP protocol, command-line interface or e-mail
 *
 * @package Actions
 */
class ActionRunner {
    /**
     * Command-line running arguments
     *
     * @var array
     */
    private $_aRunningArguments = [];

    public function __construct(array $_aArgs) {
        $this->_aRunningArguments = $_aArgs;
    }

    /**
     * Recognizes running type - HTTP or command-line
     *
     * @return boolean Is it command-line launch or not
     */
    private function isCommandLineLaunch() {
        return php_sapi_name() == 'cli' || (isset($_SERVER['argc']) &&
            is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0);
    }

    /**
     * Handles request, parses arguments, instantiate action and executes it
     *
     * @throws ActionException
     */
    public function run() {
        $aArguments = [];
        $xActionName = '';
        $bHttpCall = false;

        if ($this->isCommandLineLaunch()) {
            // Command-line call
            if (count($this->_aRunningArguments) > 1) {
                $xActionName = $this->_aRunningArguments[1];

                if ($xActionName == '__email__') {
                    // E-mail call
                    $aResult = [];

                    $aActions = $this->_checkEmail();

                    if (!empty($aActions)) {
                        foreach ($aActions as $aCurrentAction) {
                            $sPathAlias = '';

                            if (isset($aCurrentAction['registerPath'])) {
                                ActionsFactory::registerPath($aCurrentAction['registerPath']['alias'],
                                    $aCurrentAction['registerPath']['path']);

                                $sPathAlias = $aCurrentAction['registerPath']['alias'] . '/';
                            }

                            $aArguments = array_slice($this->_aRunningArguments, 2);
                            $aCurrentAction['args'] = array_merge($aCurrentAction['args'], $aArguments);
                            $aResult[] = ['name' => $sPathAlias . $aCurrentAction['name'], 'args' => $aCurrentAction['args']];
                        }

                        $xActionName = $aResult;
                    }
                    else {
                        // No Actions to execute...
                        return;
                    }
                }
                else {
                    $aArguments = array_slice($this->_aRunningArguments, 2);
                }
            }
        }
        else {
            // HTTP call
            $bHttpCall = true;
            $aGetParams = $this->_parseGetParams($_SERVER['QUERY_STRING']);

            if (isset($aGetParams['action'])) {
                $xActionName = $aGetParams['action'];
                unset($aGetParams['action']);
                $aArguments = $aGetParams;
            }
        }

        if (!empty($xActionName)) {
            $oFactory = ActionsFactory::getInstance();

            if (is_string($xActionName)) {
                $xActionName = [['name' => $xActionName, 'args' => $aArguments]];
            }

            foreach ($xActionName as $aAction) {
                // Fetch and execute action
                $oAction = $oFactory->get($aAction['name']);

                // Action must be "streamable" to return string result
                if (is_a($oAction, 'ru\yukosh\actions\IStreamable')) {
                    call_user_func_array(array($oAction, ($bHttpCall) ? 'httpCall' : 'call'), $aAction['args']);
                    echo $sResult = $oAction->getResult();
                }
                else {
                    throw new ActionException($aAction['name'] . ' action should implement IStreamable interface');
                }
            }
        }
    }

    /**
     * Parses query string to array of parameters
     * We can't use $_GET array because PHP transforms "." to "_" in parameters names
     *
     * @param  $_sQueryString  Query string
     * @returns  array  Array of parameters
     */
    private function _parseGetParams($_sQueryString) {
        $aResult = [];
        $aParams = explode('&', $_sQueryString);

        foreach ($aParams as $sParam) {
            $aParam = explode('=', $sParam);

            if (count($aParam) == 2) {
                $aResult[$aParam[0]] = urldecode($aParam[1]);
            }
            else {
                $aResult[] = urldecode($aParam[0]);
            }
        }

        return $aResult;
    }

    /**
     * Checks e-mail and searches Actions that should be run
     *
     * @return array Array of Actions to execute
     * @throws ActionException
     */
    private function _checkEmail() {
        $sConfig = file_get_contents('EmailCheckingConfig.json');
        $aConfig = json_decode($sConfig, true);

        // Get accounts
        $aAccountsConfig = $aConfig['accounts'];

        // Get Actions
        $aActionsConfig = $aConfig['actions'];

        // Select Actions to run
        $aActionsToCheck = [];

        // Actions to run
        $aActionsToRun = [];

        foreach ($aActionsConfig as $aActionConfig) {
            // Check "check" parameter
            $oCron = Cron\CronExpression::factory($aActionConfig['check']);

            if ($oCron->isDue()) {
                $aActionsToCheck[] = $aActionConfig;
            }
        }

        // Check selected Actions for running letter
        foreach ($aActionsToCheck as $aActionConfig) {
            $aAccountConfig = $aAccountsConfig[$aActionConfig['account']];

            if ($oConnection = imap_open($aAccountConfig['server'],
                $aAccountConfig['user'], $aAccountConfig['password'])) {
                $oCheck = imap_check($oConnection);

                if ($oCheck->Nmsgs > 0) {
                    $aEmails = imap_search($oConnection, 'UNSEEN');

                    foreach ($aEmails as $iEmailNumber) {
                        $oOverview = imap_fetch_overview($oConnection, $iEmailNumber)[0];

                        // Match letter and Action run parameters
                        $bResult = preg_match('/' . $aActionConfig['email']['fromRegexp'] . '/i', $oOverview->from);

                        // Check subject patterns
                        $xCheckSubjectPatternsResult = $this->_checkSubjectPatterns($oOverview->subject,
                            $aActionConfig['email']['subjectRegexp']);

                        $bResult = $bResult && $xCheckSubjectPatternsResult !== false;

                        if ($bResult) {
                            $aArguments = [
                                $oOverview,
                                imap_body($oConnection, $iEmailNumber),
                                imap_fetchstructure($oConnection, $iEmailNumber)
                            ];

                            if (is_array($xCheckSubjectPatternsResult) && count($xCheckSubjectPatternsResult) > 0) {
                                $aArguments[] = $xCheckSubjectPatternsResult;
                            }

                            $aAction = [
                                'name'=> $aActionConfig['name'],
                                'args' => $aArguments
                            ];

                            if (isset($aActionConfig['registerPath'])) {
                                $aAction['registerPath'] = $aActionConfig['registerPath'];
                            }

                            $aActionsToRun[] = $aAction;
                            imap_delete($oConnection, $iEmailNumber);
                        }
                    }
                }

                imap_close($oConnection, CL_EXPUNGE);
            }
            else {
                throw new ActionException('Can\'t connect to mail server');
            }
        }

        return $aActionsToRun;
    }

    /**
     * Checks subject patterns and returns matches
     *
     * @param string $_sSubject  Letter subject
     * @param string|array $_xPatterns  Subject pattern or array of patterns
     * @return array|bool False if appropriate subject haven't been found or array of matches
     */
    private function _checkSubjectPatterns($_sSubject, $_xPatterns) {
        $aPatterns = (!is_array($_xPatterns)) ? [$_xPatterns] : $_xPatterns;

        foreach ($aPatterns as $sPattern) {
            $sSubject = trim($_sSubject);
            $sSubject = preg_replace('/ +/', ' ', $sSubject);

            if (preg_match('/' . $sPattern . '/i', $sSubject, $aMatches)) {
                return array_slice($aMatches, 1);
            }
        }

        return false;
    }
}

$oLauncher = new ActionRunner((isset($argv)) ? $argv : []);

try {
    $oLauncher->run();
}
catch (ActionException $oException) {
    echo $oException->getMessage();
}