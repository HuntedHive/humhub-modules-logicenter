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


class CustomsController extends Controller
{
    
    public $subLayout = "application.modules_core.admin.views._layout";

    /**
     * Returns a List of Users
     */
    public function actionBasic()
    {
        if(!Yii::app()->user->isAdmin()) {
            return $this->redirect(['/']);
        }

        Yii::import('admin.forms.*');

        $form = new BasicSettingsLogicForm;
        $form->name = HSetting::Get('name');
        $form->baseUrl = HSetting::Get('baseUrl');
        $form->defaultLanguage = HSetting::Get('defaultLanguage');
        $form->dashboardShowProfilePostForm = HSetting::Get('showProfilePostForm', 'dashboard');
        $form->tour = HSetting::Get('enable', 'tour');
        $form->logic_enter = HSetting::GetText('logic_enter');
        $form->logic_else = HSetting::GetText('logic_else');
        
        $form->defaultSpaceGuid = "";
        foreach (Space::model()->findAllByAttributes(array('auto_add_new_members' => 1)) as $defaultSpace) {
            $form->defaultSpaceGuid .= $defaultSpace->guid . ",";
        }

        if (isset($_POST['ajax']) && $_POST['ajax'] === 'basic-settings-form') {
            echo CActiveForm::validate($form);
            Yii::app()->end();
        }

        if (isset($_POST['BasicSettingsLogicForm'])) {
            $_POST['BasicSettingsLogicForm'] = Yii::app()->input->stripClean($_POST['BasicSettingsLogicForm']);
            $form->attributes = $_POST['BasicSettingsLogicForm'];

            if ($form->validate()) {

                $form->logic_enter = $this->validateText($form->logic_enter);
                preg_match("/[\s]{2,}/", $form->logic_enter, $emptyR);
                if(empty($emptyR)) {
                    HSetting::Set('name', $form->name);
                    HSetting::Set('baseUrl', $form->baseUrl);
                    HSetting::Set('defaultLanguage', $form->defaultLanguage);
                    HSetting::Set('enable', $form->tour, 'tour');
                    HSetting::Set('showProfilePostForm', $form->dashboardShowProfilePostForm, 'dashboard');
                    HSetting::SetText('logic_enter', $form->logic_enter);
                    HSetting::SetText('logic_else', $form->logic_else);

                    $spaceGuids = explode(",", $form->defaultSpaceGuid);

                    // Remove Old Default Spaces
                    foreach (Space::model()->findAllByAttributes(array('auto_add_new_members' => 1)) as $space) {
                        if (!in_array($space->guid, $spaceGuids)) {
                            $space->auto_add_new_members = 0;
                            $space->save();
                        }
                    }

                    // Add new Default Spaces
                    foreach ($spaceGuids as $spaceGuid) {
                        $space = Space::model()->findByAttributes(array('guid' => $spaceGuid));
                        if ($space != null && $space->auto_add_new_members != 1) {
                            $space->auto_add_new_members = 1;
                            $space->save();
                        }
                    }

                    // set flash message
                    Yii::app()->user->setFlash('data-saved', Yii::t('AdminModule.controllers_SettingController', 'Saved'));
                }
                $this->redirect(Yii::app()->createUrl('//admin/setting/basic'));
            }
        }
        
        $this->render('basic', array('model' => $form));
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
