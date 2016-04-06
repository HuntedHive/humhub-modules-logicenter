<?php

class LogicModule extends HWebModule
{
    /**
     * Inits the Module
     */
    public function init()
    {
        $this->setImport(array(
            'logicenter.models.*',
            'logicenter.forms.*',
        ));
    }
}
