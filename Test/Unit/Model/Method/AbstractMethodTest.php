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
 * @copyright   Copyright (c) 2015 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
namespace TIG\Buckaroo\Test\Unit\Model\Method;

/**
 * Class AbstractMethod. Temporary for testing only.
 *
 * @package TIG\Buckaroo\Test\Unit\Model\Method
 */
class AbstractMethod extends \TIG\Buckaroo\Model\Method\AbstractMethod {
    protected $_code = 'tig_buckaroo_test';
    public function getOrderTransactionBuilder($payment) {}
    public function getAuthorizeTransactionBuilder($payment) {}
    public function getCaptureTransactionBuilder($payment) {}
    public function getRefundTransactionBuilder($payment) {}
    public function getVoidTransactionBuilder($payment) {}
}

class AbstractMethodTest extends \TIG\Buckaroo\Test\BaseTest
{
    /**
     * @var \Mockery\MockInterface
     */
    protected $objectManager;

    /**
     * @var \Mockery\MockInterface
     */
    protected $configProvider;

    /**
     * @var \TIG\Buckaroo\Model\Method\AbstractMethod
     */
    protected $object;

    /**
     * @var \Mockery\MockInterface
     */
    protected $scopeConfig;

    /**
     * @var \Mockery\MockInterface
     */
    protected $account;

    /**
     * Setup the standard mocks
     */
    public function setUp()
    {
        parent::setUp();

        $this->objectManager = \Mockery::mock(\Magento\Framework\ObjectManagerInterface::class);
        $this->scopeConfig = \Mockery::mock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->account = \Mockery::mock(\TIG\Buckaroo\Model\ConfigProvider\Account::class);
        $this->configProvider = \Mockery::mock(\TIG\Buckaroo\Model\ConfigProvider\Factory::class);
        $this->configProvider->shouldReceive('get')->with('account')->andReturn($this->account);

        /**
         * We are using the temporary class declared above, but it could be any class extending from the AbstractMethod class.
         */
        $this->object = $this->objectManagerHelper->getObject(AbstractMethod::class, [
            'objectManager' => $this->objectManager,
            'configProviderFactory' => $this->configProvider,
            'scopeConfig' => $this->scopeConfig,
        ]);
    }

    /**
     * Test what happens if the payment method is disabled.
     */
    public function testIsAvailableDisabled()
    {
        /** @var \Magento\Quote\Api\Data\CartInterface|\Mockery\MockInterface $quote */
        $quote = \Mockery::mock(\Magento\Quote\Api\Data\CartInterface::class);

        $this->account->shouldReceive('getActive')->once()->andReturn(0);
        $result = $this->object->isAvailable($quote);

        $this->assertFalse($result);
    }

    /**
     * Test what happens if the allow by ip option is on, but our ip is not in the list.
     */
    public function testIsAvailableInvalidIp()
    {
        /** @var \Magento\Quote\Api\Data\CartInterface|\Mockery\MockInterface $quote */
        $quote = \Mockery::mock(\Magento\Quote\Api\Data\CartInterface::class);
        $quote->shouldReceive('getStoreId')->once()->andReturn(1);

        $this->account->shouldReceive('getActive')->once()->andReturn(1);
        $this->account->shouldReceive('getLimitByIp')->once()->andReturn(1);

        $this->scopeConfig->shouldReceive('getValue');

        $developerHelper = \Mockery::mock(\Magento\Developer\Helper\Data::class);
        $developerHelper->shouldReceive('isDevAllowed')->once()->with(1)->andReturn(false);

        $this->objectManager->shouldReceive('create')->once()->with(\Magento\Developer\Helper\Data::class)->andReturn($developerHelper);

        $result = $this->object->isAvailable($quote);

        $this->assertFalse($result);
    }

