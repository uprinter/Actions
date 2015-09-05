<?php
namespace ru\yukosh\actions;

/**
 * IAction interface
 *
 * Provides interface for actions classes
 *
 * @package Actions
 */
interface IAction {
    /**
     * Execute action
     * @return void
     */
    public function exec();
}