<?php

namespace yii2tech\tests\unit\ar\softdelete\data;

use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * @property int $id
 * @property int $categoryId
 * @property string $name
 * @property bool $isDeleted
 * @property int $deletedAt
 * @property int $version
 *
 * @property Category $category
 */
class Item extends ActiveRecord
{
    /**
     * @var bool whether to throw {@see onDeleteExceptionClass} exception on {@see delete()}
     */
    public $throwOnDeleteException = false;
    /**
     * @var string class name of the exception to be thrown on delete.
     */
    public $onDeleteExceptionClass = 'yii\db\IntegrityException';


    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'softDelete' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'isDeleted' => true
                ],
                'allowDeleteCallback' => function ($model) {
                    return $model->name === 'allow-delete';
                },
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'Item';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['name', 'required'],
            ['categoryId', 'numeric'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeDelete()
    {
        if ($this->throwOnDeleteException) {
            $className = $this->onDeleteExceptionClass;
            $exception = new $className('Emulation');
            throw $exception;
        }
        return parent::beforeDelete();
    }

    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'categoryId']);
    }
}