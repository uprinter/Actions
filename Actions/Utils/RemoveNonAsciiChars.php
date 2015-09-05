<?php
namespace Actions\Utils;

use ru\yukosh\actions\AbstractAction;
use ru\yukosh\actions\IStreamable;

/**
 * This file is the example of action
 * @package Actions\Utils
 *
 * HOW TO DEFINE NAMESPACE
 * 1. If this action file is located in the default actions directory (Actions):
 *    Namespace should match the physical path from Actions directory to the directory where
 *    this file is located:
 *    namespace Actions\Utils
 *    namespace Actions\Utils
 *    namespace Actions\Utils\Strings
 *    namespace Actions\Utils\Database
 *    etc...
 * 2. If the action file is located under other directories:
 *    Namespace should be started from "Actions" word and the rest part should match the physical path starting from
 *    directory name:
 *    namespace Actions\misc\library\Actions
 *    etc...
 */

class RemoveNonAsciiChars extends AbstractAction implements IStreamable {
    public function exec() {
        // Get indexed array of passed arguments
        $aArguments = $this->getArgs();

        // Handle parameters
        $sString = preg_replace('/[^\x00-\x7F]+/', '', $aArguments[0]);

        // Set results
        $this->setResult($sString);
    }
}