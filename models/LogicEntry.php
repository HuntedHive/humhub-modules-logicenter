<?php

class LogicEntry
{

    public static function getDropDown($type = 0)
    {
        return array_merge(CHtml::listData(ManageRegistration::model()->findAll('type='. $type . " ORDER BY updated_at DESC"), 'id', 'name'), ['other' => 'other']);
    }
}
