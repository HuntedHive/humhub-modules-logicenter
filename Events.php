<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.org/licences
 */

namespace humhub\modules\logicenter;
use Yii;

use humhub\modules\logicenter\widgets\LogicNotificationWidget;

class Events extends \yii\base\Object
{  
    public static function onLogicNotificationAddonInit($event)
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        return $event->sender->addWidget(LogicNotificationWidget::className(), array(), array('sortOrder' => 90));
    }
}
