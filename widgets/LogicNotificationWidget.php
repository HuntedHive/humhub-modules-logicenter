<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.org/licences
 */

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
