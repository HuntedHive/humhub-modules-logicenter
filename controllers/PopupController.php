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

namespace humhub\modules\logicenter\controllers;

use humhub\modules\logicenter\forms\BaseAccountLogin;
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
use yii\bootstrap\ActiveForm;
use humhub\models\Setting;
use humhub\components\Controller;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use humhub\modules\user\models\Invite;
use yii\web\HttpException;

class PopupController extends Controller
{

    public $subLayout = "@humhub/modules/user/views/layouts/";
    public $layout = "@humhub/modules/user/views/layouts/main";

    /**
     * After the user validated his email
     *
     */
    public function actionEmailValidate()
    {
        $token = Yii::$app->request->get('token');
        $userInvite = Invite::findOne(['token' => $token]);

        // Check if Token is valid
        if (empty($userInvite)) {
            throw new HttpException(404, Yii::t('UserModule.controllers_AccountController', 'Invalid link! Please make sure that you entered the entire url.'));
        }

        // Check if E-Mail is in use, e.g. by other user
        $user = \humhub\modules\user\models\User::findOne(['email' => $userInvite->email]);
        if ($user == null) {
            throw new HttpException(404, Yii::t('UserModule.controllers_AccountController', 'Not found user'));
        }

        $user->status = User::STATUS_ENABLED;
        $user->save();

        if(!$user->hasErrors()) {
            $model = new BaseAccountLogin();
            $model->username = $user->email;
            $model->password = $user->email;
            if ($model->validate() && $model->login()) {
                // add session error if need
            }
        }
        return $this->redirect(Url::toRoute('/'));
    }

    public function actionLogin()
    {
        // If user is already logged in, redirect him to the dashboard
        if (!Yii::$app->user->isGuest) {
            $this->redirect(Yii::$app->user->returnUrl);
        }

        // Show/Allow Anonymous Registration
        $canRegister = Setting::Get('anonymousRegistration', 'authentication_internal');
        $model = new BaseAccountLogin();

        // if it is ajax validation request
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'account-login-form') {
            echo ActiveForm::validate($model);
            Yii::$app->end();
        }
        
