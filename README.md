# humhub-modules-logicenter

This module adds functionality and has basic design for first entry http://i.imgur.com/QUdmvLL.png. Before activating/deactivating module, please follow the instructions in  README

Add code to config/common.php and add code only to Rules

    'admin/setting/basic' => 'logicenter/customs/basic',
    'user/auth/login' => 'logicenter/popup/login',
    
And change urlFormat:

    'urlFormat' => 'path'
    
If you disable module  you need to delete the line below:

    'admin/setting/basic' => 'logicenter/customs/basic',
    'user/auth/login' => 'logicenter/popup/login',