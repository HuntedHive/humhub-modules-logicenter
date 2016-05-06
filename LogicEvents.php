<?php

class LogicEvents
{  
    public static function onLogicNotificationAddonInit($event)
    {
        if (Yii::app()->user->isGuest) {
            return;
        }

        $event->sender->addWidget('application.modules.logicenter.widgets.LogicNotificationWidget', array(), array('sortOrder' => 90));
    }
}