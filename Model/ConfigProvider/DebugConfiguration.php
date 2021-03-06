<?php
/**
 *
 *          ..::..
 *     ..::::::::::::..
 *   ::'''''':''::'''''::
 *   ::..  ..:  :  ....::
 *   ::::  :::  :  :   ::
 *   ::::  :::  :  ''' ::
 *   ::::..:::..::.....::
 *     ''::::::::::::''
 *          ''::''
 *
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
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
namespace TIG\Buckaroo\Model\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;

class DebugConfiguration extends AbstractConfigProvider
{
    /** @var Account */
    private $accountConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Account $account
    ) {
        $this->accountConfig = $account;

        parent::__construct($scopeConfig);
    }

    /**
     * @return mixed
     */
    public function getDebugTypes()
    {
        return $this->accountConfig->getDebugTypes();
    }

    /**
     * @return array
     */
    public function getDebugEmails()
    {
        $debugEmails = $this->accountConfig->getDebugEmail();
        $debugEmails = explode(',', $debugEmails);

        return $debugEmails;
    }

    /**
     * @param $level
     *
     * @return bool
     */
    public function canLog($level)
    {
        $logTypes = explode(',', $this->getDebugTypes());
        return in_array($level, $logTypes);
    }
}
