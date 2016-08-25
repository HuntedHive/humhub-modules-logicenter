<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace humhub\modules\logicenter\controllers;

use humhub\modules\logicenter\forms\BasicSettingsLogicForm;
use humhub\modules\logicenter\forms\ContactForm;
use humhub\modules\logicenter\forms\CustomAccountRegisterForm;
use humhub\modules\logicenter\models\LogicEntry;
use humhub\modules\registration\models\ManageRegistration;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\Password;
use humhub\modules\user\models\Profile;
use humhub\modules\user\models\User;
use humhub\modules\user\models\forms\AccountLogin;
use yii\bootstrap\ActiveForm;
use humhub\models\Setting;
use humhub\components\Controller;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

class PopupController extends Controller
{

    public $subLayout = "@humhub/modules/user/views/layouts/";
    public $layout = "@humhub/modules/user/views/layouts/main";

    public function actionLogin()
    {
        // If user is already logged in, redirect him to the dashboard
        if (!Yii::$app->user->isGuest) {
            $this->redirect(Yii::$app->user->returnUrl);
        }

        // Show/Allow Anonymous Registration
        $canRegister = Setting::Get('anonymousRegistration', 'authentication_internal');
        $model = new AccountLogin();

        // if it is ajax validation request
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'account-login-form') {
            echo ActiveForm::validate($model);
            Yii::$app->end();
        }

        // collect user input data
        if (isset($_POST['AccountLogin'])) {
            $model->attributes = $_POST['AccountLogin'];
            // validate user input and redirect to the previous page if valid
            if ($model->validate() && ($model->login())) {
                $user = \humhub\modules\user\models\User::findOne(Yii::$app->user->id);
                if (Yii::$app->request->isAjax) {
                    return $this->htmlRedirect(Yii::$app->user->returnUrl);
                } else {
                    return $this->redirect(["/"]);
                }
            }
        }
        // Always clear password
        $model->password = "";

        $registerModel = new CustomAccountRegisterForm();

        // Registration enabled?
        if ($canRegister && isset($_POST['CustomAccountRegisterForm'])) {
            // if it is ajax validation request
            if (Yii::$app->request->isAjax) {
                $registerModel->load(Yii::$app->request->post());
                $registerModel->validate();

                $logic = strtolower(Setting::GetText("logic_enter"));
                $ifRegular = $this->ifRegular(explode("then", $logic)[0]);
                $domain = $this->returnEmail($ifRegular);

                if(!is_null($domain)) {

                    if(!preg_match("/^[\w\W]*[.](" . str_replace(" ","|", $domain) . ")$/", $_POST['CustomAccountRegisterForm']['email'])) {
                        $registerModel->addError("AccountRegisterForm_email", "email only: " . $domain);
                    }

                    if($registerModel->hasErrors()) {
                        echo json_encode(
                            [
                                'flag' => "error",
                                'errors' => $this->implodeAssocArray($registerModel->getErrors()),
                            ]
                        );
                        Yii::$app->end();
                    }

                    echo json_encode(
                        [
                            'flag' => "next"
                        ]
                    );
                } else {
                    if($registerModel->hasErrors()) {
                        echo json_encode(
                            [
                                'flag' => "error",
                                'errors' => $this->implodeAssocArray($registerModel->getErrors()),
                            ]
                        );
                        Yii::$app->end();
                    }

                    $usEmail = $_POST['CustomAccountRegisterForm']['email'];
                    $user = new User();
                    $user->scenario = 'registration';

                    $groupModels = \humhub\modules\user\models\Group::find()->orderBy('name ASC')->all();
                    $defaultUserGroup = \humhub\models\Setting::Get('defaultUserGroup', 'authentication_internal');
                    $groupFieldType = "dropdownlist";
                    if ($defaultUserGroup != "") {
                        $groupFieldType = "hidden";
                    } else if (count($groupModels) == 1) {
                        $groupFieldType = "hidden";
                        $defaultUserGroup = $groupModels[0]->id;
                    }

                    if ($groupFieldType == 'hidden') {
                        $user->group_id = $defaultUserGroup;
                    }

                    // Generate a random first name
                    $firstNameOptions = explode("\n", Setting::GetText('anonAccountsFirstNameOptions'));
                    $randomFirstName = trim(ucfirst($firstNameOptions[array_rand($firstNameOptions)]));

                    // Generate a random last name
                    $lastNameOptions = explode("\n", Setting::GetText('anonAccountsLastNameOptions'));
                    $randomLastName = trim(ucfirst($lastNameOptions[array_rand($lastNameOptions)]));

                    $user->username = substr(str_replace(" ", "_", strtolower($randomFirstName . "_" . $randomLastName)), 0, 25);
                    $user->email = $usEmail;
                    $user->status = User::STATUS_ENABLED;
                    $user->save(false);

                    $userPasswordModel = new Password();
                    $userPasswordModel->setPassword($user->email);
                    $userPasswordModel->user_id = $user->getPrimaryKey();
                    $userPasswordModel->save();

                    $profileModel = $user->profile;
                    $profileModel->scenario = 'registration';
                    $profileModel->user_id = $user->getPrimaryKey();
                    $profileModel->firstname = $randomFirstName;
                    $profileModel->lastname = $randomLastName;
                    $profileModel->save();

                    $model = new AccountLogin();
                    $model->username = $user->username;
                    $model->password = $usEmail;

                    if ($model->validate() && $model->login()) {
                        echo json_encode(
                            [
                                'flag' => 'redirect',
                                'location' => Url::toRoute("/"),
                            ]
                        );
                        Yii::$app->end();
                    }
                }
            }
        }


