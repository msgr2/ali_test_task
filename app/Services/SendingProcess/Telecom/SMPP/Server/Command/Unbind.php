<?php

namespace App\Services\SendingProcess\Telecom\SMPP\Server\Command;

use App\Services\SendingProcess\Telecom\SMPP\Server\Command;


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * SMPP v3.4 unbind command class and/or data
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Networking
 * @package    Net_SMPP
 * @author     Ian Eure <ieure@php.net>
 * @copyright  (c) Copyright 2005 WebSprockets, LLC.
 * @copyright  Portions of the documentation (c) Copyright 1999 SMPP Developers
 *             Forum.
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @version    CVS:     $Revision: 298083 $
 * @since      Release
 * @link       http://pear.php.net/package/Net_SMPP
 */

// Place includes, constant defines and $_GLOBAL settings here.

/**
 * unbind class
 *
 * This command has no paramaters
 *
 * @category   Networking
 * @package    Net_SMPP
 * @author     Ian Eure <ieure@php.net>
 * @copyright  (c) Copyright 2005 WebSprockets, LLC.
 * @copyright  Portions of the documentation (c) Copyright 1999 SMPP Developers
 *             Forum.
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @version    CVS:     $Revision: 298083 $
 * @since      Release
 * @link       http://pear.php.net/package/Net_SMPP
 */
class Unbind extends Command
{
    /**
     * Paramater definitions for this command
     *
     * @var     array
     * @access  protected
     * @see     Net_SMPP_Command::$_defs
     */
    var $_defs = array();
}
