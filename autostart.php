<?php
Yii::app()->moduleManager->register(array(
    'id' => 'logicenter',
    'class' => 'application.modules.logicenter.LogicModule',
    'import' => array(
        'application.modules.logicenter.*',
        'application.modules.logicenter.forms.*',
        'application.modules.logicenter.models.*',
    ),
    'events' => array(
    ),
));
?>