        $contact = new ContactForm();
        if(Yii::$app->request->isPost && isset($_POST['ContactForm'])) {
            $contact->load(Yii::$app->request->post());
            if ($contact->validate()) {
                Yii::$app->getSession()->setFlash("success_", "Your message send successful");
                Yii::$app->mailer->compose()
                    ->setFrom(\humhub\models\Setting::Get('systemEmailAddress', 'mailing'))
                    ->setTo($contact->email)
                    ->setSubject("ContactUs:" . $contact->name)
                    ->setTextBody($contact->content)
                    ->send();
                return true;
            }

            return false;
        }

        $manageReg = new ManageRegistration();
        if (Yii::$app->request->isAjax) {
        } else {
            echo $this->render('login', array('model' => $model,
                'registerModel' => $registerModel,
                'canRegister' => $canRegister,
                'manageReg' => $manageReg,
                'contact' => $contact,
                )
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

            if(isset($M_Reg['subject_area']) && !empty($M_Reg['subject_area']) && $keyItem == "subject_area") { // because it dependency and this array given
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
        $logic = strtolower(Setting::GetText("logic_enter"));
        $logic_else = Setting::GetText("logic_else");
        $ifRegular = $this->ifRegular(explode("then", $logic)[0]);
        $thenRegular = $this->thenRegular(explode("then", $logic)[1])[0][1];
        $if = '';
        $mailReg = '';

        $if = $this->parseExpression(explode("then", $logic)[0]);
        $domain = $this->returnEmail($ifRegular);
        if(!is_null($domain) && preg_match("/^[\w\W]*.(" . str_replace(" ","|", $mailReg) . ")$/", $_POST['email_domain'])) {
            $user = new User();
            $user->scenario = 'registration';
            $user->status = User::STATUS_ENABLED;

            $groupModels = \humhub\modules\user\models\Group::find()->orderBy('name ASC')->all();
            $defaultUserGroup = \humhub\models\Setting::Get('defaultUserGroup', 'authentication_internal');
            $groupFieldType = "dropdownlist";
            if ($defaultUserGroup != "") {
                $groupFieldType = "hidden";
            } else if (count($groupModels) == 1) {
                $groupFieldType = "hidden";
                $defaultUserGroup = $groupModels[0]->id;
            }

            if ($groupFieldType == 'hidden') {
                $user->group_id = $defaultUserGroup;
            }


            // Generate a random first name
            $firstNameOptions = explode("\n", Setting::GetText('anonAccountsFirstNameOptions'));
            $randomFirstName = trim(ucfirst($firstNameOptions[array_rand($firstNameOptions)]));

            // Generate a random last name
            $lastNameOptions = explode("\n", Setting::GetText('anonAccountsLastNameOptions'));
            $randomLastName = trim(ucfirst($lastNameOptions[array_rand($lastNameOptions)]));

            $user->username = substr(str_replace(" ", "_", strtolower($randomFirstName . "_" . $randomLastName)), 0, 25);
            $user->email = $_POST['email_domain'];
            $user->save(false);

            $userPasswordModel = new Password();
            $userPasswordModel->setPassword($user->email);
            $userPasswordModel->user_id = $user->id;
            $userPasswordModel->save();

            $profileModel = $user->profile;
            $profileModel->scenario = 'registration';
            $profileModel->teacher_type = $_POST['ManageRegistration']['teacher_type'];
            $profileModel->user_id = $user->id;
            $profileModel->firstname = $randomFirstName;
            $profileModel->lastname = $randomLastName;
            $profileModel->save();

            if ($if) {
                $then = explode(",", $thenRegular);
                if(!empty($then)) {
                    foreach ($then as $circle) {
                        $space = Space::find()->andWhere(['name' => trim($circle)])->one();
                        if (!empty($space) && empty(Membership::find()->andWhere(['user_id' => $user->id, 'space_id' => $space->id])->one())) {
                            $newMemberSpace = new Membership;
                            $newMemberSpace->space_id = $space->id;
                            $newMemberSpace->user_id = $user->id;
                            $newMemberSpace->status = Membership::STATUS_MEMBER;
                            $newMemberSpace->save();
                        }
                    }
                }
            } else {
                $logic_else_string = explode(",", $logic_else);
                if(!empty($logic_else_string)) {
                    foreach ($logic_else_string as $circle) {
                        $space = Space::find()->andWhere(['name' => trim($circle)])->one();
                        if (!empty($space) && empty(Membership::find()->andWhere(['user_id' => $user->id, 'space_id' => $space->id])->one())) {
                            $newMemberSpace = new Membership;
                            $newMemberSpace->space_id = $space->id;
                            $newMemberSpace->user_id = $user->id;
                            $newMemberSpace->status = Membership::STATUS_MEMBER;
                            $newMemberSpace->save();
                        }
                    }
                }
            }

            $model = new AccountLogin();
            $model->username = $user->email;
            $model->password = $_POST['email_domain'];

            $this->addOthertoList();

            if ($model->validate() && $model->login()) {
                echo json_encode(
                    [
                        'flag' => 'redirect'
                    ]
                );
                Yii::$app->end();
            }
        }

        echo json_encode(
            [
                'flag' => 'redirect',
            ]
        );
        Yii::$app->end();
    }

    protected function implodeAssocArray($array)
    {
        $string = "<div class='errorsSignup'>";
        if(is_array($array) && !empty(array_filter($array))) {
            foreach ($array as $key => $value) {
                foreach ($value as $item) {
                    $string.=  $item . "<br />";
                }
            }
        }
        $string.="</div>";

        return $string;
    }

    protected function addOthertoList()
    {
        $data = $_POST['ManageRegistration'];
        $typeRever = array_flip(ManageRegistration::$type);
        $dependTeacherTypeId = "";
        $existTeacherTypeId = '';
        if(!empty($data) && is_array($data)) {
            foreach ($data as $key => $value) {
                if (isset($typeRever[$key]) && !empty($value) && $key != "subject_area") {
                    $manageItem = ManageRegistration::find()->andWhere(['name' => trim($value)])->one();
                    if (empty($manageItem)) {
                        $manage = new ManageRegistration;
                        $manage->name = trim($value);
                        $manage->type = $typeRever[$key];
                        $manage->default = ManageRegistration::DEFAULT_DEFAULT;
                        $manage->save();
                    }
                }

                if($key == "teacher_type") {
                    $existTeacherTypeId = ManageRegistration::find()->andWhere(['name' => trim($value)])->one();
                    if(!empty($existTeacherTypeId)) {
                        $dependTeacherTypeId = $existTeacherTypeId->id;
                    }
                }

                if(isset($typeRever[$key]) && !empty($value) && $key == "subject_area" && !empty($dependTeacherTypeId)) {
                    foreach ($value as $itemSubject) {
                        if (empty($itemSubject) && strtolower($itemSubject) != "other") {
                            $manage2 = new ManageRegistration;
                            $manage2->name = trim($itemSubject);
                            $manage2->type = ManageRegistration::TYPE_SUBJECT_AREA;
                            $manage2->default = ManageRegistration::DEFAULT_DEFAULT;
                            $manage2->depend = $dependTeacherTypeId;
                            $manage2->save(false);
                        }
                    }
                }
            }
        }
    }

    public function validateRequredFields()
    {
        $required = Setting::find()->andWhere(['name' => 'required_manage'])->all();
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
            Yii::$app->end();
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
        if(isset($_POST['type']) && $_POST['type'] == ManageRegistration::TYPE_TEACHER_TYPE && strtolower($_POST['nameTeacherType']) == "other") {
            $sql = 'SELECT t1.name FROM `manage_registration` t1 LEFT JOIN manage_registration t2 ON t1.depend = t2.id WHERE t1.type = 2 AND t2.name = "other"';
            $data = Yii::$app->db->createCommand($sql)->queryAll();
            $data = ArrayHelper::getColumn($data, ["name" => "name"]);
            if (!empty($data)) {
                $list = $this->toUl($data);
                $options = $this->toOptions($data);
            } else {
                $list .= '<li data-original-index="' . $i . '"><a tabindex="' . $i . '" class="" style="" data-tokens="null"><span class="text">other</span><span class="glyphicon glyphicon-ok check-mark"></span></a></li>';
            }
        } else {
            $idByName = ManageRegistration::find()->andWhere(['name' => $name, 'type' => ManageRegistration::TYPE_TEACHER_TYPE])->one();
                if (!empty($idByName)) {
                    $data = ArrayHelper::getColumn(ManageRegistration::find()->andWhere('name!="'.ManageRegistration::VAR_OTHER.'" AND depend=' . $idByName->id)->all(), ['name' => 'name']);
                    $list = $this->toUl($data);
                    $options = $this->toOptions($data);
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

        if(LogicEntry::getStatusTypeManage(ManageRegistration::TYPE_SUBJECT_AREA)) {
            $options.="<option value='other'>other</option>";
        }
        $options.="<option value='other'>other</option>";

        return $options;
    }

    public function toUl($array)
    {
        $ul = '<li class="dropdown-header " data-optgroup="1"><span class="text">Select subject area(s)</span></li>';
        $i = 0;
        foreach ($array as $option) {
            $ul.='<li data-original-index="' . $i . '"><a tabindex="' . $i . '" class="" style="" data-tokens="null"><span class="text">' . $option . '</span><span class="glyphicon glyphicon-ok check-mark"></span></a></li>';
            $i++;
        }

        if(LogicEntry::getStatusTypeManage(ManageRegistration::TYPE_SUBJECT_AREA)) {
            $ul .= '<li data-original-index="' . ++$i . '"><a tabindex="' . ++$i . '" class="" style="" data-tokens="null"><span class="text">other</span><span class="glyphicon glyphicon-ok check-mark"></span></a></li>';
        }
        return $ul;
    }
}