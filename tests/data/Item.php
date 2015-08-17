<?php

namespace yii2tech\tests\unit\ar\softdelete\data;

use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * @property integer $id
 * @property string $name
 * @property boolean $isDeleted
 * @property integer $deletedAt
 */
class Item extends ActiveRecord
{
    /**
     * @var array config for soft delete behavior
     */
    public static $softDeleteBehaviorConfig = [];

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'softDeleteBehavior' => array_merge(
                [
                    'class' => SoftDeleteBehavior::className(),
                    'softDeleteAttributeValues' => [
                        'isDeleted' => true
                    ],
                    'allowDeleteCallback' => function ($model) {
                        return $model->name === 'allow-delete';
                    },
                ],
                static::$softDeleteBehaviorConfig
            ),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'Item';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['name', 'required'],
        ];
    }
}