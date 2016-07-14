<?php

use humhub\widgets\NotificationArea;

return [
    'id' => 'logicenter',
    'class' => 'humhub\modules\logicenter\Module',
    'namespace' => 'humhub\modules\logicenter',
    'events' => array(
        array('class' => NotificationArea::className(), 'event' => NotificationArea::EVENT_INIT, 'callback' => array('humhub\modules\logicenter\Events', 'onLogicNotificationAddonInit')),
    ),
];
?>