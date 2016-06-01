<?php

class LogicEntry
{
    public static function getDropDown($type, $name = "")
    {
//        return [$name => array_merge(CHtml::listData(ManageRegistration::model()->findAll('type='. $type . " AND `default`=". ManageRegistration::DEFAULT_ADDED ." ORDER BY updated_at DESC"), 'name', 'name', 'group'), ['other' => 'other'])];
        if(empty($name)) {
            return array_merge(CHtml::listData(ManageRegistration::model()->findAll('type='. $type . " AND `default`=". ManageRegistration::DEFAULT_ADDED . " ORDER BY updated_at DESC"), 'name', 'name', 'group'), ['other' => 'other']);
        } else {
            return [$name => array_merge(CHtml::listData(ManageRegistration::model()->findAll('type='. $type . self::getQueryTypeManage($type) . " AND `default`=". ManageRegistration::DEFAULT_ADDED . " ORDER BY updated_at DESC"), 'name', 'name', 'group'), (self::getStatusTypeManage($type))?['other' => 'other']:[])];
        }
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

    /* GROUP */
        public static function getStatusTypeManage($type)
        {
            return (!HSetting::model()->find("name='type_manage' AND value='" . ManageRegistration::$type[$type] .  "'")->value_text == 1)?false:true;
        }
    /* END GROUP */

    /* GROUP */
        public static function getQueryTypeManage($type)
        {
            return (!HSetting::model()->find("name='type_manage' AND value='" . ManageRegistration::$type[$type] .  "'")->value_text == 1)?" AND t.default=1":"";
        }
    /* END GROUP */

    /**
     * return true if user have only one mentorship and home on main page panel will hidden
     */
    public static function getStatusHomeOfUser(){
        $membership = SpaceMembership::GetUserSpaces(Yii::app()->user->id);
        if(count($membership) <= 1) {
            return true;
        }
        
        return false;
    }
}
