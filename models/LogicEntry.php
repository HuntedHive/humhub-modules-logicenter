<?php

class LogicEntry
{
    public static function getDropDown($type)
    {
        return array_merge(CHtml::listData(ManageRegistration::model()->findAll('type='. $type . " ORDER BY updated_at DESC"), 'name', 'name'), ['other' => 'other']);
    }

    public static function getDropDownDepend()
    {
        $getDependFirst = ManageRegistration::model()->find('type='. ManageRegistration::TYPE_TEACHER_TYPE . " ORDER BY updated_at DESC");
        $data = CHtml::listData(ManageRegistration::model()->findAll('depend='. $getDependFirst->id . " ORDER BY updated_at DESC"), 'name', 'name');
        if(!empty($data)) {
            return $data;
        }

        return ['other' => 'other'];
    }

    public static function getRequired($type)
    {
        return (HSetting::model()->find("name='required_manage' AND value='" . ManageRegistration::$type[$type] .  "'")->value_text == 1)?"*":"";
    }
}
