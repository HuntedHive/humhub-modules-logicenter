<?php

/**
 * @package humhub.modules.mail
 * @since 0.5
 */
class LogicNotificationWidget extends HWidget
{

    public function init()
    {
    }

    /**
     * Creates the Wall Widget
     */
    public function run()
    {
        $this->render('logicNotification');
    }

}

?>