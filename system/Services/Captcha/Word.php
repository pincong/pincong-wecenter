<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Captcha
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */



/**
 * Word-based captcha adapter
 *
 * Generates random word which user should recognise
 *
 * @category   Zend
 * @package    Zend_Captcha
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
abstract class Services_Captcha_Word
{
    /**#@+
     * @var array Character sets
     */
    static public $V  = array("a", "e", "i", "o", "u", "y");
    static public $VN = array("a", "e", "i", "o", "u", "y","2","3","4","5","6","7","8","9");
    static public $C  = array("b","c","d","f","g","h","j","k","m","n","p","q","r","s","t","u","v","w","x","z");
    static public $CN = array("b","c","d","f","g","h","j","k","m","n","p","q","r","s","t","u","v","w","x","z","2","3","4","5","6","7","8","9");
    /**#@-*/


    /**
     * Should the numbers be used or only letters
     *
     * @var boolean
     */
    protected $_useNumbers = true;

    /**
     * Length of the word to generate
     *
     * @var integer
     */
    protected $_wordlen = 8;

    /**
     * Retrieve word length to use when genrating captcha
     *
     * @return integer
     */
    public function getWordlen()
    {
        return $this->_wordlen;
    }

    /**
     * Set word length of captcha
     *
     * @param integer $wordlen
     * @return Services_Captcha_Word
     */
    public function setWordlen($wordlen)
    {
        $this->_wordlen = $wordlen;
        return $this;
    }

    /**
     * Numbers should be included in the pattern?
     *
     * @return bool
     */
    public function getUseNumbers()
    {
        return $this->_useNumbers;
    }

    /**
     * Set if numbers should be included in the pattern
     *
     * @param bool $_useNumbers numbers should be included in the pattern?
     * @return Services_Captcha_Word
     */
    public function setUseNumbers($_useNumbers)
    {
        $this->_useNumbers = $_useNumbers;
        return $this;
    }

    /**
     * Generate new random word
     *
     * @return string
     */
    public function generateWord()
    {
        $word       = '';
        $wordLen    = $this->getWordLen();
        $vowels     = $this->_useNumbers ? self::$VN : self::$V;
        $consonants = $this->_useNumbers ? self::$CN : self::$C;

        $totIndexCon = count($consonants) - 1;
        $totIndexVow = count($vowels) - 1;
        for ($i=0; $i < $wordLen; $i = $i + 2) {
            // generate word with mix of vowels and consonants
            $consonant = $consonants[mt_rand(0, $totIndexCon)];
            $vowel     = $vowels[mt_rand(0, $totIndexVow)];
            $word     .= $consonant . $vowel;
        }

        if (strlen($word) > $wordLen) {
            $word = substr($word, 0, $wordLen);
        }

        return $word;
    }

}