        // collect user input data
        if (isset($_POST['BaseAccountLogin'])) {

            $model->attributes = $_POST['BaseAccountLogin'];
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

    public function setAlert($message = '', $type = '')
    {
        if(in_array($type, ['success', 'error'])) {
            Yii::$app->session->setFlash($type, $message);
        }
    }

    protected function parseExpression($string)
    {
        $stringArray = explode(" , ", strtolower($string));
        $M_Reg = $_POST['ManageRegistration'];
        $result = [];
        foreach ($stringArray as $keyArray => $muchString) {
            $string = explode("then", $muchString)[0];
            $string = preg_replace("/((&&|||) email_domain = [\'\"](.*?)[\'\"])/i", "", $string);
            preg_match_all("/(([a-z0-9_]*)[\s]{0,1}=[\s]{0,1}\"(.*?)\")/i", $string, $array, PREG_SET_ORDER);
            $return = $this->deleteZeroColumnInArray($array);

            foreach ($return as $item) {
                $expressionItem = trim($item[1]);
                $keyItem = trim($item[2]);
                $valueItem = trim($item[3]);
                if (isset($M_Reg[$keyItem]) && $keyItem != "email_domain" && $keyItem != "subject_area") {
                    if (!in_array(strtolower($M_Reg[$keyItem]), array_map('trim', explode(',', strtolower($valueItem))))) {
                        $errors[$keyArray][$keyItem] = $M_Reg[$keyItem] . " not in array " . '",["' . str_replace(' ', '","', $valueItem) . '"]';
                    }
                }

                if (isset($M_Reg['subject_area']) && !empty($M_Reg['subject_area']) && $keyItem == "subject_area") { // because it dependency and this array given
                    foreach ($M_Reg['subject_area'] as $subjectItem) {
                        if (!in_array(strtolower($subjectItem), array_map('trim', explode(',', strtolower($valueItem))))) {
                            $errors[$keyArray]['subject_area'][] = $subjectItem . ' not in ' . '["' . str_replace(' ', '","', $valueItem) . '"]';
                        }
                    }
                }
            }

            if(empty($errors[$keyArray])) {
                $result[$keyArray] = true;
            } else {
                $result[$keyArray] = false;
            }
        }
        return $result;
    }


    public function actionSecondModal()
    {
        $this->forcePostRequest();
        $this->validateRequredFields();
        $ifRegular = null;
        $thenRegular = null;
        $logic = strtolower(Setting::GetText("logic_enter"));
        $logic_else = Setting::GetText("logic_else");
        if(!empty($logic) && isset(explode("then", $logic)[0])) {
            $ifRegular = $this->ifRegular(explode("then", $logic)[0]);
            $thenRegular = $this->thenRegular($logic);
        }

        $if = '';
        $mailReg = '';
        
        if(!empty($logic) && isset(explode("then", $logic)[0])) {
            $ifs = $this->parseExpression($logic);
        }

        $registerModel = new CustomAccountRegisterForm();
        $registerModel->load(Yii::$app->request->post());
        $registerModel->validate();


        if($registerModel->hasErrors()) {
            $this->setAlert('Incorrect validation email', 'error');
            return json_encode(['flag' => 'redirect']);
            Yii::$app->end();
        }

        $user = new User();
        $user->scenario = 'registration';
        $user->status = User::STATUS_DISABLED;

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

        $user->username = substr($randomFirstName . " " . $randomLastName, 0, 25);
        $user->email = $registerModel->email;
        $user->save();

        if($user->hasErrors()) {
            $this->setAlert('Incorrect user validation', 'error');
            return json_encode(['flag' => 'redirect']);
            Yii::$app->end();
        }

        $userPasswordModel = new Password();
        $userPasswordModel->setPassword($user->email);
        $userPasswordModel->user_id = $user->id;
        $userPasswordModel->save();

        if($userPasswordModel->hasErrors()) {
            $this->setAlert('Incorrect form validation', 'error');
            return json_encode(['flag' => 'redirect']);
            Yii::$app->end();
        }

        $profileModel = $user->profile;
        $profileModel->scenario = 'registration';
        $profileModel->teacher_type = $_POST['ManageRegistration']['teacher_type'];
        $profileModel->user_id = $user->id;
        $profileModel->firstname = $randomFirstName;
        $profileModel->lastname = $randomLastName;
        $profileModel->save();

        if($profileModel->hasErrors()) {
            $this->setAlert('Invalid validation profile', 'error');
            return json_encode(['flag' => 'redirect']);
            Yii::$app->end();
        }

        if(!empty($thenRegular) && !empty($logic) && isset(explode("then", $logic)[0])) {
            if(!empty($ifs) && is_array($ifs)) {
                foreach ($ifs as $key => $if) {
                    if ($if) {
                        $then = array_map('trim', explode(",", $thenRegular[$key]));
                        if (!empty($then)) {
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
                        $logic_else_string = array_map('trim', explode(",", $logic_else));
                        if (!empty($logic_else_string)) {
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
                }
            }

            if(isset($_POST['ManageRegistration']) && !empty($_POST['ManageRegistration']) && is_array($_POST['ManageRegistration'])) {
                $this->addOthertoList();
            }
        }
        
        if (isset($_POST['ManageRegistration']['teacher_type']) && !empty($_POST['ManageRegistration']['teacher_type'])) {
            setcookie("teacher_type_" . $user->id, "user_" . $user->id, time() + (86400 * 30 * 10), "/");
        }

        $registerModel->sendVerifyEmail();

        $this->setAlert('Registration is successful, check your email.', 'success');

        echo json_encode(['flag' => 'redirect']);

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
                if (isset($typeRever[$key]) && !empty($value) && $key != "subject_area" && $key != "teacher_interest") {
                    $manageItem = ManageRegistration::find()->andWhere(['name' => trim($value)])->one();
                    if (empty($manageItem)) {
                        $manage = new ManageRegistration;
                        $manage->name = trim($value);
                        $manage->type = $typeRever[$key];
                        $manage->default = ManageRegistration::DEFAULT_DEFAULT;
                        $manage->save(false);
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

                if(!empty($value) && $key == "teacher_interest" && is_array($value)) {
                    foreach ($value as $itemSubject) {
                        $manageItem = ManageRegistration::find()->andWhere(['name' => trim($itemSubject)])->one();
                        if (empty($manageItem)) {
                            $manage2 = new ManageRegistration;
                            $manage2->name = trim($itemSubject);
                            $manage2->default = ManageRegistration::DEFAULT_DEFAULT;
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
        $result = [];
        $stringArray = explode(" , ", strtolower($string));
        foreach ($stringArray as $keyArray => $muchString) {
            $array = [];
            $tmp = explode("then", $muchString)[1];
            preg_match_all("/[\"|'](.*?)[\"|']/i", $tmp, $array, PREG_SET_ORDER);
            $result[$keyArray] = $array[0][1];
        }
        return $result;
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
            if(!empty($option)) {
                $options.="<option value='$option'>$option</option>";
            }
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
            if(!empty($option)) {
                $ul.='<li data-original-index="' . $i . '"><a tabindex="' . $i . '" class="" style="" data-tokens="null"><span class="text">' . $option . '</span><span class="glyphicon glyphicon-ok check-mark"></span></a></li>';
                $i++;
            }
        }

        if(LogicEntry::getStatusTypeManage(ManageRegistration::TYPE_SUBJECT_AREA)) {
            $ul .= '<li data-original-index="' . ++$i . '"><a tabindex="' . ++$i . '" class="" style="" data-tokens="null"><span class="text">other</span><span class="glyphicon glyphicon-ok check-mark"></span></a></li>';
        }
        return $ul;
    }
}
