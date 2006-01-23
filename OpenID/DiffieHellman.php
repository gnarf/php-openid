<?php

/**
 * The OpenID library's Diffie-Hellman implementation.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: See the COPYING file included in this distribution.
 *
 * @package OpenID
 * @author JanRain, Inc. <openid@janrain.com>
 * @copyright 2005 Janrain, Inc.
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 */

/**
 * Require CryptUtil because we need to get a Auth_OpenID_MathWrapper
 * object.
 */
require_once('BigMath.php');

$_Auth_OpenID_DEFAULT_MOD = '155172898181473697471232257763715539915724801'.
'966915404479707795314057629378541917580651227423698188993727816152646631'.
'438561595825688188889951272158842675419950341258706556549803580104870537'.
'681476726513255747040765857479291291572334510643245094715007229621094194'.
'349783925984760375594985848253359305585439638443';

$_Auth_OpenID_DEFAULT_GEN = '2';

/**
 * The Diffie-Hellman key exchange class.  This class relies on
 * Auth_OpenID_MathLibrary to perform large number operations.
 *
 * @package OpenID
 */
class Auth_OpenID_DiffieHellman {

    var $mod;
    var $gen;
    var $private;
    var $lib = null;

    function fromBase64($mod, $gen)
    {
        if ($mod !== null) {
            $mod = Auth_OpenID_base64ToLong($mod);
        }

        if ($gen !== null) {
            $gen = Auth_OpenID_base64ToLong($gen);
        }

        return new Auth_OpenID_DiffieHellman($mod, $gen);
    }

    function Auth_OpenID_DiffieHellman($mod = null, $gen = null,
                                       $private = null)
    {
        global $_Auth_OpenID_DEFAULT_MOD,
            $_Auth_OpenID_DEFAULT_GEN;

        $this->lib =& Auth_OpenID_getMathLib();

        if ($mod === null) {
            $this->mod = $this->lib->init($_Auth_OpenID_DEFAULT_MOD);
        } else {
            $this->mod = $mod;
        }

        if ($gen === null) {
            $this->gen = $this->lib->init($_Auth_OpenID_DEFAULT_GEN);
        } else {
            $this->gen = $gen;
        }

        if ($private === null) {
            $r = Auth_OpenID_randrange($this->mod);
            $this->private = $this->lib->add($r, 1);
        } else {
            $this->private = $private;
        }

        $this->public = $this->lib->powmod($this->gen, $this->private,
                                           $this->mod);
    }

    function getSharedSecret($composite)
    {
        return $this->lib->powmod($composite, $this->private, $this->mod);
    }

    function getPublicKey()
    {
        return $this->public;
    }

    function xorSecret($composite, $secret)
    {
        $dh_shared = $this->getSharedSecret($composite);
        $dh_shared_str = Auth_OpenID_longToBinary($dh_shared);
        $sha1_dh_shared = Auth_OpenID_SHA1($dh_shared_str);

        $xsecret = "";
        for ($i = 0; $i < strlen($secret); $i++) {
            $xsecret .= chr(ord($secret[$i]) ^ ord($sha1_dh_shared[$i]));
        }

        return $xsecret;
    }
}
