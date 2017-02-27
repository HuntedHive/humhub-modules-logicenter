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

namespace humhub\modules\logicenter\forms;

use humhub\modules\admin\models\forms\BasicSettingsForm;
use humhub\modules\space\models\Space;
use yii\base\Model;
use Yii;
/**
 * @package humhub.modules_core.admin.forms
 * @since 0.5
 */
class BasicSettingsLogicForm extends BasicSettingsForm
{

    public $name;
    public $baseUrl;
    public $defaultLanguage;
    public $defaultSpaceGuid;
    public $tour;
    public $dashboardShowProfilePostForm;
    public $logic_enter;
    public $logic_else;

    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        return array(
            array(['name', 'baseUrl'], 'required'),
            array('name', 'string', 'max' => 150),
            array('logic_enter', 'string', 'max' => 5000),
            array('logic_else', 'string', 'max' => 255),
            array('defaultLanguage', 'in', 'range' => array_keys(Yii::$app->params['availableLanguages'])),
            array('timeZone', 'in', 'range' => \DateTimeZone::listIdentifiers()),
            array('defaultSpaceGuid', 'checkSpaceGuid'),
            array(['tour', 'dashboardShowProfilePostForm'], 'in', 'range' => array(0, 1))
        );
    }

    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels()
    {
        return array(
            'logic_enter' => 'Custom if-then logic for default user space',
            'logic_else' => 'Custom else logic for default user space',
            'name' => Yii::t('AdminModule.forms_BasicSettingsForm', 'Name of the application'),
            'baseUrl' => Yii::t('AdminModule.forms_BasicSettingsForm', 'Base URL'),
            'defaultLanguage' => Yii::t('AdminModule.forms_BasicSettingsForm', 'Default language'),
            'defaultSpaceGuid' => Yii::t('AdminModule.forms_BasicSettingsForm', 'Default space'),
            'tour' => Yii::t('AdminModule.forms_BasicSettingsForm', 'Show introduction tour for new users'),
            'dashboardShowProfilePostForm' => Yii::t('AdminModule.forms_BasicSettingsForm', 'Show user profile post form on dashboard')
        );
    }

    /**
     * This validator function checks the defaultSpaceGuid.
     *
     * @param type $attribute
     * @param type $params
     */
    public function checkSpaceGuid($attribute, $params)
    {

        if ($this->defaultSpaceGuid != "") {

            foreach (explode(',', $this->defaultSpaceGuid) as $spaceGuid) {
                if ($spaceGuid != "") {
                    $space = Space::find()->andWhere(['guid' => $spaceGuid])->one();
                    if ($space == null) {
                        $this->addError($attribute, Yii::t('AdminModule.forms_BasicSettingsForm', "Invalid space"));
                    }
                }
            }
        }
    }

}
