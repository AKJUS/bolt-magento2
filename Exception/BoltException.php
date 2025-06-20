<?php
/**
 * Bolt magento2 plugin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Bolt
 * @package    Bolt_Boltpay
 * @copyright  Copyright (c) 2017-2024 Bolt Financial, Inc (https://www.bolt.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Bolt\Boltpay\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Quote\Model\Quote;

class BoltException extends LocalizedException
{
    /**
     * @var Quote
     */

    protected $quote;

    /**
     * Override LocalizedException constructor because in older Magento versions
     * it does not take the $code parameter into account, defaulting it to 0.
     *
     * @param \Magento\Framework\Phrase $phrase
     * @param \Exception $cause
     * @param int $code
     * @param null|Quote $quote
     */

    public function __construct(Phrase $phrase, ?\Exception $cause = null, $code = 0, $quote = null)
    {
        parent::__construct($phrase, $cause);
        $this->code = (int) $code;
        $this->quote = $quote;
    }

    /**
     * @return null|Quote
     */
    public function getQuote()
    {
        return $this->quote;
    }
}
