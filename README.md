ActiveRecord Soft Delete Extension for Yii2
===========================================

This extension provides support for ActiveRecord soft delete.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://poser.pugx.org/yii2tech/ar-softdelete/v/stable.png)](https://packagist.org/packages/yii2tech/ar-softdelete)
[![Total Downloads](https://poser.pugx.org/yii2tech/ar-softdelete/downloads.png)](https://packagist.org/packages/yii2tech/ar-softdelete)
[![Build Status](https://travis-ci.org/yii2tech/ar-softdelete.svg?branch=master)](https://travis-ci.org/yii2tech/ar-softdelete)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yii2tech/ar-softdelete
```

or add

```json
"yii2tech/ar-softdelete": "*"
```

to the require section of your composer.json.


Usage
-----

This extension provides support for so called "soft" deletion of the ActiveRecord, which means record is not deleted
from database, but marked with some flag or status, which indicates it is no longer active, instead.

This extension provides [[\yii2tech\ar\softdelete\SoftDeleteBehavior]] ActiveRecord behavior for such solution
support in Yii2. You may attach it to your model class in the following way:

```php
class Item extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'isDeleted' => true
                ],
            ],
        ];
    }
}
```

There are 2 ways of "soft" delete applying:
 - using `softDelete()` separated method
 - mutating regular `delete()` method

Usage of `softDelete()` is recommended, since it allows marking the record as "deleted", while leaving regular `delete()`
method intact, which allows you to perform "hard" delete if necessary. For example:

```php
$id = 17;
$item = Item::findOne($id);
$item->softDelete(); // mark record as "deleted"

$item = Item::findOne($id);
var_dump($item->isDeleted); // outputs "true"

$item->delete(); // perform actual deleting of the record
$item = Item::findOne($id);
var_dump($item); // outputs "null"
```

However you may want to mutate regular ActiveRecord `delete()` method in the way in performs "soft" deleting instead
of actual removing of the record. It is a common solution in such cases as applying "soft" delete functionality for
existing code. For such functionality you should enable [[\yii2tech\ar\softdelete\SoftDeleteBehavior::replaceRegularDelete]]
option in behavior configuration:

```php
class Item extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'isDeleted' => true
                ],
                'replaceRegularDelete' => true // mutate native `delete()` method
            ],
        ];
    }
}
```

Now invocation of the `delete()` method will mark record as "deleted" instead of removing it:

```php
$id = 17;
$item = Item::findOne($id);
$item->delete(); // no record removal, mark record as "deleted" instead

$item = Item::findOne($id);
var_dump($item->isDeleted); // outputs "true"
```

> Tip: you may apply a condition, which filters "not deleted" records, to the ActiveQuery as default scope, overriding
  `find()` method. Also remember, you may reset such default scope using `where()` method with empty condition.

```php
class Item extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'isDeleted' => true
                ],
            ],
        ];
    }

    public static function find()
    {
        return parent::find()->where(['isDeleted' => false]);
    }
}

$notDeletedItems = Item::find()->all(); // returns only not "deleted" records
$allItems = Item::find()->where([])->all(); // returns all records
```


## Smart deletion <span id="smart-deletion"></span>

Usually "soft" deleting feature is used to prevent the database history loss ensuring data, which been in use and
perhaps have a references or dependencies, is kept in the system. However sometimes actual deleting is allowed for
such data as well.
For example: usually user account records should not be deleted but only marked as "inactive", however if you browse
through users list and found accounts, which has been registered long ago, but don't have at least single log-in in the
system, these records have no value for the history and can be removed from database to save disk space.

You can make "soft" deletion to be "smart" and detect, if the record can be removed from database or only marked as "deleted".
This can be done via [[\yii2tech\ar\softdelete\SoftDeleteBehavior::allowDeleteCallback]]. For example:

```php
class User extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'isDeleted' => true
                ],
                'allowDeleteCallback' => function ($user) {
                    return $user->lastLoginDate === null; // allow delete user, if he has never logged in
                }
            ],
        ];
    }
}

$user = User::find()->where(['lastLoginDate' => null])->limit(1)->one();
$user->softDelete(); // removes the record!!!

$user = User::find()->where(['not' =>['lastLoginDate' => null]])->limit(1)->one();
$user->softDelete(); // marks record as "deleted"
```

[[\yii2tech\ar\softdelete\SoftDeleteBehavior::allowDeleteCallback]] logic is applied in case [[\yii2tech\ar\softdelete\SoftDeleteBehavior::replaceRegularDelete]]
is enabled as well.


## Record restoration <span id="record-restoration"></span>

At some point you may want to "restore" records, which have been marked as "deleted" in the past.
You may use `restore()` method for this:

```php
$id = 17;
$item = Item::findOne($id);
$item->softDelete(); // mark record as "deleted"

$item = Item::findOne($id);
$item->restore(); // restore record
var_dump($item->isDeleted); // outputs "false"
```

By default attribute values, which should be applied for record restoration are automatically detected from [[\yii2tech\ar\softdelete\SoftDeleteBehavior::softDeleteAttributeValues]],
however it is better you specify them explicitly via [[\yii2tech\ar\softdelete\SoftDeleteBehavior::restoreAttributeValues]].


## Events <span id="events"></span>

By default [[\yii2tech\ar\softdelete\SoftDeleteBehavior::softDelete()]] triggers [[\yii\db\BaseActiveRecord::EVENT_BEFORE_DELETE]]
and [[\yii\db\BaseActiveRecord::EVENT_AFTER_DELETE]] events in the same way they are triggered at regular `delete()`.

Also [[\yii2tech\ar\softdelete\SoftDeleteBehavior]] triggers several additional events in the scope of the owner ActiveRecord:
 - [[\yii2tech\ar\softdelete\SoftDeleteBehavior::EVENT_BEFORE_SOFT_DELETE]] - triggered before "soft" delete is made.
 - [[\yii2tech\ar\softdelete\SoftDeleteBehavior::EVENT_AFTER_SOFT_DELETE]] - triggered after "soft" delete is made.
 - [[\yii2tech\ar\softdelete\SoftDeleteBehavior::EVENT_BEFORE_RESTORE]] - triggered before record is restored from "deleted" state.
 - [[\yii2tech\ar\softdelete\SoftDeleteBehavior::EVENT_AFTER_RESTORE]] - triggered after record is restored from "deleted" state.

You may attach the event handlers for these events to your ActiveRecord object:

```php
$item = Item::findOne($id);
$item->on(SoftDeleteBehavior::EVENT_BEFORE_SOFT_DELETE, function($event) {
    $event->isValid = false; // prevent "soft" delete to be performed
});
```

You may also handle these events inside your ActiveRecord class by declaring the corresponding methods:

```php
class Item extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                // ...
            ],
        ];
    }

    public function beforeSoftDelete()
    {
        $this->deletedAt = time(); // log the deletion date
        return true;
    }

    public function beforeRestore()
    {
        return $this->deletedAt > (time() - 3600); // allow restoration only for the records, being deleted during last hour
    }
}
```
