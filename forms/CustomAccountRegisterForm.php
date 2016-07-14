<?php

namespace humhub\modules\logicenter\forms;

use humhub\modules\space\models\Space;
use yii\base\Model;
use Yii;
use humhub\modules\user\models\User;

/**
 * Register Form just collects users e-mail and sends an invite
 *
 * @package humhub.modules_core.user.forms
 * @since 0.5
 * @author Luke
 */
class CustomAccountRegisterForm extends Model {

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

        $email = User::find()->andWhere(array('email' => $this->$attribute))->one();
        if ($email !== null) {
            $this->addError($attribute, Yii::t('UserModule.forms_AccountRegisterForm', 'E-Mail is already in use! - Try forgot password.'));
        }

    }

    public function uniqueSecondayEmailValidator($attribute, $params) {

        $email = User::find()->andWhere(array('secondary_email' => $this->$attribute))->one();
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