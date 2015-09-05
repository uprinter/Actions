## What is Action?
Actions are more convenient alternative for simple functions library with wider functionality. Moreover, the same Action can be called by five ways: PHP method, PHP stream functions via action:// protocol, HTTP request, command-line interface and sending specific e-mail.
## How to use existing Action?
Just one file you need to include is "ActionsFactory.php":
```php
require_once 'Actions/ActionsFactory.php';
use ru\yukosh\actions\ActionsFactory;
```
After that you can get and use Action:
```php
// Get instance of Actions Factory
$oFactory = ActionsFactory::getInstance();

// Get Action that you need
$oAction = $oFactory->get('Utils/RemoveNonAsciiChars');

// Call action with parameters
$oAction->call('Français');

// Get result
echo $oAction->getResult(); // prints 'Franais'
```
## How to call Action by different ways?
```php
// Example 1 (short call via PHP)
$oFactory = ActionsFactory::getInstance();
$oFactory['Utils/RemoveNonAsciiChars']->call('Français')->getResult();
// or
ActionsFactory::getAction('Utils/RemoveNonAsciiChars')->call('Français')->getResult();

// Example 2 (via stream functions using action:// protocol)
echo file_get_contents('action://Utils/RemoveNonAsciiChars?Français');

// Example 3 (loading XML via action:// protocol)
$oXml = new DOMDocument();
$sXml->load('action://GetXMLDocument');

// Example 4 (via HTTP)
http://your_host/Actions/ActionRunner.php?action=Utils/RemoveNonAsciiChars&Français

// Example 5 (via CLI)
php -f ActionRunner.php Utils/RemoveNonAsciiChars Français

// Example 6 (via e-mail)
// See configuration file EmailCheckingConfig.json
// Action should implement IEmailable interface
```
## How to get result from an Action?
```php
// Example 1 (returns array if several values were set in setResult() method)
$xValue = $oAction->getResult();

// Example 2 (returns result by key)
$sValue = $oAction->getResult('key');

// Example 3 (returns result by keys)
$aValues = $oAction->getResult('key1', 'key2');
```
## How to create new Action?
1. Create a new class that extends AbstractAction class and place it into PHP file with the same name. You can also create any sub-folder according your vision of Actions structure;
2. Class namespace must match the physical path from Actions directory to the directory where this file is located;
3. Class may implement IStreamable interface if Action returns string result (i.e. the result may be received in command-line or via HTTP protocol) and/or IEmailable if Action should be called by sending email.

```php
namespace Actions\Utils;

use ru\yukosh\actions\AbstractAction;
use ru\yukosh\actions\IStreamable;

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
```