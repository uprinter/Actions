<?php
namespace ru\yukosh\actions;

/**
 * ActionWrapper class
 *
 * Implements stream behaviour for actions
 *
 * USAGE
 * // Example 1
 * file_get_contents('action://Utils/RemoveNonAsciiSymbols?Français');  // Returns string "Franais"
 *
 * // Example 2
 * $xml = new DOMDocument();
 * $xml->load('action://GenerateDocument?xml');  // Build DOMDocument object from XML
 *
 * @package Actions
 */
class ActionWrapper {
    /**
     * @var object Action object instance
     */
    private $_oAction;

    /**
     * @var bool Indicates that reading is done
     */
    private $_bDone = false;

    /**
     * @var array Action call arguments
     */
    private $_aArgs = [];

    /**
     * Instantiates action object
     *
     * @param string $_sUrl Full action name
     * @return bool Returns true in case of success
     */
    function stream_open($_sUrl) {
        try {
            $aUrl = parse_url($_sUrl);

            if (isset($aUrl['query'])) {
                $this->_aArgs = $this->_parseActionParams($aUrl['query']);
            }

            $oFactory = ActionsFactory::getInstance();
            $this->_oAction = $oFactory->get($aUrl['host'] . $aUrl['path']);

            if (!is_a($this->_oAction, 'ru\yukosh\actions\IStreamable')) {
                throw new ActionException('Action must implement IStreamable interface');
            }

            return true;
        }
        catch (ActionException $oException) {
            trigger_error($oException->getMessage(), E_USER_WARNING);
            return false;
        }
    }

    /**
     * Executes action and returns result
     *
     * @return string Action execution result
     */
    function stream_read() {
        if ($this->_bDone) {
            return '';
        }

        $this->_bDone = true;

        call_user_func_array(array($this->_oAction, 'call') , $this->_aArgs);

        $sResult = $this->_oAction->getResult();

        if (is_string($sResult)) {
            return $sResult;
        }

        trigger_error('Result of streamable action must be string', E_USER_WARNING);
    }

    function stream_stat() { }
    function url_stat() {
        return [];
    }

    /**
     * Indicates the end of "reading" process
     *
     * @return bool True if action has been executed
     */
    function stream_eof() {
        return $this->_bDone;
    }

    /**
     * Parses action query string to array of parameters
     * We can't use "parse_str" function because PHP transforms "." to "_" in parameters names
     *
     * @param string $_sQueryString Query string
     * @returns array Array of parameters
     */
    private function _parseActionParams($_sQueryString) {
        $aResult = [];
        $aParams = explode('&', $_sQueryString);

        foreach ($aParams as $sParam) {
            $aParam = explode('=', $sParam);

            if (count($aParam) == 2) {
                $aResult[$aParam[0]] = $aParam[1];
            }
            else {
                $aResult[] = $aParam[0];
            }
        }

        return $aResult;
    }
}

if (!in_array('action', stream_get_wrappers())) {
    stream_wrapper_register('action', 'ru\yukosh\actions\ActionWrapper');
}