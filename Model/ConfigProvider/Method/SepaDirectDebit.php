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
 * to servicedesk@totalinternetgroup.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@totalinternetgroup.nl for more information.
 *
 * @copyright Copyright (c) 2014 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license   http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

namespace TIG\Buckaroo\Model\ConfigProvider\Method;

class SepaDirectDebit extends AbstractConfigProvider
{
    const XPATH_SEPADIRECTDEBIT_PAYMENT_FEE             = 'payment/tig_buckaroo_sepadirectdebit/payment_fee';
    const XPATH_SEPADIRECTDEBIT_PAYMENT_FEE_LABEL       = 'payment/tig_buckaroo_sepadirectdebit/payment_fee_label';
    const XPATH_SEPADIRECTDEBIT_ACTIVE                  = 'payment/tig_buckaroo_sepadirectdebit/active';
    const XPATH_SEPADIRECTDEBIT_ACTIVE_STATUS           = 'payment/tig_buckaroo_sepadirectdebit/active_status';
    const XPATH_SEPADIRECTDEBIT_ORDER_STATUS_SUCCESS    = 'payment/tig_buckaroo_sepadirectdebit/order_status_success';
    const XPATH_SEPADIRECTDEBIT_ORDER_STATUS_FAILED     = 'payment/tig_buckaroo_sepadirectdebit/order_status_failed';
    const XPATH_SEPADIRECTDEBIT_AVAILABLE_IN_BACKEND    = 'payment/tig_buckaroo_sepadirectdebit/available_in_backend';

    const XPATH_ALLOWED_CURRENCIES = 'payment/tig_buckaroo_sepadirectdebit/allowed_currencies';

    const XPATH_ALLOW_SPECIFIC                  = 'payment/tig_buckaroo_sepadirectdebit/allowspecific';
    const XPATH_SPECIFIC_COUNTRY                = 'payment/tig_buckaroo_sepadirectdebit/specificcountry';

    /**
     * @return array|void
     */
    public function getConfig()
    {
        $paymentFeeLabel = $this
            ->getBuckarooPaymentFeeLabel(\TIG\Buckaroo\Model\Method\SepaDirectDebit::PAYMENT_METHOD_CODE);

        return [
            'payment' => [
                'buckaroo' => [
                    'sepadirectdebit' => [
                        'paymentFeeLabel' => $paymentFeeLabel,
                        'allowedCurrencies' => $this->getAllowedCurrencies(),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return float
     */
    public function getPaymentFee()
    {
        $paymentFee = $this->scopeConfig->getValue(
            self::XPATH_SEPADIRECTDEBIT_PAYMENT_FEE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $paymentFee ? $paymentFee : false;
    }
}
