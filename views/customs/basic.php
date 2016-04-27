<style type="text/css">
	.form-control-code{
		font-family: 'Courier';
	}
</style>

<div class="panel panel-default">
    <div class="panel-heading"><?php echo Yii::t('AdminModule.views_setting_index', '<strong>Basic</strong> settings'); ?></div>
    <div class="panel-body">

        <?php
        
        $form = $this->beginWidget('CActiveForm', array(
            'id' => 'basic-settings-form',
            'enableAjaxValidation' => false,
        ));
        ?>

        <?php echo $form->errorSummary($model); ?>

        <div class="form-group">
            <?php echo $form->labelEx($model, 'name'); ?>
            <?php echo $form->textField($model, 'name', array('class' => 'form-control', 'readonly' => HSetting::IsFixed('name'))); ?>
        </div>

        <div class="form-group">
            <?php echo $form->labelEx($model, 'baseUrl'); ?>
            <?php echo $form->textField($model, 'baseUrl', array('class' => 'form-control', 'readonly' => HSetting::IsFixed('baseUrl'))); ?>
            <p class="help-block"><?php echo Yii::t('AdminModule.views_setting_index', 'E.g. http://example.com/humhub'); ?></p>
        </div>

        <div class="form-group">
            <?php echo $form->labelEx($model, 'defaultLanguage'); ?>
            <?php echo $form->dropDownList($model, 'defaultLanguage', Yii::app()->params['availableLanguages'], array('class' => 'form-control', 'readonly' => HSetting::IsFixed('defaultLanguage'))); ?>
        </div>


        <?php echo $form->labelEx($model, 'defaultSpaceGuid'); ?>
        <?php echo $form->textField($model, 'defaultSpaceGuid', array('class' => 'form-control', 'id' => 'space_select')); ?>
        <?php
        $this->widget('application.modules_core.space.widgets.SpacePickerWidget', array(
            'inputId' => 'space_select',
            'model' => $model,
            'maxSpaces' => 50,
            'attribute' => 'defaultSpaceGuid'
        ));
        ?>
        
        <strong><?php echo Yii::t('AdminModule.views_setting_index', 'Matching Logic'); ?></strong>
        <div class="form-group">
            <p class="help-block">
				<?php echo Yii::t('AdminModule.views_setting_index', 'Custom if-then logic for default user space.'); ?>
            </p>
            <p class="help-block">
                <i style="color:#0A246A;font-size:10px">
                    <?php echo Yii::t('AdminModule.views_setting_index', 'Help: IF teacher_type = "math math2" and teacher_level = "level" and subject_area = "math math2" and email_domain = "edu.au" THEN insert into "Welcome Space, default, some-some"'); ?>
                </i>
            </p>
            <?php echo $form->textArea($model, 'logic_enter', array('class' => 'form-control form-control-code', 'placeholder' => 'Enter if-then matching logic...', 'spellcheck' => 'false')); ?>
        </div>
        
        <div class="form-group">
            <p class="help-block">
				<?php echo Yii::t('AdminModule.views_setting_index', 'Custom else logic for default user space.'); ?>
            </p>
            <p class="help-block">
                <i style="color:#0A246A;font-size:10px">
                    <?php echo Yii::t('AdminModule.views_setting_index', 'Help: Welcome Space, default, some, callback'); ?>
                </i>
            </p>
            <?php echo $form->textArea($model, 'logic_else', array('class' => 'form-control form-control-code', 'placeholder' => 'Enter else matching logic...', 'spellcheck' => 'false')); ?>
        </div>
        

        <strong><?php echo Yii::t('AdminModule.views_setting_index', 'Dashboard'); ?></strong>
        <div class="form-group">
            <div class="checkbox">
                <label>
                    <?php echo $form->checkBox($model, 'tour'); ?> <?php echo $model->getAttributeLabel('tour'); ?>
                </label>
            </div>
            <div class="checkbox">
                <label>
                    <?php echo $form->checkBox($model, 'dashboardShowProfilePostForm'); ?> <?php echo $model->getAttributeLabel('dashboardShowProfilePostForm'); ?>
                </label>
            </div>
        </div>

        <hr>

        <?php echo CHtml::submitButton(Yii::t('AdminModule.views_setting_index', 'Save'), array('class' => 'btn btn-primary')); ?>

        <!-- show flash message after saving -->
        <?php $this->widget('application.widgets.DataSavedWidget'); ?>

        <?php $this->endWidget(); ?>

    </div>
</div>

