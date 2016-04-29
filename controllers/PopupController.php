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

                if(!preg_match("/^[\w\W]*[.](" . str_replace(" ","|", $domain) . ")$/", $_POST['AccountRegisterForm']['email'])) {
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
//            echo $this->render('login-updatedui', array('model' => $model, 'registerModel' => $registerModel, 'canRegister' => $canRegister, 'manageReg' => $manageReg))
            echo $this->render('login', array('model' => $model,
                'registerModel' => $registerModel,
                'canRegister' => $canRegister,
                'manageReg' => $manageReg)
            );
        }
    }

    protected function parseExpression($string)
    {
        $errors = [];
        $string = strtolower($string);
        $M_Reg = $_POST['ManageRegistration'];
        $string = preg_replace("/((&&|||) email_domain = [\'\"](.*?)[\'\"])/i", "", $string);
        preg_match_all("/(([a-z0-9_]*)[\s]{0,1}=[\s]{0,1}\"(.*?)\")/i", $string, $array, PREG_SET_ORDER);
        $return = $this->deleteZeroColumnInArray($array);

        foreach ($return as $item) {
            $expressionItem = trim($item[1]);
            $keyItem = trim($item[2]);
            $valueItem = trim($item[3]);

            if(isset($M_Reg[$keyItem]) && $keyItem != "email_domain" && $keyItem != "subject_area") {
                if(!in_array($M_Reg[$keyItem], explode(" ", $valueItem))) {
                    $errors[$keyItem] = $M_Reg[$keyItem] . " not in array " . '",["' . str_replace(' ', '","', $valueItem) . '"]';
                }
            }

            if(isset($M_Reg['subject_area']) && $keyItem == "subject_area") { // because it dependency and this array given
                foreach ($M_Reg['subject_area'] as $subjectItem) {
                    if(!in_array($subjectItem, explode(" ", $valueItem))) {
                        $errors['subject_area'][] = $subjectItem . ' not in ' . '["' . str_replace(' ', '","', $valueItem) . '"]';
                    }
                }
            }
        }

        return !empty($errors)?false:true;
    }


    public function actionSecondModal()
    {
        $this->validateRequredFields();
        $logic = strtolower(HSetting::GetText("logic_enter"));
        $logic_else = HSetting::GetText("logic_else");
        $ifRegular = $this->ifRegular(explode("then", $logic)[0]);
        $thenRegular = $this->thenRegular(explode("then", $logic)[1])[0][1];
        $if = '';
        $mailReg = '';

        $if = $this->parseExpression(explode("then", $logic)[0]);
        $domain = $this->returnEmail($ifRegular);
        if(!is_null($domain) && preg_match("/^[\w\W]*.(" . str_replace(" ","|", $mailReg) . ")$/", $_POST['email_domain'])) {
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
                if(!empty($then)) {
                    foreach ($then as $circle) {
                        $space = Space::model()->findByAttributes(['name' => trim($circle)]);
                        if (!empty($space) && empty(SpaceMembership::model()->findAllByAttributes(['user_id' => $user->id, 'space_id' => $space->id]))) {
                            $newMemberSpace = new SpaceMembership;
                            $newMemberSpace->space_id = $space->id;
                            $newMemberSpace->user_id = $user->id;
                            $newMemberSpace->status = SpaceMembership::STATUS_MEMBER;
                            $newMemberSpace->save();
                        }
                    }
                }
            } else {
                $logic_else_string = explode(",", $logic_else);
                if(!empty($logic_else_string)) {
                    foreach ($logic_else_string as $circle) {
                        $space = Space::model()->findByAttributes(['name' => trim($circle)]);
                        if (!empty($space) && empty(SpaceMembership::model()->findAllByAttributes(['user_id' => $user->id, 'space_id' => $space->id]))) {
                            $newMemberSpace = new SpaceMembership;
                            $newMemberSpace->space_id = $space->id;
                            $newMemberSpace->user_id = $user->id;
                            $newMemberSpace->status = SpaceMembership::STATUS_MEMBER;
                            $newMemberSpace->save();
                        }
                    }
                }
            }

            $model = new AccountLoginForm;
            $model->username = $user->email;
            $model->password = $_POST['email_domain'];

            if ($model->validate() && $model->login()) {
                echo json_encode(['flag' => 'redirect']);
                Yii::app()->end();
            }
        }
        echo json_encode(['flag' => 'redirect']);
    }

    public function validateRequredFields()
    {
        $required = HSetting::model()->findAll("name='required_manage'");
        $data = $_POST['ManageRegistration'];
        $errors = [];
        foreach ($required as $requiredItem) {
            if(!empty($requiredItem->value) && $requiredItem->value_text == 1 && isset($data[$requiredItem->value]) && empty($data[$requiredItem->value]))
            {
                $errors[] = $requiredItem->value . " is required";
            }

            if(!isset($data[$requiredItem->value]) && $requiredItem->value_text == 1) {
                $errors[] = $requiredItem->value . " is required";
            }
        }

        if(!empty($errors)) {
            echo json_encode(['flag' => true, 'errors' => '<div class="errorMessage">' . implode("<br>", $errors) . '</div>']);
            Yii::app()->end();
        }
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
        $options = '';
        $i = 0;
        if(isset($_POST['type']) && $_POST['type'] == ManageRegistration::TYPE_TEACHER_TYPE && $_POST['nameTeacherType'] == "other") {
            $data = ManageRegistration::model()->findAll('type=' . ManageRegistration::TYPE_TEACHER_OTHER);
            if (!empty($data)) {
                $list = $this->toUl(CHtml::listData($data, 'name', 'name'));
                $options = $this->toOptions(CHtml::listData($data, 'name', 'name'));
            } else {
//                $list .= '<li data-original-index="' . $i . '"><a tabindex="' . $i . '" class="" style="" data-tokens="null"><span class="text">other</span><span class="glyphicon glyphicon-ok check-mark"></span></a></li>';
            }
        } else {
            $idByName = ManageRegistration::model()->find('name="' . $name . '" and type=' . ManageRegistration::TYPE_TEACHER_TYPE);
                if (!empty($idByName)) {
                    $list = $this->toUl(CHtml::listData(ManageRegistration::model()->findAll('depend=' . $idByName->id), 'name', 'name'));
                    $options = $this->toOptions(CHtml::listData(ManageRegistration::model()->findAll('depend=' . $idByName->id), 'name', 'name'));
                } else {
//                    $list .= '<li data-original-index="' . $i . '"><a tabindex="' . $i . '" class="" style="" data-tokens="null"><span class="text">other</span><span class="glyphicon glyphicon-ok check-mark"></span></a></li>';
                }
        }
        echo json_encode(['li' => $list, 'option' => $options]);
    }



    public function toOptions($array)
    {
        $options = '';
        foreach ($array as $option) {
            $options.="<option value='$option'>$option</option>";
        }
        return $options;
    }

    public function toUl($array)
    {
        $ul = '';
        $i = 0;
        foreach ($array as $option) {
            $ul.='<li data-original-index="' . $i . '"><a tabindex="' . $i . '" class="" style="" data-tokens="null"><span class="text">' . $option . '</span><span class="glyphicon glyphicon-ok check-mark"></span></a></li>';
            $i++;
        }
        return $ul;
    }
}