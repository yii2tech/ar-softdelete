<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\ar\softdelete;

use yii\base\Behavior;

/**
 * SoftDeleteQueryBehavior
 *
 * @property \yii\db\ActiveQueryInterface|\yii\db\ActiveQueryTrait $owner owner ActiveQuery instance.
 * @property array $deletedCondition filter condition for 'deleted' records.
 * @property array $notDeletedCondition filter condition for not 'deleted' records.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0.3
 */
class SoftDeleteQueryBehavior extends Behavior
{
    /**
     * @var array filter condition for 'deleted' records.
     */
    private $_deletedCondition;
    /**
     * @var array filter condition for not 'deleted' records.
     */
    private $_notDeletedCondition;

    /**
     * @return array
     */
    public function getDeletedCondition()
    {
        if ($this->_deletedCondition === null) {
            $this->_deletedCondition = $this->defaultDeletedCondition();
        }

        return $this->_deletedCondition;
    }

    /**
     * @param array $deletedCondition
     */
    public function setDeletedCondition($deletedCondition)
    {
        $this->_deletedCondition = $deletedCondition;
    }

    /**
     * @return array
     */
    public function getNotDeletedCondition()
    {
        if ($this->_notDeletedCondition === null) {
            $this->_notDeletedCondition = $this->defaultNotDeletedCondition();
        }

        return $this->_notDeletedCondition;
    }

    /**
     * @param array $notDeletedCondition
     */
    public function setNotDeletedCondition($notDeletedCondition)
    {
        $this->_notDeletedCondition = $notDeletedCondition;
    }

    /**
     * Filters query to return only 'soft-deleted' records.
     * @return \yii\db\ActiveQueryInterface|static query instance.
     */
    public function deleted()
    {
        return $this->addFilterCondition($this->getDeletedCondition());
    }

    /**
     * Filters query to return only not 'soft-deleted' records.
     * @return \yii\db\ActiveQueryInterface|static query instance.
     */
    public function notDeleted()
    {
        return $this->addFilterCondition($this->getNotDeletedCondition());
    }

    /**
     * Applies `deleted()` or `notDeleted()` scope to the query regardless to passed filter value.
     * If an empty value is passed - only not deleted records will be queried.
     * If value matching non empty int passed - only deleted records will be queried.
     * If non empty value matching int zero passed (e.g. `0`, `'0'`, `'all'`, `false`) - all records will be queried.
     * @param mixed $deleted filter value.
     * @return \yii\db\ActiveQueryInterface|static
     */
    public function filterDeleted($deleted)
    {
        if ($deleted === '' || $deleted === null || $deleted === []) {
            return $this->notDeleted();
        }

        if ((int) $deleted) {
            return $this->deleted();
        }

        return $this->owner;
    }

    /**
     * Adds given filter condition to the owner query.
     * @param array $condition filter condition.
     * @return \yii\db\ActiveQueryInterface|static owner query instance.
     */
    protected function addFilterCondition($condition)
    {
        $condition = $this->normalizeFilterCondition($condition);

        if (method_exists($this->owner, 'andOnCondition')) {
            return $this->owner->andOnCondition($condition);
        }

        return $this->owner->andWhere($condition);
    }

    /**
     * Generates default filter condition for 'deleted' records.
     * @see deletedCondition
     * @return array filter condition.
     */
    protected function defaultDeletedCondition()
    {
        $modelInstance = $this->getModelInstance();

        $condition = [];
        foreach ($modelInstance->softDeleteAttributeValues as $attribute => $value) {
            if (!is_scalar($value) && is_callable($value)) {
                $value = call_user_func($value, $modelInstance);
            }
            $condition[$attribute] = $value;
        }

        return $condition;
    }

    /**
     * Generates default filter condition for not 'deleted' records.
     * @see notDeletedCondition
     * @return array filter condition.
     */
    protected function defaultNotDeletedCondition()
    {
        $modelInstance = $this->getModelInstance();

        $condition = [];
        foreach ($modelInstance->softDeleteAttributeValues as $attribute => $value) {
            if (!is_scalar($value) && is_callable($value)) {
                $condition[$attribute] = null;
                continue;
            }
            $condition[$attribute] = !$value;
        }

        return $condition;
    }

    /**
     * Returns static instance for the model, which owner query is related to.
     * @return \yii\db\BaseActiveRecord|SoftDeleteBehavior
     */
    protected function getModelInstance()
    {
        return call_user_func([$this->owner->modelClass, 'instance']);
    }

    /**
     * Normalizes raw filter condition adding table alias for relation database query.
     * @param array $condition raw filter condition.
     * @return array normalized condition.
     */
    protected function normalizeFilterCondition($condition)
    {
        if (method_exists($this->owner, 'getTablesUsedInFrom')) {
            $fromTables = $this->owner->getTablesUsedInFrom();
            $alias = array_keys($fromTables)[0];

            foreach ($condition as $attribute => $value) {
                if (is_numeric($attribute) || strpos($attribute, '.') !== false) {
                    continue;
                }

                unset($condition[$attribute]);
                if (strpos($attribute, '[[') === false) {
                    $attribute = '[[' . $attribute . ']]';
                }
                $attribute = $alias . '.' . $attribute;
                $condition[$attribute] = $value;
            }
        }

        return $condition;
    }
}
