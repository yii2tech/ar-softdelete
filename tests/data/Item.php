<?php

namespace yii2tech\tests\unit\ar\softdelete\data;

use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * @property int $id
 * @property string $name
 * @property bool $isDeleted
 * @property int $deletedAt
 */
class Item extends ActiveRecord
{
    /**
     * @var bool whether to throw [[onDeleteExceptionClass]] exception on [[delete()]]
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
            'soft-delete' => [
                '__class' => SoftDeleteBehavior::class,
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
}