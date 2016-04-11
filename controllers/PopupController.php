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
            if ($model->validate() && $model->login()) {
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
                if (!empty($registerModel->hasErrors())) {
                    echo $errors;
                    Yii::app()->end();
                }
            }

            if (isset($_POST['AccountRegisterForm'])) {
                
                $registerModel->attributes = $_POST['AccountRegisterForm'];

                if ($registerModel->validate()) {

                    // Try Load an invite
                    $userInvite = UserInvite::model()->findByAttributes(array('email' => $registerModel->email));

                    if ($userInvite === null) {
                        $userInvite = new UserInvite();
                    }

                    $userInvite->email = $registerModel->email;
                    $userInvite->source = UserInvite::SOURCE_SELF;
                    $userInvite->language = Yii::app()->language;
                    $userInvite->save();

                    $userInvite->sendInviteMail();

//                    $this->render('register_success', array(
//                        'model' => $registerModel,
//                    ));
//                    return;
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
        $logic_enter = strtolower(HSetting::GetText("logic_enter"));
        $logic_else = HSetting::GetText("logic_else");
        $ifRegular = $this->ifRegular(explode("then", $logic_enter)[0]);
        $thenRegular = $this->thenRegular(explode("then", $logic_enter)[1]);
        var_dump($thenRegular);die;
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
}