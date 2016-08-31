<?php

namespace humhub\modules\logicenter\controllers;

use humhub\libs\DynamicConfig;
use humhub\modules\logicenter\forms\BasicSettingsLogicForm;
use humhub\modules\space\models\Space;
use yii\bootstrap\ActiveForm;
use humhub\models\Setting;
use humhub\components\Controller;
use Yii;

class CustomsController extends Controller
{
    
    public $subLayout = "@humhub/modules/admin/views/layouts/main";

    /**
     * Returns a List of Users
     */
    public function actionBasic()
    {
        if(!Yii::$app->user->isAdmin()) {
            return $this->redirect(['/']);
        }

        $form = new BasicSettingsLogicForm();
        $form->name = Setting::Get('name');
        Setting::Set('baseUrl', \yii\helpers\BaseUrl::base(true));
        $form->baseUrl = Setting::Get('baseUrl');
        $form->defaultLanguage = Setting::Get('defaultLanguage');
        $form->dashboardShowProfilePostForm = Setting::Get('showProfilePostForm', 'dashboard');
        $form->tour = Setting::Get('enable', 'tour');
        $form->logic_enter = Setting::GetText('logic_enter');
        $form->logic_else = Setting::GetText('logic_else');
        
        $form->defaultSpaceGuid = "";
        foreach (Space::find()->andWhere(['auto_add_new_members' => 1])->all() as $defaultSpace) {
            $form->defaultSpaceGuid .= $defaultSpace->guid . ",";
        }

        if (isset($_POST['ajax']) && $_POST['ajax'] === 'basic-settings-form') {
            echo ActiveForm::validate($form);
            Yii::$app->end();
        }

        if (isset($_POST['BasicSettingsLogicForm'])) {
            $form->load(Yii::$app->request->post());

            if ($form->validate()) {
                $form->logic_enter = $this->validateText($form->logic_enter);
                preg_match("/[\s]{2,}/", $form->logic_enter, $emptyR);

                if(empty($emptyR)) {
                    Setting::Set('name', $form->name);
                    Setting::Set('baseUrl', $form->baseUrl);
                    Setting::Set('defaultLanguage', $form->defaultLanguage);
                    Setting::Set('enable', $form->tour, 'tour');
                    Setting::Set('showProfilePostForm', $form->dashboardShowProfilePostForm, 'dashboard');
                    Setting::SetText('logic_enter', $form->logic_enter);
                    Setting::SetText('logic_else', $form->logic_else);

                    $spaceGuids = explode(",", $form->defaultSpaceGuid);

                    // Remove Old Default Spaces
                    foreach (Space::find()->andWhere(array('auto_add_new_members' => 1))->all() as $space) {
                        if (!in_array($space->guid, $spaceGuids)) {
                            $space->auto_add_new_members = 0;
                            $space->save();
                        }
                    }

                    // Add new Default Spaces
                    foreach ($spaceGuids as $spaceGuid) {
                        $space = Space::find()->andWhere(array('guid' => $spaceGuid))->one();
                        if ($space != null && $space->auto_add_new_members != 1) {
                            $space->auto_add_new_members = 1;
                            $space->save();
                        }
                    }
                    DynamicConfig::rewrite();
                    // set flash message

                    Yii::$app->session->setFlash('data-saved', Yii::t('AdminModule.controllers_SettingController', 'Saved'));
                } else {
                    $form->addError("logic_enter", "Parsing string error");
                    return $this->render('basic', array('model' => $form));
                }

                Yii::$app->search->rebuild();
                $this->redirect(['//admin/setting/basic']);
            }
        }

        return $this->render('basic', array('model' => $form));
    }

    public function validateText($text)
    {
        $text = preg_replace("/([\s]+)/i", " ", $text);
        $text = $this->trim_spaces_around_quotes($text);
        $text = trim($text);
        return $text;
    }

    protected function trim_spaces_around_quotes($text)
    {
        preg_match_all("/[\"'](.*?)[\"']/i", $text, $array);
        foreach ($array[1] as $match) {
            $text=str_replace($match, trim($match), $text);
        }

        return $text;
    }

    protected $o = ['and', 'if', 'or'];
    protected function parseString($string)
    {
        $string = strtolower($string);
        foreach ($this->o as $oper) {
            $string = str_replace($oper, $this->_o($oper), $string);
        }

        $string = preg_replace("/((&&|||) email_domain = [\'\"](.*?)[\'\"])/i", "", $string);
        preg_match_all("/(([a-z0-9_]*) = \"(.*?)\")/i", $string, $array, PREG_SET_ORDER);
        $return = $this->deleteZeroColumnInArray($array);
        foreach ($return as $item) {
            if(trim($item[2]) != "email_domain") {
                 $string = str_replace($item[1],'in_array("' . $_POST['ManageRegistration'][trim($item[2])] . '",["' . str_replace(' ', '","', trim($item[3])) . '"]) ', $string);
            }
        }
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
            $newArray[] = $value;
        }

        return $newArray;
    }
}
