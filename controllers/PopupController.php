<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class PopupController extends CController
{
    
    public $layout = "application.modules_core.user.views.layouts.main_auth";
    public $subLayout = "application.modules_core.user.views.auth._layout";
    
    public function actionLogin()
    {
        // If user is already logged in, redirect him to the dashboard
        if (!Yii::app()->user->isGuest) {
            $this->redirect(Yii::app()->user->returnUrl);
        }

        // Show/Allow Anonymous Registration
        $canRegister = HSetting::Get('anonymousRegistration', 'authentication_internal');
        $model = new AccountLoginForm;

        //TODO: Solve this via events!
        if (Yii::app()->getModule('zsso') != null) {
            ZSsoModule::beforeActionLogin();
        }

        // if it is ajax validation request
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'account-login-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }

        // collect user input data
        if (isset($_POST['AccountLoginForm'])) {
            $model->attributes = $_POST['AccountLoginForm'];
            var_dump($_POST);
            // validate user input and redirect to the previous page if valid
            if ($model->validate() && ($model->login() || $model->secondLogin())) {
                $user = User::model()->findByPk(Yii::app()->user->id);
                if (Yii::app()->request->isAjaxRequest) {
                    $this->htmlRedirect(Yii::app()->user->returnUrl);
                } else {
                    $this->redirect(Yii::app()->user->returnUrl);
                }
            }
        }
        // Always clear password
        $model->password = "";

        $registerModel = new AccountRegisterForm;

        // Registration enabled?
        if ($canRegister) {
            
            // if it is ajax validation request
            if (isset($_POST['ajax'])) {
                $errors = CActiveForm::validate($registerModel);

                $logic = strtolower(HSetting::GetText("logic_enter"));
                $ifRegular = $this->ifRegular(explode("then", $logic)[0]);
                $domain = $this->returnEmail($ifRegular);
                if(is_null($domain)) {
                    $registerModel->addError("AccountRegisterForm_email", "Error in parse not found email_domain");
                }

                if(!preg_match("/^[\w\W]*.(" . str_replace(" ","|", $domain) . ")$/", $_POST['AccountRegisterForm']['email'])) {
                    $registerModel->addError("AccountRegisterForm_email", "Only: " . $domain);
                }

                if (!empty($registerModel->hasErrors())) {
                    echo json_encode($registerModel->getErrors());
                    Yii::app()->end();
                }
            }
        }

        $manageReg = new ManageRegistration;
        if (Yii::app()->request->isAjaxRequest) {
        } else {
            echo $this->render('login', array('model' => $model, 'registerModel' => $registerModel, 'canRegister' => $canRegister, 'manageReg' => $manageReg));
        }
    }
    
    public function actionSecondModal()
    {
        $logic = strtolower(HSetting::GetText("logic_enter"));
        $logic_else = HSetting::GetText("logic_else");
        $ifRegular = $this->ifRegular(explode("then", $logic)[0]);
        $thenRegular = $this->thenRegular(explode("then", $logic)[1])[0][1];
        $if = '';
        $mailReg = '';
        foreach ($ifRegular as $reg) {
            if(isset($reg[1]) && isset($reg[2]) && isset($reg[3]) && isset($_POST['ManageRegistration'][trim($reg[2])])) {
                if (trim($reg[2]) != "email_domain") {
                    $if .= $this->_o($reg[1]) . ' ' . 'in_array("' . $_POST['ManageRegistration'][trim($reg[2])] . '",["' . str_replace(' ', '","', trim($reg[3])) . '"]) ';
                }
            } else {
                $mailReg = $reg[2];
            }
        }

        $domain = $this->returnEmail($ifRegular);
        if(preg_match("/^[\w\W]*.(" . str_replace(" ","|", $mailReg) . ")$/", $_POST['email_domain']) && !is_null($domain)) {
            $user = new User;
            $user->username = $_POST['email_domain'];
            $user->email = $_POST['email_domain'];
            $user->save();

            $userPassword = new UserPassword;
            $userPassword->user_id = $user->id;
            $userPassword->setPassword($_POST['email_domain']);
            $userPassword->save();

            if ($if) {
                $then = explode(",", $thenRegular);

                foreach ($then as $circle) {
                    $space = Space::model()->findByAttributes(['name' => $circle]);
                    if (empty(SpaceMembership::model()->findAllByAttributes(['user_id' => $user->id, 'space_id' => $space->id]))) {
                        $newMemberSpace = new SpaceMembership;
                        $newMemberSpace->space_id = $space->id;
                        $newMemberSpace->user_id = $user->id;
                        $newMemberSpace->status = SpaceMembership::STATUS_MEMBER;
                        $newMemberSpace->save();
                    }
                }
            } else {
                $space = Space::model()->findByAttributes(['name' => $logic_else]);
                if (empty(SpaceMembership::model()->findAllByAttributes(['user_id' => $user->id, 'space_id' => $space->id]))) {
                    $newMemberSpace = new SpaceMembership;
                    $newMemberSpace->space_id = $space->id;
                    $newMemberSpace->user_id = $user->id;
                    $newMemberSpace->status = SpaceMembership::STATUS_MEMBER;
                    $newMemberSpace->save();
                }
            }

            $model = new AccountLoginForm;
            $model->username = $user->email;
            $model->password = $_POST['email_domain'];

            if ($model->validate() && $model->login()) {
                $user = User::model()->findByPk(Yii::app()->user->id);

                if (Yii::app()->request->isAjaxRequest) {
                    $this->htmlRedirect(Yii::app()->user->returnUrl);
                } else {
                    $this->redirect(Yii::app()->user->returnUrl);
                }
            }
        }
        return $this->redirect(['/']);
    }

    protected function ifRegular($string)
    {
        $array= [];
        preg_match_all("/(and|IF|or)?(.*?)[\s]?=[\s]?['|\"](.*?)['|\"][\s]/i", $string, $array, PREG_SET_ORDER);
        return $this->deleteZeroColumnInArray($array);
    }
    
    protected function thenRegular($string)
    {
        $array= [];
        preg_match_all("/[\"|'](.*?)[\"|']/i", $string, $array, PREG_SET_ORDER);
        return $this->deleteZeroColumnInArray($array);
    }

    protected function _o($operator)
    {
        $operator = strtolower($operator);
        switch($operator) {
            case 'and':
                return '&&';
            case 'or':
                return '||';
            case 'if':
                return '';
        }
    }

    protected function deleteZeroColumnInArray($array)
    {
        $newArray = [];
        foreach ($array as $key => $value) {
            unset($value[0]);
            $newArray[] = $value;
        }
        
        return $newArray;
    }

    protected function returnEmail($array)
    {
        foreach ($array as $item) {
            if(trim($item[2]) == "email_domain")
            {
                return $item[3];
            }
        }

        return null;
    }
    
    public function actionGetDependTeacherType()
    {
        $name = trim($_POST['nameTeacherType']);
        $idByName = null;
        $list = '';

        if(isset($_POST['type']) && $_POST['type'] == ManageRegistration::TYPE_TEACHER_TYPE && $_POST['nameTeacherType'] == "other") {
            $data = ManageRegistration::model()->findAll('type=' . ManageRegistration::TYPE_TEACHER_OTHER);
            if (!empty($data)) {
                $list = $this->toOptions(CHtml::listData($data, 'name', 'name'));
            } else {
                $list .= "<option value='other'>other</option>";
            }
        } else {

            $idByName = ManageRegistration::model()->find('name="' . $name . '"');
            if (!empty($idByName)) {
                $list = $this->toOptions(CHtml::listData(ManageRegistration::model()->findAll('depend=' . $idByName->id), 'name', 'name'));
            } else {
                $list .= "<option value='other'>other</option>";
            }
        }

        echo $list;
    }

    public function toOptions($array)
    {
        $options = '';

        foreach ($array as $option) {
            $options.="<option value='$option'>$option</option>";
        }

        $options .= "<option value='other'>other</option>";
        return $options;
    }
}