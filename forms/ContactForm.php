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
class ContactForm extends Model {

    public $email;
    public $name;
    public $content;

    /**
     * Declares the validation rules.
     */
    public function rules() {
        return array(
            array(['email','name', 'content'], 'required'),
            array('email', 'email'),
        );
    }

    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels() {
        return array(
            'email' => 'Email',
            'name' => 'Name',
            'content' => 'Message'
        );
    }

}