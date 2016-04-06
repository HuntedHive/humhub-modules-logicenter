<?php



class CustomsController extends Controller
{
    
    public $subLayout = "application.modules_core.admin.views._layout";
    
    
    /**
     * Returns a List of Users
     */
    public function actionBasic()
    {
        Yii::import('admin.forms.*');

        $form = new BasicSettingsLogicForm;
        $form->name = HSetting::Get('name');
        $form->baseUrl = HSetting::Get('baseUrl');
        $form->defaultLanguage = HSetting::Get('defaultLanguage');
        $form->dashboardShowProfilePostForm = HSetting::Get('showProfilePostForm', 'dashboard');
        $form->tour = HSetting::Get('enable', 'tour');
        $form->logic_enter = HSetting::Get('logic_enter');
        $form->logic_else = HSetting::Get('logic_else');
        
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
                HSetting::Set('name', $form->name);
                HSetting::Set('baseUrl', $form->baseUrl);
                HSetting::Set('defaultLanguage', $form->defaultLanguage);
                HSetting::Set('enable', $form->tour, 'tour');
                HSetting::Set('showProfilePostForm', $form->dashboardShowProfilePostForm, 'dashboard');
                HSetting::Set('logic_enter', $form->logic_enter);
                HSetting::Set('logic_else', $form->logic_else);

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
                $this->redirect(Yii::app()->createUrl('//admin/setting/basic'));
            }
        }
        
        $this->render('basic', array('model' => $form));
    }
}
