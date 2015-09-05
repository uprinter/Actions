<?php
namespace ru\yukosh\actions;

/**
 * ActionException class
 */
require_once 'ActionException.php';

/**
 * IAction interface
 */
require_once 'IAction.php';

/**
 * AbstractAction class, provides common actions functionality
 */
require_once 'AbstractAction.php';

/**
 * IStreamable interface
 */
require_once 'IStreamable.php';

/**
 * IEmailable interface
 */
require_once 'IEmailable.php';

/**
 * ActionWrapper class, implements stream behaviour for actions
 */
require_once 'ActionWrapper.php';

/**
 * ActionsFactory class
 *
 * Instantiates actions objects
 *
 * @package Actions
 */
class ActionsFactory implements \ArrayAccess {
    /**
     * Private constructor
     */
    private function __construct() {
        $this->_sDefaultActionsDirectory = 'Actions';
    }

    /**
     * Hides _clone() method in order to forbid explicit instance class creation
     */
    private function __clone() {}

    /**
     * @var object Store instance of class
     */
    private static $_oInstance = null;

    /**
     * @var array Cache of instantiated Actions
     */
    private static $_aCache = [];

    /**
     * @var string Default actions directory
     */
    private $_sDefaultActionsDirectory;

    /**
     * @var string Root path for project
     */
    private $_sRoot;

    /**
     * @var array Path aliases
     */
    private static $_aRegisteredPaths = array();

    /**
     * Returns class instance
     *
     * @static
     * @return object Class instance
     */
    public static function getInstance() {
        if (is_null(self::$_oInstance)) {
            self::$_oInstance = new ActionsFactory();
        }

        return self::$_oInstance;
    }

    /**
     * Register filesystem path where actions are located
     *
     * @param mixed $_xAlias Array of paths to find actions or action alias
     * @param string $_sPath Action path if first parameter is action alias
     * @return void
     */
    public static function registerPath($_xAlias, $_sPath = null) {
        if (is_string($_xAlias) && is_string($_sPath)) {
            self::_addPath($_xAlias, $_sPath);
        }

        if (is_array($_xAlias)) {
            foreach ($_xAlias as $sAlias => $sPath) {
                self::_addPath($sAlias, $_sPath);
            }
        }
    }

    /**
     * Gets action class instance by action name
     *
     * @param string $_sActionName Action name
     * @return object Action class instance
     * @throws ActionException
     */
    public static function getAction($_sActionName) {
        $oFactory = self::getInstance();
        return $oFactory->get($_sActionName);
    }

    /**
     * Add filesystem path to the registry
     *
     * @param string $_sAlias Action alias
     * @param string $_sPath Action path
     * @return void
     */
    private static function _addPath($_sAlias, $_sPath) {
        $sAlias = trim($_sAlias);
        $sPath = trim($_sPath);

        if ($sAlias != '' && $sPath != '') {
            self::$_aRegisteredPaths[$sAlias] = $sPath;
        }
    }

    /**
     * Unregister filesystem path where actions are located
     *
     * @param string $_sAlias Path alias
     * @return void
     */
    public static function unregisterPath($_sAlias) {
        unset(self::$_aRegisteredPaths[$_sAlias]);
    }

    /**
     * Nothing to do if user tries to set Action
     *
     * @param string $_sOffset Array key
     * @param mixed $_xValue Value
     */
    public function offsetSet($_sOffset, $_xValue) {}

    /**
     * Removes Action from the cache
     *
     * @param string $_sActionName Action name
     */
    public function offsetUnset($_sActionName) {
        if (isset(self::$_aCache[$_sActionName])) {
            unset(self::$_aCache[$_sActionName]);
        }
    }

    /**
     * Checks if Action exists or not
     *
     * @param string $_sActionName Action name
     * @return bool
     */
    public function offsetExists($_sActionName) {
        try {
            $this->get($_sActionName);
            return true;
        }
        catch (ActionException $oException) {
            return false;
        }
    }

    /**
     * Gets action class instance by action name
     *
     * @param string $_sActionName Action name
     * @return object Action class instance
     */
    public function offsetGet($_sActionName) {
        return $this->get($_sActionName);
    }

    /**
     * Gets action class instance by action name
     *
     * @param string $_sActionName Action name
     * @return object Action class instance
     * @throws ActionException
     */
    public function get($_sActionName) {
        $sPathAlias = strstr($_sActionName, '/', true);

        if (isset(self::$_aRegisteredPaths[$sPathAlias]) && is_dir(self::$_aRegisteredPaths[$sPathAlias])) {
            // Compose include path for action in registered path
            $sRealActionName = strstr($_sActionName, '/');
            $sRealActionName = ($sRealActionName == '') ? '/' . $_sActionName : $sRealActionName;
            $sIncludePath = self::$_aRegisteredPaths[$sPathAlias] . '/' . $sRealActionName . '.php';
            $sFullClassName =  self::$_aRegisteredPaths[$sPathAlias] . $sRealActionName;
        }
        else {
            // If registered path not found, compose include path for action in default directory
            $sIncludePath = __DIR__ . '/' . $this->_sDefaultActionsDirectory. '/' . $_sActionName . '.php';
            $sFullClassName = $_sActionName;
        }

        // Compose action class name
        $sFullClassName = preg_replace('/\/+/', '/', $sFullClassName);
        $sClassName = 'Actions\\' . str_replace('/', '\\', $sFullClassName);

        if (isset(self::$_aCache[$sFullClassName])) {
            // Return Action if it is already instantiated
            return self::$_aCache[$sFullClassName];
        }

        if (file_exists($sIncludePath)) {
            // Includes action class file
            require_once $sIncludePath;
        }
        else {
            $sIncludePath = str_replace('\\', '/', $sIncludePath);
            $oActionException = new ActionException('Action file ' . $sIncludePath . ' not found');
            $oActionException->setActionPath($sIncludePath);
            throw $oActionException;
        }

        if (class_exists($sClassName)) {
            $oInstance = new $sClassName();
            self::$_aCache[$sFullClassName] = $oInstance;
            return $oInstance;
        }

        $oActionException = new ActionException('Class ' . $sClassName . ' not found');
        $oActionException->setClassName($sClassName);
        throw $oActionException;
    }
}