<?php
/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) 2015 Total Internet Group B.V. (http://www.tig.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

namespace TIG\Buckaroo\Model\Total\Quote\Tax;

use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;

class BuckarooFeeAfterTax extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /**
     */
    public function __construct()
    {
        $this->setCode('tax_buckaroo_fee');
    }

    /**
     * Collect buckaroo fee tax totals
     *
     * @param \Magento\Quote\Model\Quote                          $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total            $total
     *
     * @return $this
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        if (!$shippingAssignment->getItems()) {
            return $this;
        }
        $extraTaxableDetails = $total->getExtraTaxableDetails();
        $itemTaxDetails = $extraTaxableDetails[BuckarooFee::QUOTE_TYPE];

        $buckarooFeeTaxDetails = $itemTaxDetails[CommonTaxCollector::ASSOCIATION_ITEM_CODE_FOR_QUOTE][0];

        $buckarooFeeBaseTaxAmount = $buckarooFeeTaxDetails['base_row_tax'];
        $buckarooFeeTaxAmount = $buckarooFeeTaxDetails['row_tax'];
        $buckarooFeeInclTax = $buckarooFeeTaxDetails['price_incl_tax'];
        $buckarooFeeBaseInclTax = $buckarooFeeTaxDetails['base_price_incl_tax'];

        $total->setBuckarooFeeInclTax($buckarooFeeInclTax);
        $total->setBaseBuckarooFeeInclTax($buckarooFeeBaseInclTax);

        $total->setBuckarooFeeBaseTaxAmount($buckarooFeeBaseTaxAmount);
        $total->setBuckarooFeeTaxAmount($buckarooFeeTaxAmount);

        $quote->setBuckarooFeeInclTax($buckarooFeeInclTax);
        $quote->setBaseBuckarooFeeInclTax($buckarooFeeBaseInclTax);

        $quote->setBuckarooFeeBaseTaxAmount($buckarooFeeBaseTaxAmount);
        $quote->setBuckarooFeeTaxAmount($buckarooFeeTaxAmount);

        return $this;
    }

    /**
     * Assign buckaroo fee tax totals and labels to address object
     *
     * @param \Magento\Quote\Model\Quote               $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     *
     * @return array
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        return [
            'code' => 'buckaroo_fee',
            'title' => $this->getLabel(),
            'buckaroo_fee' => $total->getBuckarooFee(),
            'base_buckaroo_fee' => $total->getBaseBuckarooFee(),
            'buckaroo_fee_incl_tax' => $total->getBuckarooFeeInclTax(),
            'base_buckaroo_fee_incl_tax' => $total->getBaseBuckarooFeeInclTax(),
            'buckaroo_fee_tax_amount' => $total->getBuckarooFeeTaxAmount(),
            'buckaroo_fee_base_tax_amount' => $total->getBuckarooFeeBaseTaxAmount(),
        ];
    }

    /**
     * Get Buckaroo label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return __('Payment Fee');
    }
}
