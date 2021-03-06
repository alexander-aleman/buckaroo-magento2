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
 * @copyright Copyright (c) 2015 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license   http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
namespace TIG\Buckaroo\Test\Unit\Model\Config\Source;

use TIG\Buckaroo\Model\Config\Source\Enablemode;
use TIG\Buckaroo\Test\BaseTest;

class EnableModeTest extends BaseTest
{
    /**
     * @var Enablemode
     */
    protected $object;

    /**
     * @var array
     */
    protected $shouldHaveOptions;

    public function setUp()
    {
        parent::setUp();

        $this->object = new Enablemode();

        $this->shouldHaveOptions = [
            0 => __('Off'),
            1 => __('Test'),
            2 => __('Live'),
        ];
    }

    public function testToOptionArray()
    {
        $options = $this->object->toOptionArray();
        $this->assertTrue($options >= 3);

        foreach ($this->shouldHaveOptions as $key => $shouldHaveOptionValue) {
            foreach ($options as $option) {
                if ($option['value'] == $key) {
                    /**
                     * @noinspection PhpUndefinedMethodInspection
                     */
                    $this->assertEquals($option['label']->getText(), $shouldHaveOptionValue->getText());
                    break;
                }
            }
        }
    }

    public function testToArray()
    {
        $options = $this->object->toArray();

        foreach ($options as $key => $option) {
            if (array_key_exists($key, $this->shouldHaveOptions)) {
                /**
                 * @noinspection PhpUndefinedMethodInspection
                 */
                $this->assertEquals($option->getText(), $this->shouldHaveOptions[$key]->getText());
            }
        }
    }
}
