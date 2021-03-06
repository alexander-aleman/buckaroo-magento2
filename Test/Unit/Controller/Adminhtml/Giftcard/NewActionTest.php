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
 * @copyright Copyright (c) 2016 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license   http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
namespace TIG\Buckaroo\Test\Unit\Controller\Adminhtml\Giftcard;

use \TIG\Buckaroo\Controller\Adminhtml\Giftcard\NewAction;
use TIG\Buckaroo\Test\BaseTest;

class NewActionTest extends BaseTest
{
    /**
     * @var NewAction
     */
    protected $controller;

    public function setUp()
    {
        parent::setUp();

        $httpRequest = \Mockery::mock(\Magento\Framework\App\Request\Http::class)->makePartial();

        $context = $this->objectManagerHelper->getObject(
            \Magento\Backend\App\Action\Context::class,
            [
                'request' => $httpRequest
            ]
        );

        $registry = $registryMock = \Mockery::mock(\Magento\Framework\Registry::class);
        $resultPageFactory = \Mockery::mock(\Magento\Framework\View\Result\PageFactory::class);
        $giftcardFactory = \Mockery::mock(\TIG\Buckaroo\Model\GiftcardFactory::class);

        $this->controller = $this->objectManagerHelper->getObject(
            NewAction::class,
            [
                'context' => $context,
                'coreRegistry' => $registry,
                'resultPageFactory' => $resultPageFactory,
                'giftcardFactory' => $giftcardFactory
            ]
        );
    }

    public function testExecute()
    {
        $this->markTestSkipped('Figure out how to perform assertions');

        $this->controller->execute();

        if ($container = \Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }
    }
}
