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
 * @copyright Copyright (c) 2016 Total Internet Group B.V. (http://www.tig.nl)
 * @license   http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
namespace TIG\Buckaroo\Test\Unit\Model;

use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use TIG\Buckaroo\Model\Method\AbstractMethod;
use TIG\Buckaroo\Model\Method\Giftcards;

class PushTest extends \TIG\Buckaroo\Test\BaseTest
{
    /**
     * @var \TIG\Buckaroo\Model\Push
     */
    protected $object;

    /**
     * @var \Mockery\MockInterface
     */
    protected $objectManager;

    /**
     * @var \Mockery\MockInterface
     */
    protected $request;

    /**
     * @var \Mockery\MockInterface
     */
    protected $helper;

    /**
     * @var \Mockery\MockInterface
     */
    protected $configAccount;

    /**
     * @var \Mockery\MockInterface
     */
    public $debugger;

    /**
     * @var \Mockery\MockInterface
     */
    public $orderSender;

    /**
     * Setup the standard mocks
     */
    public function setUp()
    {
        parent::setUp();

        $this->objectManager = \Mockery::mock(\Magento\Framework\ObjectManagerInterface::class);
        $this->request = \Mockery::mock(\Magento\Framework\Webapi\Rest\Request::class);
        $this->helper = \Mockery::mock(\TIG\Buckaroo\Helper\Data::class);
        $this->configAccount = \Mockery::mock(\TIG\Buckaroo\Model\ConfigProvider\Account::class);
        $this->debugger = \Mockery::mock(\TIG\Buckaroo\Debug\Debugger::class);

        /**
         * Needed to deal with __destruct
         */
        $this->debugger->shouldReceive('log')->andReturnSelf();

        $this->orderSender = \Mockery::mock(\Magento\Sales\Model\Order\Email\Sender\OrderSender::class);

        /**
         * We are using the temporary class declared above, but it could be any class extending from the AbstractMethod
         * class.
         */
        $this->object = $this->objectManagerHelper->getObject(
            \TIG\Buckaroo\Model\Push::class,
            [
                'objectManager' => $this->objectManager,
                'request' => $this->request,
                'helper' => $this->helper,
                'configAccount' => $this->configAccount,
                'debugger' => $this->debugger,
                'orderSender' => $this->orderSender,
            ]
        );
    }

    /**
     * @return array
     */
    public function giftcardPartialPaymentProvider()
    {
        return [
            'processed partial giftcard payment' => [
                Giftcards::PAYMENT_METHOD_CODE,
                5,
                2,
                'abc',
                true
            ],
            'incorrect method code' => [
                'fake_method_code',
                4,
                1,
                'def',
                false
            ],
            'push amount equals order amount' => [
                Giftcards::PAYMENT_METHOD_CODE,
                3,
                6,
                'ghi',
                false
            ],
            'no related transaction key' => [
                Giftcards::PAYMENT_METHOD_CODE,
                8,
                7,
                null,
                false
            ],
        ];
    }

    /**
     * @param $methodCode
     * @param $orderAmount
     * @param $pushAmount
     * @param $relatedTransaction
     * @param $expected
     *
     * @dataProvider giftcardPartialPaymentProvider
     */
    public function testGiftcardPartialPayment($methodCode, $orderAmount, $pushAmount, $relatedTransaction, $expected)
    {
        $paymentMock = $this->getFakeMock(Payment::class)
            ->setMethods(['getMethod', 'setAdditionalInformation'])
            ->getMock();
        $paymentMock->expects($this->once())->method('getMethod')->willReturn($methodCode);
        $paymentMock->method('setAdditionalInformation');

        $orderMock = $this->getFakeMock(Order::class)->setMethods(['getPayment', 'getGrandTotal'])->getMock();
        $orderMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $orderMock->method('getGrandTotal')->willReturn($orderAmount);

        $this->object->order = $orderMock;
        $this->object->postData = [
            'brq_amount' => $pushAmount,
            'brq_relatedtransaction_partialpayment' => $relatedTransaction
        ];

        $result = $this->invoke('giftcardPartialPayment', $this->object);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param $state
     *
     * @dataProvider processPendingPaymentPushDataProvider
     */
    public function testProcessPendingPaymentPush($state)
    {
        $message = 'testMessage';
        $status = 'testStatus';

        $expectedDescription = 'Payment push status : '.$message;

        $pendingPaymentState = Order::STATE_PROCESSING;

        $orderMock = \Mockery::mock(Order::class);
        $orderMock->shouldReceive('getState')->atLeast(1)->andReturn($state);
        $orderMock->shouldReceive('getStore')->andReturn(0);
        $orderMock->shouldReceive('getPayment')->andReturnSelf();
        $orderMock->shouldReceive('getMethodInstance')->andReturnSelf();
        $orderMock->shouldReceive('getEmailSent')->andReturn(true);

        if ($state == $pendingPaymentState) {
            $orderMock->shouldReceive('addStatusHistoryComment')->once()->with($expectedDescription, $status);
        } else {
            $orderMock->shouldReceive('addStatusHistoryComment')->once()->with($expectedDescription);
        }
        $this->object->order = $orderMock;

        $this->assertTrue($this->object->processPendingPaymentPush($status, $message));
    }

    public function processPendingPaymentPushDataProvider()
    {
        return [
            [
                Order::STATE_PROCESSING,
            ],
            [
                Order::STATE_NEW,
            ],
        ];
    }

    /**
     * @param $state
     * @param $canCancel
     * @param $cancelOnFailed
     *
     * @dataProvider processFailedPushDataProvider
     */
    public function testProcessFailedPush($state, $canCancel, $cancelOnFailed)
    {
        $message = 'testMessage';
        $status = 'testStatus';

        $expectedDescription = 'Payment status : '.$message;

        $canceledPaymentState = Order::STATE_CANCELED;

        $this->configAccount->shouldReceive('getCancelOnFailed')->andReturn($cancelOnFailed);

        $orderMock = $this->getFakeMock(\Magento\Sales\Model\Order::class)
            ->setMethods(['getState', 'getStore', 'addStatusHistoryComment', 'canCancel', 'getPayment', 'cancel', 'save'])
            ->getMock();
        $orderMock->expects($this->atLeastOnce())->method('getState')->willReturn($state);
        $orderMock->expects($this->once())->method('getStore')->willReturnSelf();

        $addHistoryCommentExpects = $orderMock->expects($this->once());
        $addHistoryCommentExpects->method('addStatusHistoryComment');

        if ($state == $canceledPaymentState) {
            $addHistoryCommentExpects->with($expectedDescription, $status);
        } else {
            $addHistoryCommentExpects->with($expectedDescription);
        }

        if ($cancelOnFailed) {
            $methodInstanceMock = $this->getMockForAbstractClass(MethodInterface::class);
            $paymentMock = $this->getMockBuilder(Payment::class)
                ->disableOriginalConstructor()
                ->setMethods(['getMethodInstance'])
                ->getMock();
            $paymentMock->method('getMethodInstance')->willReturn($methodInstanceMock);

            $orderMock->expects($this->once())->method('canCancel')->willReturn($canCancel);
            $orderMock->expects($this->exactly((int)$canCancel))->method('getPayment')->willReturn($paymentMock);

            if ($canCancel) {
                $this->debugger->shouldReceive('addToMessage')->withAnyArgs()->andReturnSelf();
                $this->debugger->shouldReceive('log')->andReturnSelf();

                $orderMock->expects($this->once())->method('cancel')->willReturnSelf();
                $orderMock->expects($this->once())->method('save')->willReturnSelf();
            }
        }

        $this->object->order = $orderMock;

        $this->assertTrue($this->object->processFailedPush($status, $message));
    }

    public function processFailedPushDataProvider()
    {
        return [
            [
                Order::STATE_CANCELED,
                true,
                true,
            ],
            [
                Order::STATE_CANCELED,
                true,
                false,
            ],
            [
                Order::STATE_CANCELED,
                false,
                true,
            ],
            [
                Order::STATE_CANCELED,
                false,
                false,
            ],
            [
                Order::STATE_PROCESSING,
                true,
                true,
            ],
            [
                Order::STATE_PROCESSING,
                true,
                false,
            ],
            [
                Order::STATE_PROCESSING,
                false,
                true,
            ],
            [
                Order::STATE_PROCESSING,
                false,
                false,
            ],
        ];
    }

    /**
     * @param      $state
     * @param      $orderEmailSent
     * @param      $sendOrderConfirmationEmail
     * @param      $paymentAction
     * @param      $amount
     * @param bool                       $textAmount
     * @param bool                       $autoInvoice
     * @param bool                       $orderCanInvoice
     * @param bool                       $orderHasInvoices
     * @param array                      $postData
     *
     * @dataProvider processSucceededPushDataProvider
     */
    public function testProcessSucceededPush(
        $state,
        $orderEmailSent,
        $sendOrderConfirmationEmail,
        $paymentAction,
        $amount,
        $textAmount,
        $autoInvoice = false,
        $orderCanInvoice = false,
        $orderHasInvoices = false,
        $postData = []
    ) {
        $message = 'testMessage';
        $status = 'testStatus';

        /**
         * Only orders with this state should have their status updated
         */
        $successPaymentState = Order::STATE_PROCESSING;

        /**
         * Set config values on config provider mock
         */
        $this->configAccount->shouldReceive('getOrderConfirmationEmail')
            ->andReturn($sendOrderConfirmationEmail);
        $this->configAccount->shouldReceive('getAutoInvoice')->andReturn($autoInvoice);
        $this->configAccount->shouldReceive('getInvoiceEmail');

        /**
         * Build an order mock and set several non mandatory method calls
         */
        $orderMock = \Mockery::mock(Order::class);
        $orderMock->shouldReceive('getEmailSent')->andReturn($orderEmailSent);
        $orderMock->shouldReceive('getGrandTotal')->andReturn($amount);
        $orderMock->shouldReceive('getBaseGrandTotal')->andReturn($amount);
        $orderMock->shouldReceive('getTotalDue')->andReturn($amount);
        $orderMock->shouldReceive('getStore')->andReturnSelf();

        /**
         * The order state has to be checked at least once
         */
        $orderMock->shouldReceive('getState')->atLeast(1)->andReturn($state);

        /**
         * If order email is not sent and order email should be sent, expect sending of order email
         */
        if (!$orderEmailSent && $sendOrderConfirmationEmail) {
            $this->orderSender->shouldReceive('send')->with($orderMock);
        }

        /**
         * Build a payment mock and set the payment action
         */
        $paymentMock = \Mockery::mock(Payment::class);
        $paymentMock->shouldReceive('getMethodInstance')->andReturnSelf();
        $paymentMock->shouldReceive('getConfigData')->with('payment_action')->andReturn($paymentAction);
        $paymentMock->shouldReceive('getConfigData');
        $paymentMock->shouldReceive('getMethod');
        $paymentMock->shouldReceive('setTransactionAdditionalInfo');
        $paymentMock->shouldReceive('setTransactionId');
        $paymentMock->shouldReceive('setParentTransactionId');
        $paymentMock->shouldReceive('setAdditionalInformation');

        /**
         * Build a currency mock
         */
        $currencyMock = \Mockery::mock(\Magento\Directory\Model\Currency::class);
        $currencyMock->shouldReceive('formatTxt')->andReturn($textAmount);

        /**
         * Update order mock with payment and currency mock
         */
        $orderMock->shouldReceive('getPayment')->andReturn($paymentMock);
        $orderMock->shouldReceive('getBaseCurrency')->andReturn($currencyMock);

        /**
         * If no auto invoicing is required, or if auto invoice is required and the order can be invoiced and
         *  has no invoices, expect a status update
         */
        if (!$autoInvoice || ($autoInvoice && $orderCanInvoice && !$orderHasInvoices)) {
            if ($paymentAction != 'authorize') {
                $expectedDescription = 'Payment status : <strong>' . $message . "</strong><br/>";
                $expectedDescription .= 'Total amount of ' . $textAmount . ' has been paid';
            } else {
                $expectedDescription = 'Authorization status : <strong>' . $message . "</strong><br/>";
                $expectedDescription .= 'Total amount of ' . $textAmount . ' has been ' .
                    'authorized. Please create an invoice to capture the authorized amount.';
            }

            /**
             * Only orders with the success state should have their status updated
             */
            if ($state == $successPaymentState) {
                $orderMock->shouldReceive('addStatusHistoryComment')->once()->with($expectedDescription, $status);
            } else {
                $orderMock->shouldReceive('addStatusHistoryComment')->once()->with($expectedDescription);
            }
        }

        /**
         * If autoInvoice is required, also test protected method saveInvoice
         */
        if ($autoInvoice) {
            $orderMock->shouldReceive('canInvoice')->andReturn($orderCanInvoice);
            $orderMock->shouldReceive('hasInvoices')->andReturn($orderHasInvoices);

            if (!$orderCanInvoice || $orderHasInvoices) {
                /**
                 * If order cannot be invoiced or if order already has invoices, expect an exception
                 */
                $this->setExpectedException(\TIG\Buckaroo\Exception::class);
                $this->debugger->shouldReceive('addToMessage')->withAnyArgs();
            } else {

                /**
                 * Payment should receive register capture notification only once and payment should be saved
                 */
                $paymentMock->shouldReceive('registerCaptureNotification')->once()->with($amount);
                $paymentMock->shouldReceive('save')->once()->withNoArgs();

                /**
                 * Order should be saved at least once
                 */
                $orderMock->shouldReceive('save')->atLeast(1)->withNoArgs();

                $this->object->postData = $postData;

                $invoiceMock = \Mockery::mock(\Magento\Sales\Model\Order\Invoice::class);
                $invoiceMock->shouldReceive('getEmailSent')->andReturn(false);

                /**
                 * Invoice collection should be array iterable so a simple array is used for a mock collection
                 */
                $orderMock->shouldReceive('getInvoiceCollection')->andReturn([$invoiceMock]);

                /**
                 * If key brq_transactions is set in postData, invoice should expect a transaction id to be set
                 */
                if (isset($postData['brq_transactions'])) {
                    $invoiceMock->shouldReceive('setTransactionId')
                        ->with($postData['brq_transactions'])
                        ->andReturnSelf();
                    $invoiceMock->shouldReceive('save');
                }
            }
        }


        $this->helper->shouldReceive('getTransactionAdditionalInfo');

        $this->object->order = $orderMock;

        /**
         * @noinspection PhpUndefinedMethodInspection
         */
        $this->assertTrue($this->object->processSucceededPush($status, $message));
    }

    public function processSucceededPushDataProvider()
    {
        return [
            /**
             * CANCELED && AUTHORIZE
             */
            0 => [
                /**
                 * $state
                 */
                Order::STATE_CANCELED,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                false,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            1 => [
                /**
                 * $state
                 */
                Order::STATE_CANCELED,
                /**
                 * $orderEmailSent
                 */
                false,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                false,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            2 => [
                /**
                 * $state
                 */
                Order::STATE_CANCELED,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                false,
                /**
                 * $paymentAction
                 */
                'authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                false,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            3 => [
                /**
                 * $state
                 */
                Order::STATE_CANCELED,
                /**
                 * $orderEmailSent
                 */
                false,
                /**
                 * $sendOrderConfirmationEmail
                 */
                false,
                /**
                 * $paymentAction
                 */
                'authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                false,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            /**
             * CANCELED && NOT AUTHORIZE
             */
            4 => [
                /**
                 * $state
                 */
                Order::STATE_CANCELED,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                false,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            5 => [
                /**
                 * $state
                 */
                Order::STATE_CANCELED,
                /**
                 * $orderEmailSent
                 */
                false,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                false,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            6 => [
                /**
                 * $state
                 */
                Order::STATE_CANCELED,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                false,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                false,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            7 => [
                /**
                 * $state
                 */
                Order::STATE_CANCELED,
                /**
                 * $orderEmailSent
                 */
                false,
                /**
                 * $sendOrderConfirmationEmail
                 */
                false,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                false,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            /**
             * CANCELED && NOT AUTHORIZE && AUTO INVOICE
             */
            8 => [
                /**
                 * $state
                 */
                Order::STATE_CANCELED,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                true,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            9 => [
                /**
                 * $state
                 */
                Order::STATE_CANCELED,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                true,
                /**
                 * $orderCanInvoice
                 */
                true,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                ['brq_transactions' => 'test_transaction_id'],
            ],
            10 => [
                /**
                 * $state
                 */
                Order::STATE_CANCELED,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                true,
                /**
                 * $orderCanInvoice
                 */
                true,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                ['brq_transactions' => 'test_transaction_id'],
            ],
            11 => [
                /**
                 * $state
                 */
                Order::STATE_CANCELED,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                true,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                true,
                /**
                 * $postData
                 */
                [],
            ],
            12 => [
                /**
                 * $state
                 */
                Order::STATE_CANCELED,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                true,
                /**
                 * $orderCanInvoice
                 */
                true,
                /**
                 * $orderHasInvoices
                 */
                true,
                /**
                 * $postData
                 */
                [],
            ],
            /**
             * PROCESSING && AUTHORIZE
             */
            13 => [
                /**
                 * $state
                 */
                Order::STATE_PROCESSING,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                false,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            14 => [
                /**
                 * $state
                 */
                Order::STATE_PROCESSING,
                /**
                 * $orderEmailSent
                 */
                false,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                false,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            15 => [
                /**
                 * $state
                 */
                Order::STATE_PROCESSING,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                false,
                /**
                 * $paymentAction
                 */
                'authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                false,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            16 => [
                /**
                 * $state
                 */
                Order::STATE_PROCESSING,
                /**
                 * $orderEmailSent
                 */
                false,
                /**
                 * $sendOrderConfirmationEmail
                 */
                false,
                /**
                 * $paymentAction
                 */
                'authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                false,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            /**
             * PROCESSING && NOT AUTHORIZE
             */
            17 => [
                /**
                 * $state
                 */
                Order::STATE_PROCESSING,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                false,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            18 => [
                /**
                 * $state
                 */
                Order::STATE_PROCESSING,
                /**
                 * $orderEmailSent
                 */
                false,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                false,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            19 => [
                /**
                 * $state
                 */
                Order::STATE_PROCESSING,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                false,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                false,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            20 => [
                /**
                 * $state
                 */
                Order::STATE_PROCESSING,
                /**
                 * $orderEmailSent
                 */
                false,
                /**
                 * $sendOrderConfirmationEmail
                 */
                false,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                false,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            /**
             * PROCESSING && NOT AUTHORIZE && AUTO INVOICE
             */
            21 => [
                /**
                 * $state
                 */
                Order::STATE_PROCESSING,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                true,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                [],
            ],
            22 => [
                /**
                 * $state
                 */
                Order::STATE_PROCESSING,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                true,
                /**
                 * $orderCanInvoice
                 */
                true,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                ['brq_transactions' => 'test_transaction_id'],
            ],
            23 => [
                /**
                 * $state
                 */
                Order::STATE_PROCESSING,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                true,
                /**
                 * $orderCanInvoice
                 */
                true,
                /**
                 * $orderHasInvoices
                 */
                false,
                /**
                 * $postData
                 */
                ['brq_transactions' => 'test_transaction_id'],
            ],
            24 => [
                /**
                 * $state
                 */
                Order::STATE_PROCESSING,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                true,
                /**
                 * $orderCanInvoice
                 */
                false,
                /**
                 * $orderHasInvoices
                 */
                true,
                /**
                 * $postData
                 */
                [],
            ],
            25 => [
                /**
                 * $state
                 */
                Order::STATE_PROCESSING,
                /**
                 * $orderEmailSent
                 */
                true,
                /**
                 * $sendOrderConfirmationEmail
                 */
                true,
                /**
                 * $paymentAction
                 */
                'not_authorize',
                /**
                 * $amount
                 */
                '15.95',
                /**
                 * $textAmount
                 */
                '$15.95',
                /**
                 * $autoInvoice
                 */
                true,
                /**
                 * $orderCanInvoice
                 */
                true,
                /**
                 * $orderHasInvoices
                 */
                true,
                /**
                 * $postData
                 */
                [],
            ],
        ];
    }
}
