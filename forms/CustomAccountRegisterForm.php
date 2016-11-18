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

use humhub\modules\space\models\Space;
use yii\base\Model;
use Yii;
use humhub\modules\user\models\User;
use humhub\modules\user\models\forms\AccountRegister;
/**
 * Register Form just collects users e-mail and sends an invite
 *
 * @package humhub.modules_core.user.forms
 * @since 0.5
 * @author Luke
 */
class CustomAccountRegisterForm extends AccountRegister {

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
        $email = User::find()->andWhere(array('email' => $this->email))->one();
        if ($email !== null) {
            $this->addError($attribute, Yii::t('UserModule.forms_AccountRegisterForm', 'E-Mail is already in use! - Try forgot password.'));
        }

    }

    public function uniqueSecondayEmailValidator($attribute, $params) {

        $email = User::find()->andWhere(array('secondary_email' => $this->email))->one();
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
    
    public function sendVerifyEmail()
    {
        $invite = \humhub\modules\user\models\Invite::findOne(['email' => $this->email]);
        if ($invite === null) {
            $invite = new \humhub\modules\user\models\Invite();
        }
        $invite->email = $this->email;
        $invite->source = \humhub\modules\user\models\Invite::SOURCE_SELF;
        $invite->language = Yii::$app->language;
        $invite->save();
        $invite->sendInviteMail();
        return true;
    }

}
