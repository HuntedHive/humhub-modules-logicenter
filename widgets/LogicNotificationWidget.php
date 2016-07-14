<?php

namespace humhub\modules\logicenter\widgets;

class LogicNotificationWidget extends \humhub\components\Widget
{

    /**
     * Creates the Wall Widget
     */
    public function run()
    {
       return $this->render('logicNotification');
    }

}

?>