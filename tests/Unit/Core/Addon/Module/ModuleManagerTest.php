<?php
/*
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2016 PrestaShop SA
 *  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
namespace PrestaShop\PrestaShop\Tests\Core\Addon\Module;

use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManager;

class ModuleManagerTest extends \PHPUnit_Framework_TestCase
{
    const UNINSTALLED_MODULE = "uninstalled-module";
    const INSTALLED_MODULE = "installed-module";

    private $moduleManager;
    private $adminModuleProviderS;
    private $moduleProviderS; // S means "Stub"
    private $moduleUpdaterS;
    private $moduleRepositoryS;
    private $employeeS;

    public function setUp()
    {
        // Mocks
        $this->initMocks();
        $this->moduleManager = new ModuleManager(
            $this->adminModuleProviderS,
            $this->moduleProviderS,
            $this->moduleUpdaterS,
            $this->moduleRepositoryS,
            $this->employeeS
        );
    }

    public function tearDown()
    {
        // destroy Mocks
        $this->destroyMocks();
        $this->moduleManager = null;
    }

    public function testInstallSuccessful()
    {
        $this->assertTrue($this->moduleManager->install(self::UNINSTALLED_MODULE));
        $this->setExpectedException('Exception', sprintf('The module %s is already installed.', self::INSTALLED_MODULE));
        $this->moduleManager->install(self::INSTALLED_MODULE);
    }

    public function testUninstallSuccessful()
    {
        $this->assertTrue($this->moduleManager->uninstall(self::INSTALLED_MODULE));
        $this->setExpectedException('Exception', 'You are not allowed to uninstall this module.');
        $this->assertFalse($this->moduleManager->uninstall(self::UNINSTALLED_MODULE));
    }

    public function testUpgradeSuccessful()
    {
        $this->assertTrue($this->moduleManager->upgrade(self::INSTALLED_MODULE));
        $this->setExpectedException('Exception', 'You are not allowed to upgrade this module.');
        $this->moduleManager->upgrade(self::UNINSTALLED_MODULE);
    }

    public function testDisableSuccessful()
    {
        $this->assertTrue($this->moduleManager->disable(self::INSTALLED_MODULE));
        $this->setExpectedException('Exception', 'You are not allowed to disable this module.');
        $this->assertFalse($this->moduleManager->disable(self::UNINSTALLED_MODULE));
    }

    public function testEnableSuccessful()
    {
        $this->assertTrue($this->moduleManager->enable(self::INSTALLED_MODULE));
        $this->setExpectedException('Exception', 'You are not allowed to enable this module.');
        $this->assertFalse($this->moduleManager->enable(self::UNINSTALLED_MODULE));
    }

    public function testResetSuccessful()
    {
        $this->assertTrue($this->moduleManager->reset(self::INSTALLED_MODULE));
        $this->setExpectedException('Exception', 'You are not allowed to reset this module.');
        $this->assertFalse($this->moduleManager->reset(self::UNINSTALLED_MODULE));
    }

    public function testIsEnabled()
    {
        $this->assertTrue($this->moduleManager->isEnabled(self::INSTALLED_MODULE));
        $this->assertFalse($this->moduleManager->isEnabled(self::UNINSTALLED_MODULE));
    }

    public function testIsInstalled()
    {
        $this->assertTrue($this->moduleManager->isInstalled(self::INSTALLED_MODULE));
        $this->assertFalse($this->moduleManager->isInstalled(self::UNINSTALLED_MODULE));
    }

    public function testRemoveModuleFromDisk()
    {
        $modules = [self::INSTALLED_MODULE, self::UNINSTALLED_MODULE];
        foreach ($modules as $module) {
            $this->assertSame($this->moduleManager->removeModuleFromDisk($module), $this->moduleUpdaterS->removeModuleFromDisk($module));
        }
    }

    private function initMocks()
    {
        $this->mockAdminModuleProvider();
        $this->mockModuleProvider();
        $this->mockModuleUpdater();
        $this->mockModuleRepository();
        $this->mockEmployee();
    }

    private function mockAdminModuleProvider()
    {
        $this->adminModuleProviderS = $this->getMockBuilder('PrestaShop\PrestaShop\Adapter\Module\AdminModuleDataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $installedModule = [
            self::INSTALLED_MODULE, [
                'installed' => 1,
                'active' => true
            ]
        ];

        $nonInstalledModule = [
            self::UNINSTALLED_MODULE, [
                'installed' => 0,
                'active' => false
            ]
        ];

        $findByNameReturnValues = [
            $installedModule,
            $nonInstalledModule
        ];
        $this->adminModuleProviderS
            ->method('findByName')
            ->will($this->returnValueMap($findByNameReturnValues));
    }

    private function mockModuleProvider()
    {
        $providerAuthorizations = [
            [
                'uninstall', self::INSTALLED_MODULE, true,
            ],
            [
                'uninstall', self::UNINSTALLED_MODULE, false
            ],
            [
                'configure', self::INSTALLED_MODULE, true,
            ],
            [
                'configure', self::UNINSTALLED_MODULE, false
            ]
        ];
        $this->moduleProviderS = $this->getMockBuilder('PrestaShop\PrestaShop\Adapter\Module\ModuleDataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->moduleProviderS
            ->method('can')
            ->will($this->returnValueMap($providerAuthorizations));

        $isInstalledValues = [
            [
                self::INSTALLED_MODULE, true
            ],
            [
                self::UNINSTALLED_MODULE, false
            ]
        ];
        $this->moduleProviderS
            ->method('isInstalled')
            ->will($this->returnValueMap($isInstalledValues));

        $isEnabledValues = [
            [self::INSTALLED_MODULE, true],
            [self::UNINSTALLED_MODULE, false]
        ];

        $this->moduleProviderS
            ->method('isEnabled')
            ->will($this->returnValueMap($isEnabledValues));
    }

    private function mockModuleUpdater()
    {
        $this->moduleUpdaterS = $this->getMockBuilder('PrestaShop\PrestaShop\Adapter\Module\ModuleDataUpdater')
            ->disableOriginalConstructor()
            ->getMock();
        $this->moduleUpdaterS
            ->method('removeModuleFromDisk')
            ->willReturn(true);
        $this->moduleUpdaterS
            ->method('setModuleOnDiskFromAddons')
            ->willReturn(true);
        $this->moduleUpdaterS
            ->method('upgrade')
            ->willReturn(true);
    }

    private function mockModuleRepository()
    {
        $moduleS = $this->getMockBuilder('PrestaShop\PrestaShop\Adapter\Module\Module')
            ->disableOriginalConstructor()
            ->getMock();
        $moduleS
            ->method('onInstall')
            ->willReturn(true);
        $moduleS
            ->method('onUninstall')
            ->willReturn(true);
        $moduleS
            ->method('onDisable')
            ->willReturn(true);
        $moduleS
            ->method('onEnable')
            ->willReturn(true);
        $moduleS
            ->method('onReset')
            ->willReturn(true);

        $this->moduleRepositoryS = $this->getMockBuilder('PrestaShop\PrestaShop\Core\Addon\Module\ModuleRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->moduleRepositoryS
            ->method('getModule')
            ->willReturn($moduleS);
    }

    private function mockEmployee()
    {
        /* this is a super admin */
        $this->employeeS = $this->getMockBuilder('Employee')
            ->disableOriginalConstructor()
            ->getMock();

        $this->employeeS
            ->method('can')
            ->willReturn(true);
    }

    private function destroyMocks()
    {
        $this->adminModuleProviderS = null;
        $this->moduleProviderS = null;
        $this->moduleUpdaterS = null;
        $this->moduleUpdaterS = null;
        $this->employeeS = null;
    }
}
