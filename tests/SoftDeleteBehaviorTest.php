<?php

namespace yii2tech\tests\unit\ar\softdelete;

use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\tests\unit\ar\softdelete\data\Item;

class SoftDeleteBehaviorTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        Item::$softDeleteBehaviorConfig = [];
    }

    public function testSoftDelete()
    {
        /* @var $item Item|SoftDeleteBehavior */
        $item = Item::findOne(2);

        $result = $item->softDelete();

        $this->assertEquals(1, $result);
        $this->assertEquals(true, $item->isDeleted);
    }

    public function testReplaceDelete()
    {
        Item::$softDeleteBehaviorConfig = [
            'replaceRegularDelete' => true
        ];

        /* @var $item Item|SoftDeleteBehavior */
        $item = Item::findOne(2);
        $item->delete();

        $this->assertEquals(true, $item->isDeleted);
        $this->assertEquals(2, Item::find()->count());
    }

    /**
     * @depends testSoftDelete
     */
    public function testAllowDelete()
    {
        Item::$softDeleteBehaviorConfig = [
            'replaceRegularDelete' => true
        ];

        /* @var $item Item|SoftDeleteBehavior */
        $item = Item::findOne(1);
        $item->name = 'allow-delete';
        $item->softDelete();

        $this->assertEquals(1, Item::find()->count());
    }

    /**
     * @depends testSoftDelete
     */
    public function testRestore()
    {
        /* @var $item Item|SoftDeleteBehavior */
        $item = Item::findOne(2);

        $item->softDelete();
        $result = $item->restore();

        $this->assertEquals(1, $result);
        $this->assertEquals(false, $item->isDeleted);
    }

    /**
     * @depends testRestore
     */
    public function testCallback()
    {
        Item::$softDeleteBehaviorConfig = [
            'softDeleteAttributeValues' => [
                'deletedAt' => function() {
                    return time();
                }
            ],
            'restoreAttributeValues' => [
                'deletedAt' => function() {
                    return null;
                }
            ],
        ];

        /* @var $item Item|SoftDeleteBehavior */
        $item = Item::findOne(1);
        $item->softDelete();

        $this->assertTrue($item->deletedAt >= time());

        /* @var $item Item|SoftDeleteBehavior */
        $item = Item::findOne(1);
        $item->restore();

        $this->assertNull($item->deletedAt);
    }

    /**
     * @depends testSoftDelete
     */
    public function testSafeDelete()
    {
        /* @var $item Item|SoftDeleteBehavior */
        /* @var $behavior SoftDeleteBehavior */

        // actual delete
        $item = Item::findOne(1);
        $result = $item->safeDelete();

        $this->assertEquals(1, $result);
        $this->assertNull(Item::findOne(1));

        // fallback
        $item = Item::findOne(2);
        $item->throwOnDeleteException = true;
        $result = $item->safeDelete();

        $this->assertEquals(1, $result);
        $item = Item::findOne(2);
        $this->assertNotNull($item);
        $this->assertEquals(true, $item->isDeleted);

        // custom exception class
        $item = Item::findOne(2);
        $item->throwOnDeleteException = true;
        $item->onDeleteExceptionClass = 'yii\base\InvalidValueException';
        $behavior = $item->getBehavior('softDelete');
        $behavior->deleteFallbackException = $item->onDeleteExceptionClass;

        $item->safeDelete();
        $this->assertNotNull(Item::findOne(2));
        $this->assertEquals(true, $item->isDeleted);

        $item->onDeleteExceptionClass = 'yii\db\IntegrityException';

        try {
            $item->isDeleted = false;
            $item->safeDelete();
            $this->assertTrue(false, 'No exception thrown');
        } catch (\Exception $exception) {
            $this->assertEquals('yii\db\IntegrityException', get_class($exception));
            $this->assertEquals(false, $item->isDeleted);
        }
    }
}