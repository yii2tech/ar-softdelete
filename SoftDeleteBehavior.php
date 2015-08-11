<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\ar\softdelete;

use yii\base\Behavior;
use yii\base\ModelEvent;
use yii\db\BaseActiveRecord;

/**
 * SoftDeleteBehavior
 *
 * @property boolean $replaceRegularDelete
 * @property BaseActiveRecord $owner
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class SoftDeleteBehavior extends Behavior
{
    /**
     * @event ModelEvent an event that is triggered before deleting a record.
     * You may set [[ModelEvent::isValid]] to be false to stop the deletion.
     */
    const EVENT_BEFORE_SOFT_DELETE = 'beforeSoftDelete';
    /**
     * @event Event an event that is triggered after a record is deleted.
     */
    const EVENT_AFTER_SOFT_DELETE = 'afterSoftDelete';

    /**
     * @var array values of the owner attributes, which should be applied on soft delete, in format: [attributeName => attributeValue].
     * Those may raise a flag:
     *
     * ```php
     * ['isDeleted' => true]
     * ```
     *
     * or switch status:
     *
     * ```php
     * ['statusId' => Item::STATUS_DELETED]
     * ```
     */
    public $softDeleteAttributeValues = [
        'isDeleted' => true
    ];
    /**
     * @var boolean whether to invoke owner [[BaseActiveRecord::beforeDelete()]] and [[BaseActiveRecord::afterDelete()]]
     * while performing soft delete. This option affects only [[softDelete()]] method.
     */
    public $invokeDeleteEvents = true;

    /**
     * @var boolean whether to perform soft delete instead of regular delete.
     * If enabled [[BaseActiveRecord::delete()]] will perform soft deletion instead of actual record deleting.
     */
    private $_replaceRegularDelete = false;


    /**
     * @return boolean
     */
    public function getReplaceRegularDelete()
    {
        return $this->_replaceRegularDelete;
    }

    /**
     * @param boolean $replaceRegularDelete
     */
    public function setReplaceRegularDelete($replaceRegularDelete)
    {
        $this->_replaceRegularDelete = $replaceRegularDelete;
        if (is_object($this->owner)) {
            $owner = $this->owner;
            $this->detach();
            $this->attach($owner);
        }
    }

    /**
     * Marks the owner as deleted.
     * @return integer|false the number of rows marked as deleted, or false if the soft deletion is unsuccessful for some reason.
     * Note that it is possible the number of rows deleted is 0, even though the soft deletion execution is successful.
     */
    public function softDelete()
    {
        if ($this->invokeDeleteEvents && !$this->owner->beforeDelete()) {
            return false;
        }

        $result = $this->softDeleteInternal();

        if ($this->invokeDeleteEvents) {
            $this->owner->afterDelete();
        }
        return $result;
    }

    /**
     * Marks the owner as deleted.
     * @return integer|false the number of rows marked as deleted, or false if the soft deletion is unsuccessful for some reason.
     */
    protected function softDeleteInternal()
    {
        $result = false;
        if ($this->beforeSoftDelete()) {
            $result = $this->owner->updateAttributes($this->softDeleteAttributeValues);
            $this->afterSoftDelete();
        }
        return $result;
    }

    /**
     * This method is invoked before soft deleting a record.
     * The default implementation raises the [[EVENT_BEFORE_SOFT_DELETE]] event.
     * When overriding this method, make sure you call the parent implementation like the following:
     *
     * ```php
     * public function beforeSoftDelete()
     * {
     *     if (parent::beforeSoftDelete()) {
     *         // ...custom code here...
     *         return true;
     *     } else {
     *         return false;
     *     }
     * }
     * ```
     *
     * @return boolean whether the record should be deleted. Defaults to true.
     */
    public function beforeSoftDelete()
    {
        if (method_exists($this->owner, 'beforeSoftDelete')) {
            if (!$this->owner->beforeSoftDelete()) {
                return false;
            }
        }

        $event = new ModelEvent();
        $this->owner->trigger(self::EVENT_BEFORE_SOFT_DELETE, $event);

        return $event->isValid;
    }

    /**
     * This method is invoked after soft deleting a record.
     * The default implementation raises the [[EVENT_AFTER_DELETE]] event.
     * You may override this method to do postprocessing after the record is deleted.
     * Make sure you call the parent implementation so that the event is raised properly.
     */
    public function afterSoftDelete()
    {
        if (method_exists($this->owner, 'afterSoftDelete')) {
            $this->owner->afterSoftDelete();
        }
        $this->owner->trigger(self::EVENT_AFTER_SOFT_DELETE);
    }

    // Events :

    /**
     * @inheritdoc
     */
    public function events()
    {
        if ($this->getReplaceRegularDelete()) {
            return [
                BaseActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
            ];
        } else {
            return [];
        }
    }

    /**
     * Handles owner 'beforeDelete' event, applying soft delete and preventing actual deleting.
     * @param ModelEvent $event event instance.
     */
    public function beforeDelete($event)
    {
        $this->softDeleteInternal();
        $event->isValid = false;
    }
}