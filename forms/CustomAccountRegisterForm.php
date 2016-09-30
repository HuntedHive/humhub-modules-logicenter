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

/**
 * Register Form just collects users e-mail and sends an invite
 *
 * @package humhub.modules_core.user.forms
 * @since 0.5
 * @author Luke
 */
class CustomAccountRegisterForm extends CFormModel {

    public $email;

    /**
     * Declares the validation rules.
     */
    public function rules() {
        return array(
            array('email', 'required'),
            array('email', 'email'),
            array('email', 'uniqueEmailValidator'),
            array('email', 'uniqueSecondayEmailValidator'),
        );
    }

    public function uniqueEMailValidator($attribute, $params) {

        $email = User::model()->resetScope()->findByAttributes(array('email' => $this->$attribute));
        if ($email !== null) {
            $this->addError($attribute, Yii::t('UserModule.forms_AccountRegisterForm', 'E-Mail is already in use! - Try forgot password.'));
        }

    }

    public function uniqueSecondayEmailValidator($attribute, $params) {

        $email = User::model()->resetScope()->findByAttributes(array('secondery_email' => $this->$attribute));
        if ($email !== null) {
            $this->addError($attribute, Yii::t('UserModule.forms_AccountRegisterForm', 'E-Mail is already in use! - Try forgot password.'));
        }

    }


    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels() {
        return array(
            'email' => Yii::t('UserModule.forms_AccountRegisterForm', 'E-Mail'),
        );
    }

}
