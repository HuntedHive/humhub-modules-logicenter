<?php

/**
 * Connected Communities Initiative
 * Copyright (C) 2016  Queensland University of Technology
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.org/licences GNU AGPL v3
 *
 */

namespace humhub\modules\logicenter\models;

use humhub\models\Setting;
use humhub\modules\space\models\Membership;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use humhub\modules\space\permissions\CreatePublicSpace;
use humhub\modules\space\permissions\CreatePrivateSpace;
use humhub\modules\registration\models\ManageRegistration;

class LogicEntry extends \yii\base\Object
{
    public static function getDropDown($type, $name = "")
    {
//        return [$name => array_merge(CHtml::listData(ManageRegistration::model()->findAll('type='. $type . " AND `default`=". ManageRegistration::DEFAULT_ADDED ." ORDER BY updated_at DESC"), 'name', 'name', 'group'), ['other' => 'other'])];
        if(empty($name)) {
            return array_merge(ArrayHelper::map(ManageRegistration::find()->andWhere(['type' => $type, '`default`' => ManageRegistration::DEFAULT_ADDED])->orderBy(['updated_at' => SORT_DESC])->all(), 'name','name'), ['other' => 'other']);
        } else {
            $expression = new Expression('type='. $type . self::getQueryTypeManage($type) . " AND `default`=". ManageRegistration::DEFAULT_ADDED . " ORDER BY updated_at DESC");
            return [$name => array_merge(ArrayHelper::map(ManageRegistration::find()->andWhere($expression)->all(), 'name', 'name' ), (self::getStatusTypeManage($type))?['other' => 'other']:[])];
        }
    }

    public static function getDropDownDepend()
    {
        $getDependFirst = ManageRegistration::find()->andWhere(['type' => ManageRegistration::TYPE_TEACHER_TYPE])->orderBy(['updated_at' => SORT_DESC])->one();
        $data = ArrayHelper::map(ManageRegistration::find()->andWhere(['depend' => $getDependFirst->id])->orderBy(['updated_at' => SORT_DESC])->all(), 'name', 'name');
        if(!empty($data)) {
            return $data;
        }

        return ['other' => 'other'];
    }

    public static function getRequired($type)
    {
        $expression = new Expression("name='required_manage' AND value='" . ManageRegistration::$type[$type] .  "'");
        return (Setting::find()->andWhere($expression)->one()->value_text == 1)?"*":"";
    }

    /* GROUP */
        public static function getStatusTypeManage($type)
        {
            $expression = new Expression("name='type_manage' AND value='" . ManageRegistration::$type[$type] .  "'");
            return (!Setting::find()->andWhere($expression)->one()->value_text == 1)?false:true;
        }
    /* END GROUP */

    /* GROUP */
        public static function getQueryTypeManage($type)
        {
            $expression = new Expression("name='type_manage' AND value='" . ManageRegistration::$type[$type] .  "'");
            return (!Setting::find()->andWhere($expression)->one()->value_text == 1)?" AND `default`=1":"";
        }
    /* END GROUP */

    /**
     * return true if user have only one mentorship and home on main page panel will hidden
     */
    public static function getStatusHomeOfUser()
    {
        $membership = Membership::GetUserSpaces(\Yii::$app->user->id);
        if(count($membership) <= 1) {
            return true;
        }
        
        return false;
    }

    public static function canCreateSpace()
    {
        return (\Yii::$app->user->permissionmanager->can(new CreatePublicSpace) || \Yii::$app->user->permissionmanager->can(new CreatePrivateSpace()));
    }

    public static function getCurrentSpace()
    {
        $currentSpace = null;
        if (\Yii::$app->controller instanceof \humhub\modules\content\components\ContentContainerController) {
            if (\Yii::$app->controller->contentContainer !== null && \Yii::$app->controller->contentContainer instanceof \humhub\modules\space\models\Space) {
                return \Yii::$app->controller->contentContainer;
            }
        }

        return null;
    }

    public static function getMembershipQuery()
    {
        $query = Membership::find();

        if (Setting::Get('spaceOrder', 'space') == 0) {
            $query->orderBy('name ASC');
        } else {
            $query->orderBy('last_visit DESC');
        }

        $query->joinWith('space');
        $query->where(['space_membership.user_id' => \Yii::$app->user->id, 'space_membership.status' => Membership::STATUS_MEMBER]);

        return $query->all();
    }
}
