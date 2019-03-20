<?php

namespace yii2tech\tests\unit\ar\softdelete;

use yii\helpers\ArrayHelper;
use Yii;

/**
 * Base class for the test cases.
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication();

        $this->setupTestDbData();
    }

    protected function tearDown()
    {
        $this->destroyApplication();
    }

    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($config = [], $appClass = '\yii\console\Application')
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => $this->getVendorPath(),
            'components' => [
                'db' => [
                    'class' => 'yii\db\Connection',
                    'dsn' => 'sqlite::memory:',
                ],
            ],
        ], $config));
    }

    /**
     * @return string vendor path
     */
    protected function getVendorPath()
    {
        return dirname(__DIR__) . '/vendor';
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        Yii::$app = null;
    }

    /**
     * Setup tables for test ActiveRecord
     */
    protected function setupTestDbData()
    {
        $db = Yii::$app->getDb();

        // Structure :

        $db->createCommand()
            ->createTable('Category', [
                'id' => 'pk',
                'name' => 'string',
                'isDeleted' => 'boolean',
            ])
            ->execute();

        $db->createCommand()
            ->createTable('Item', [
                'id' => 'pk',
                'categoryId' => 'integer',
                'name' => 'string',
                'isDeleted' => 'boolean DEFAULT 0',
                'deletedAt' => 'integer',
                'version' => 'integer',
            ])
            ->execute();

        // Data :
        $categoryIds = [
            $db->getSchema()->insert('Category', ['name' => 'category1', 'isDeleted' => 0])['id'],
            $db->getSchema()->insert('Category', ['name' => 'category2', 'isDeleted' => 0])['id'],
            $db->getSchema()->insert('Category', ['name' => 'category3', 'isDeleted' => 0])['id'],
        ];

        $db->createCommand()->batchInsert('Item', ['name', 'categoryId'], [
            ['item1', $categoryIds[0]],
            ['item2', $categoryIds[1]],
        ])->execute();
    }
}