    /**
     * Test what happens if the allow by ip option is on, and our ip is in the list.
     */
    public function testIsAvailableValidIp()
    {
        $this->scopeConfig->shouldReceive('getValue')->with('payment/tig_buckaroo_test/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 1)->andReturn(1);
        $this->scopeConfig->shouldReceive('getValue')->with('payment/tig_buckaroo_test/max_amount', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 1)->andReturn(null);
        $this->scopeConfig->shouldReceive('getValue')->with('payment/tig_buckaroo_test/min_amount', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 1)->andReturn(null);

        /** @var \Magento\Quote\Api\Data\CartInterface|\Mockery\MockInterface $quote */
        $quote = \Mockery::mock(\Magento\Quote\Api\Data\CartInterface::class);
        $quote->shouldReceive('getStoreId')->andReturn(1);
        $quote->shouldReceive('getGrandTotal')->once()->andReturn(60);

        $this->account->shouldReceive('getActive')->once()->andReturn(1);
        $this->account->shouldReceive('getLimitByIp')->once()->andReturn(1);

        $this->scopeConfig->shouldReceive('getValue')->andReturn(1);

        $developerHelper = \Mockery::mock(\Magento\Developer\Helper\Data::class);
        $developerHelper->shouldReceive('isDevAllowed')->once()->with(1)->andReturn(true);

        $this->objectManager->shouldReceive('create')->once()->with(\Magento\Developer\Helper\Data::class)->andReturn($developerHelper);

        $result = $this->object->isAvailable($quote);

        $this->assertTrue($result);
    }

    /**
     * Test what happens if we exceed the maximum amount. The method should be hidden.
     */
    public function testIsAvailableExceedsMaximum()
    {
        $this->scopeConfig->shouldReceive('getValue')->once()->with('payment/tig_buckaroo_test/max_amount', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 1)->andReturn(80);
        $this->scopeConfig->shouldReceive('getValue')->once()->with('payment/tig_buckaroo_test/min_amount', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 1)->andReturn(80);
        $this->scopeConfig->shouldReceive('getValue')->once()->with('payment/tig_buckaroo_test/limit_by_ip', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, NULL)->andReturn(false);

        /** @var \Magento\Quote\Api\Data\CartInterface|\Mockery\MockInterface $quote */
        $quote = \Mockery::mock(\Magento\Quote\Api\Data\CartInterface::class);
        $quote->shouldReceive('getStoreId')->andReturn(1);
        $quote->shouldReceive('getGrandTotal')->once()->andReturn(90);

        $this->account->shouldReceive('getActive')->once()->andReturn(1);
        $this->account->shouldReceive('getLimitByIp')->once()->andReturn(1);

        $developerHelper = \Mockery::mock(\Magento\Developer\Helper\Data::class);
        $developerHelper->shouldReceive('isDevAllowed')->once()->with(1)->andReturn(true);

        $this->objectManager->shouldReceive('create')->once()->with(\Magento\Developer\Helper\Data::class)->andReturn($developerHelper);

        $result = $this->object->isAvailable($quote);

        $this->assertFalse($result);
    }

    /**
     * Test what happens if we exceed the minimum amount. The method should be hidden.
     */
    public function testIsAvailableExceedsMinimum()
    {
        $this->scopeConfig->shouldReceive('getValue')->once()->with('payment/tig_buckaroo_test/max_amount', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 1)->andReturn(80);
        $this->scopeConfig->shouldReceive('getValue')->once()->with('payment/tig_buckaroo_test/min_amount', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 1)->andReturn(80);
        $this->scopeConfig->shouldReceive('getValue')->once()->with('payment/tig_buckaroo_test/limit_by_ip', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, NULL)->andReturn(false);

        /** @var \Magento\Quote\Api\Data\CartInterface|\Mockery\MockInterface $quote */
        $quote = \Mockery::mock(\Magento\Quote\Api\Data\CartInterface::class);
        $quote->shouldReceive('getStoreId')->andReturn(1);
        $quote->shouldReceive('getGrandTotal')->once()->andReturn(60);

        $this->account->shouldReceive('getActive')->once()->andReturn(1);
        $this->account->shouldReceive('getLimitByIp')->once()->andReturn(1);

        $developerHelper = \Mockery::mock(\Magento\Developer\Helper\Data::class);
        $developerHelper->shouldReceive('isDevAllowed')->once()->with(1)->andReturn(true);

        $this->objectManager->shouldReceive('create')->once()->with(\Magento\Developer\Helper\Data::class)->andReturn($developerHelper);

        $result = $this->object->isAvailable($quote);

        $this->assertFalse($result);
    }
}