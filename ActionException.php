<?php
namespace ru\yukosh\actions;

/**
 * ActionException class
 *
 * Specify special exception type for actions
 *
 * @package Actions
 */
class ActionException extends \Exception {
    /**
     * @var string Action path
     */
    private $_sActionPath;

    /**
     * @var string Action class name
     */
    private $_sClassName;

    /**
     * $_sActionPath property setter
     *
     * @param string Action path
     * @return void
     */
    public function setActionPath($_sActionPath) {
        $this->_sActionPath = $_sActionPath;
    }

    /**
     * $_sActionPath property getter
     *
     * @return string Action path
     */
    public function getActionPath() {
        return $this->_sActionPath;
    }

    /**
     * $_sClassName property setter
     * 
     * @param string Action class name
     * @return void
     */
    public function setClassName($_sClassName) {
        $this->_sClassName = $_sClassName;
    }

    /**
     * $_sClassName property getter
     *
     * @return string Action class name
     */
    public function getClassName() {
        return $this->_sClassName;
    }
}