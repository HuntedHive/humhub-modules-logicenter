<?php

use humhub\models\Setting;

?>
<style type="text/css">
	.form-control-code{
		font-family: 'Courier';
	}

    .space-acronym {
        color: #fff;
        text-align: center;
        display: inline-block;
    }
</style>

<div class="panel panel-default">
    <div class="panel-heading"><?php echo Yii::t('AdminModule.views_setting_index', '<strong>Basic</strong> settings'); ?></div>
    <div class="panel-body">

        <?php
        
        $form = \yii\bootstrap\ActiveForm::begin(array(
            'id' => 'basic-settings-form',
            'enableAjaxValidation' => false,
        ));
        ?>


        <div class="form-group">
            <?php echo $form->field($model, 'name')->textInput(array('class' => 'form-control', 'readonly' => Setting::IsFixed('name'))); ?>
        </div>

        <div class="form-group">
            <?php echo $form->field($model, 'baseUrl')->textInput(array('class' => 'form-control', 'readonly' => Setting::IsFixed('baseUrl'))); ?>
            <p class="help-block"><?php echo Yii::t('AdminModule.views_setting_index', 'E.g. http://example.com/humhub'); ?></p>
        </div>

        <div class="form-group">
            <?php echo $form->field($model, 'defaultLanguage')->dropDownList( Yii::$app->params['availableLanguages'], array('class' => 'form-control', 'readonly' => Setting::IsFixed('defaultLanguage'))); ?>
        </div>


        <div class="form-group">
            <?php echo $form->field($model, 'defaultSpaceGuid')->textInput(array('class' => 'form-control', 'id' => 'space_select')); ?>
            <?php
                echo \humhub\modules\space\widgets\Picker::widget(array(
                    'inputId' => 'space_select',
                    'model' => $model,
                    'maxSpaces' => 50,
                    'attribute' => 'defaultSpaceGuid'
                ));
            ?>
        </div>

        <div class="form-group">
            <?php echo $form->field($model, 'timeZone')->dropdownList(\humhub\libs\TimezoneHelper::generateList()); ?>
        </div>

        <label><?php echo Yii::t('AdminModule.views_setting_index', 'Matching Logic'); ?></label>
        <div class="form-group">
            <p class="help-block">
				<?php echo Yii::t('AdminModule.views_setting_index', 'Custom if-then logic for default user space.'); ?>
            </p>
            <p class="help-block">
                <i style="color:#0A246A;font-size:10px">
                    <?php echo Yii::t('AdminModule.views_setting_index', 'Help: IF teacher_type = "math, math2" and teacher_level = "level" and subject_area = "math, math2" THEN insert into "Welcome Space, default, some-some"'); ?>
                </i>
            </p>
            <?php echo $form->field($model, 'logic_enter')->textarea(array('class' => 'form-control form-control-code', 'placeholder' => 'Enter if-then matching logic...', 'spellcheck' => 'false', 'style'=>"height:90px;resize:vertical")); ?>
        </div>
        
        <div class="form-group">
            <p class="help-block">
				<?php echo Yii::t('AdminModule.views_setting_index', 'Custom else logic for default user space.'); ?>
            </p>
            <p class="help-block">
                <i style="color:#0A246A;font-size:10px">
                    <?php echo Yii::t('AdminModule.views_setting_index', 'Help: Welcome Space, default, some, another'); ?>
                </i>
            </p>
            <?php echo $form->field($model, 'logic_else')->textarea(array('class' => 'form-control form-control-code', 'placeholder' => 'Enter else matching logic...', 'spellcheck' => 'false')); ?>
        </div>


        <strong><?php echo Yii::t('AdminModule.views_setting_index', 'Dashboard'); ?></strong>
        <div class="form-group">
            <div class="checkbox">
                <?php echo \yii\helpers\Html::activeCheckbox($model, 'tour'); ?>
            </div>
            <div class="checkbox">
                <?php echo \yii\helpers\Html::activeCheckbox($model, 'share'); ?>
            </div>
            <div class="checkbox">
                <?php echo \yii\helpers\Html::activeCheckbox($model, 'dashboardShowProfilePostForm'); ?>
            </div>
        </div>

        <hr>

        <?php echo \yii\helpers\Html::submitButton(Yii::t('AdminModule.views_setting_index', 'Save'), array('class' => 'btn btn-primary')); ?>

        <!-- show flash message after saving -->
        <?= \humhub\widgets\DataSaved::widget(); ?>

        <?php \yii\bootstrap\ActiveForm::end() ?>

    </div>
</div>

