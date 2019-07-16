<?php

namespace yii2tech\tests\unit\ar\softdelete\data;

use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

/**
 * @property int $id
 * @property string $name
 * @property bool $isDeleted
 *
 * @property Item[] $items
 */
class Category extends ActiveRecord
{
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
                'useRestoreAttributeValuesAsDefaults' => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     * @return \yii\db\ActiveQuery|SoftDeleteQueryBehavior
     */
    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', [
            'class' => SoftDeleteQueryBehavior::className(),
        ]);
        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'Category';
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

    public function getItems()
    {
        return $this->hasMany(Item::className(), ['categoryId' => 'id']);
    }
}
