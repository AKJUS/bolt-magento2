<?php
// phpcs:ignoreFile
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

namespace Bolt\Boltpay\Test\Unit\Setup;

use Bolt\Boltpay\Setup\UpgradeSchema;
use Bolt\Boltpay\Test\Unit\BoltTestCase;
use Bolt\Boltpay\Test\Unit\TestHelper;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

/**
 * @coversDefaultClass \Bolt\Boltpay\Setup\UpgradeSchema
 */
class UpgradeSchemaTest extends BoltTestCase
{
    /**
     * @var AdapterInterface|MockObject mocked instance of the database connection class
     */
    private $dbAdapter;

    /**
     * @var SchemaSetupInterface|MockObject mocked instance of the setup class, provided to {@see \Bolt\Boltpay\Setup\UpgradeSchema::upgrade}
     */
    private $schemaSetup;

    /**
     * @var Table|MockObject mocked instance of the database table model
     */
    private $customTable;

    /**
     * @var MockObject|UpgradeSchema mocked instance of the class tested
     */
    private $currentMock;

    /**
     * Setup test dependencies, called before each test
     */
    protected function setUpInternal()
    {
        $this->dbAdapter = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->schemaSetup = $this->getMockBuilder(SchemaSetupInterface::class)
            ->setMethods(
                [
                    'addColumn',
                    'dropColumn',
                    'addIndex',
                    'isTableExists',
                    'getIndexList',
                    'setComment',
                    'createTable',
                    'newTable',
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customTable = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->initCurrentMock();
    }

    /**
     * Sets mocked instance of the tested class
     *
     * @param array $methods to be stubbed
     */
    private function initCurrentMock($methods = [])
    {
        $mockBuilder = $this->getMockBuilder(UpgradeSchema::class);
        if ($methods) {
            $mockBuilder->setMethods($methods);
        } else {
            $mockBuilder->enableProxyingToOriginalMethods();
        }
        $this->currentMock = $mockBuilder->getMock();
    }

    /**
     * @test
     * that upgrade will:
     * 1. Start setup
     * 2. Add bolt_parent_quote_id, bolt_reserved_order_id, bolt_is_backend_order, bolt_checkout_type columns to quote table
     * 3. Add index for bolt_parent_quote_id column to quote table
     * 4. Setup customer credit cards table by calling {@see \Bolt\Boltpay\Setup\UpgradeSchema::setupFeatureBoltCustomerCreditCardsTable}
     * 5. Setup webhook log table by calling {@see \Bolt\Boltpay\Setup\UpgradeSchema::setupWebhookLogTable}
     * 6. Update webhook log table by calling {@see \Bolt\Boltpay\Setup\UpgradeSchema::updateWebhookLogTable}
     * 7. Setup external customer entity table by calling {@see \Bolt\Boltpay\Setup\UpgradeSchema::setupExternalCustomerEntityTable}
     * 8. End setup
     *
     * @covers ::upgrade
     *
     * @throws ReflectionException if unable to create ModuleContextInterface mock
     */
    public function upgrade_always_upgradesDatabase()
    {
        $moduleContextMock = $this->createMock(ModuleContextInterface::class);

        $this->schemaSetup->expects(static::once())->method('startSetup');
        $this->schemaSetup->expects(static::atLeastOnce())->method('getConnection')->willReturnSelf();
        $quoteTable = 'quote';
        $boltWebhookTable = 'bolt_webhook_log';
        $this->schemaSetup->expects(static::atLeastOnce())->method('getTable')->willReturnCallback(
            function ($tableName) {
                return $tableName;
            }
        );

        $this->schemaSetup->expects(static::atLeastOnce())->method('addColumn')->withConsecutive(
            [
                $quoteTable,
                'bolt_parent_quote_id',
                [
                    'type'     => Table::TYPE_INTEGER,
                    'nullable' => true,
                    'default'  => null,
                    'unsigned' => true,
                    'comment'  => 'Original Quote ID'
                ],
            ],
            [
                $quoteTable,
                'bolt_reserved_order_id',
                [
                    'type'     => Table::TYPE_TEXT,
                    'length'   => 64,
                    'nullable' => true,
                    'comment'  => 'Bolt Reserved Order Id'
                ],
            ],
            [
                $quoteTable,
                'bolt_checkout_type',
                [
                    'type'     => Table::TYPE_SMALLINT,
                    'unsigned' => true,
                    'nullable' => false,
                    'default'  => '1',
                    'comment'  => '1 - multi-step, 2 - PPC, 3 - back office, 4 - PPC complete'
                ],
            ],
            [
                'sales_order',
                'bolt_transaction_reference',
                [
                    'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'   => 64,
                    'nullable' => true,
                    'comment'  => 'Bolt Transaction Reference'
                ],
            ],
            [
                $quoteTable,
                'bolt_dispatched',
                [
                    'type'     => Table::TYPE_BOOLEAN,
                    'nullable' => true,
                    'comment'  => 'Order dispatched flag'
                ]
            ],
            [
                $boltWebhookTable,
                'updated_at',
                [
                    'type'    => Table::TYPE_TIMESTAMP,
                    'comment' => 'Updated At'
                ],
            ]
        );

        $this->schemaSetup->expects(static::once())
            ->method('dropColumn')
            ->with($quoteTable, 'bolt_is_backend_order')
            ->willReturnSelf();

        $quoteUniqueHash = '98156307323252d52c6683671a73dff3';
        $this->schemaSetup->expects(static::exactly(2))
        ->method('getIdxName')
            ->withConsecutive(
                ['quote', ['bolt_parent_quote_id']],
                ['plugin_version_notification', ['latest_version'], 'primary']
            )
            ->willReturn(
                $quoteUniqueHash,
                'plugin_version_notification_latest_version_primary'
            );

        $this->schemaSetup->expects(static::exactly(2))
            ->method('addIndex')
            ->withConsecutive(
                ['quote', $quoteUniqueHash, ['bolt_parent_quote_id']],
                ['plugin_version_notification', 'plugin_version_notification_latest_version_primary', ['latest_version'], 'primary']
            )
            ->willReturnSelf();

        $this->schemaSetup->expects(static::atLeastOnce())
            ->method('isTableExists')
            ->willReturn(true);

        $this->schemaSetup->expects(static::atLeastOnce())->method('getIndexList')->willReturn([]);

        $this->schemaSetup->expects(static::once())->method('endSetup');

        $this->currentMock->upgrade($this->schemaSetup, $moduleContextMock);
    }

    /**
     * Data provider for {@see setupMethods_withTablesAlreadyCreated_doNotAlterTheDatabase}
     *
     * @return array[] containing tested method name and table name associated to the method
     */
    public function setupMethods_withTablesAlreadyCreatedProvider()
    {
        return [
            [
                'methodName' => 'setupWebhookLogTable',
                'tableName'  => 'bolt_webhook_log',
            ],
            [
                'methodName' => 'setupFeatureBoltCustomerCreditCardsTable',
                'tableName'  => 'bolt_customer_credit_cards',
            ],
            [
                'methodName' => 'setupExternalCustomerEntityTable',
                'tableName'  => 'bolt_external_customer_entity',
            ]
        ];
    }

    /**
     * @test
     * that
     * @see UpgradeSchema::setupWebhookLogTable
     * @see UpgradeSchema::setupFeatureBoltCustomerCreditCardsTable
     * don't alter the database if the tables were already created
     *
     * @covers ::setupWebhookLogTable
     * @covers ::setupFeatureBoltCustomerCreditCardsTable
     *
     * @dataProvider setupMethods_withTablesAlreadyCreatedProvider
     *
     * @param string $methodName method name to be tested
     * @param string $tableName  associated with the tested method
     *
     * @throws ReflectionException if $methodName method doesn't exist
     */
    public function setupMethods_withTablesAlreadyCreated_doNotAlterTheDatabase($methodName, $tableName)
    {
        $this->schemaSetup->expects(static::once())->method('getConnection')->willReturnSelf();
        $this->schemaSetup->expects(static::once())->method('isTableExists')->with($tableName)->willReturn(true);
        $this->schemaSetup->expects(static::never())->method(
            static::logicalAnd(
                static::logicalNot(static::equalTo('getConnection')),
                static::logicalNot(static::equalTo('isTableExists'))
            )
        );
        TestHelper::invokeMethod($this->currentMock, $methodName, [$this->schemaSetup]);
    }

    /**
     * @test
     * that setupWebhookLogTable creates bolt_webhook_log table if it does not exist already
     *
     * @covers ::setupWebhookLogTable
     *
     * @throws ReflectionException if setupWebhookLogTable method doesn't exist
     */
    public function setupWebhookLogTable_ifBoltWebhookLogTableDoesNotExist_createsTable()
    {
        $this->schemaSetup->expects(static::exactly(3))->method('getConnection')->willReturnSelf();
        $this->schemaSetup->expects(static::once())
            ->method('isTableExists')
            ->with('bolt_webhook_log')
            ->willReturn(false);

        $boltWebhookTable = 'bolt_webhook_log';
        $this->schemaSetup->expects(static::once())
            ->method('getTable')
            ->with('bolt_webhook_log')
            ->willReturn($boltWebhookTable);
        $this->schemaSetup->expects(static::once())->method('newTable')->with($boltWebhookTable)->willReturnSelf();
        $this->schemaSetup->expects(static::exactly(4))->method('addColumn')->withConsecutive(
            [
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'ID',
            ],
            [
                'transaction_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'transaction id',
            ],
            [
                'hook_type',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Hook type',
            ],
            [
                'number_of_missing_quote_failed_hooks',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' => '0'],
                'number of the missing quote failed hooks',
            ]
        )->willReturnSelf();

        $this->schemaSetup->expects(static::once())
            ->method('setComment')
            ->with('Bolt Webhook Log table')
            ->willReturn($this->customTable);

        $this->schemaSetup->expects(static::once())->method('createTable')->with($this->customTable)->willReturnSelf();

        TestHelper::invokeMethod($this->currentMock, 'setupWebhookLogTable', [$this->schemaSetup]);
    }

    /**
     * @test
     * that updateWebhookLogTable adds updated_at column to bolt_webhook_log table if it exists
     *
     * @covers ::updateWebhookLogTable
     *
     * @throws ReflectionException if updateWebhookLogTable method doesn't exist
     */
    public function updateWebhookLogTable_ifBoltWebhookLogTableExists_updatesWebhookLogTable()
    {
        $this->schemaSetup->expects(static::exactly(2))->method('getConnection')->willReturnSelf();

        $this->schemaSetup->expects(static::once())
            ->method('isTableExists')
            ->with('bolt_webhook_log')
            ->willReturn(true);

        $this->schemaSetup->expects(static::once())->method('getTable')->with('bolt_webhook_log')->willReturn('bolt_webhook_log');
        $this->schemaSetup->expects(static::once())->method('addColumn')->with(
            'bolt_webhook_log',
            'updated_at',
            [
                'type'    => Table::TYPE_TIMESTAMP,
                'comment' => 'Updated At',
            ]
        );

        TestHelper::invokeMethod($this->currentMock, 'updateWebhookLogTable', [$this->schemaSetup]);
    }

    /**
     * @test
     * that setupFeatureBoltCustomerCreditCardsTable creates bolt_customer_credit_cards table if it doesn't exist already
     *
     * @covers ::setupFeatureBoltCustomerCreditCardsTable
     *
     * @throws ReflectionException if setupFeatureBoltCustomerCreditCardsTable method doesn't exist
     */
    public function setupFeatureBoltCustomerCreditCardsTable_ifTableDoesNotExist_createsTheTable()
    {
        $this->dbAdapter
            ->expects(static::once())
            ->method('newTable')
            ->with('bolt_customer_credit_cards')
            ->willReturn($this->customTable);
        $this->schemaSetup->expects(static::any())->method('getConnection')->willReturn($this->dbAdapter);
        $this->schemaSetup->method('getTable')->willReturnArgument(0);

        $this->customTable->expects(static::once())->method('setComment')->with('Bolt customer credit cards')->willReturnSelf();
        $this->customTable->expects(static::exactly(5))->method('addColumn')->withConsecutive(
            [
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'ID'
            ],
            [
                'card_info',
                Table::TYPE_TEXT,
                Table::MAX_TEXT_SIZE,
                ['nullable' => false],
                'Card Info'
            ],
            [
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => false, 'unsigned' => true, 'nullable' => false, 'primary' => false],
                'Customer ID'
            ],
            [
                'consumer_id',
                Table::TYPE_TEXT,
                Table::DEFAULT_TEXT_SIZE,
                ['nullable' => false],
                'Consumer Id'
            ],
            [
                'credit_card_id',
                Table::TYPE_TEXT,
                Table::DEFAULT_TEXT_SIZE,
                ['nullable' => false],
                'Credit Card ID'
            ]
        )->willReturnSelf();
        $this->customTable->expects(static::once())->method('addForeignKey')->with(
            $this->schemaSetup->getFkName(
                'bolt_customer_credit_cards',
                'customer_id',
                'customer_entity',
                'entity_id'
            ),
            'customer_id',
            'customer_entity',
            'entity_id',
            Table::ACTION_CASCADE
        )->willReturnSelf();
        $this->dbAdapter->expects(static::once())->method('createTable')->with($this->customTable);
        TestHelper::invokeMethod($this->currentMock, 'setupFeatureBoltCustomerCreditCardsTable', [$this->schemaSetup]);
    }

    /**
     * @test
     * that updateWebhookLogTable will not try to alter bolt_webhook_log table if it doesn't exist
     *
     * @covers ::updateWebhookLogTable
     *
     * @throws ReflectionException if updateWebhookLogTable method doesn't exist
     */
    public function updateWebhookLogTable_ifTableDoesNotExist_doesNotAlterTheDatabase()
    {
        $this->schemaSetup->expects(static::once())->method('getConnection')->willReturnSelf();
        $this->schemaSetup->expects(static::once())->method('isTableExists')->with('bolt_webhook_log')->willReturn(false);
        // expect nothing apart from getConnection and isTableExists to be called
        $this->schemaSetup->expects(static::never())->method(
            static::logicalAnd(
                static::logicalNot(static::equalTo('getConnection')),
                static::logicalNot(static::equalTo('isTableExists'))
            )
        );
        TestHelper::invokeMethod($this->currentMock, 'updateWebhookLogTable', [$this->schemaSetup]);
    }

    /**
     * @test
     * that setupExternalCustomerEntityTable creates bolt_external_customer_entity table if it does not exist already
     *
     * @covers ::setupExternalCustomerEntityTable
     *
     * @throws ReflectionException if setupExternalCustomerEntityTable method doesn't exist
     */
    public function setupExternalCustomerEntityTable_ifBoltExternalCustomerEntityTableDoesNotExist_createsTable()
    {
        $this->schemaSetup->expects(static::exactly(3))->method('getConnection')->willReturnSelf();
        $this->schemaSetup->expects(static::once())
            ->method('isTableExists')
            ->with('bolt_external_customer_entity')
            ->willReturn(false);

        $boltExternalCustomerEntityTable = 'bolt_external_customer_entity';
        $this->schemaSetup->expects(static::once())
            ->method('getTable')
            ->with('bolt_external_customer_entity')
            ->willReturn($boltExternalCustomerEntityTable);
        $this->schemaSetup->expects(static::once())->method('newTable')->with($boltExternalCustomerEntityTable)->willReturnSelf();
        $this->schemaSetup->expects(static::exactly(3))->method('addColumn')->withConsecutive(
            [
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'ID',
            ],
            [
                'external_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'External ID',
            ],
            [
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Customer ID',
            ]
        )->willReturnSelf();

        $this->schemaSetup->expects(static::once())
            ->method('setComment')
            ->with('Bolt External Customer Entity table')
            ->willReturn($this->customTable);

        $this->schemaSetup->expects(static::once())->method('createTable')->with($this->customTable)->willReturnSelf();

        TestHelper::invokeMethod($this->currentMock, 'setupExternalCustomerEntityTable', [$this->schemaSetup]);
    }
}
