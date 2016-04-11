# humhub-modules-logicenter

Add code to config/local/_settings.php in `Components` array 

'urlManager' => array(
    'urlFormat' => 'path',
    'showScriptName' => true,
    'rules' => array(
        'admin/setting/basic' => 'logicenter/customs/basic',
        'user/auth/login' => 'logicenter/popup/login',
    ),
),

If `urlManager` already exists then add code below to only to `Rules`. 

'admin/setting/basic' => 'logicenter/customs/basic',
'user/auth/login' => 'logicenter/popup/login',

If you disable module  you need to delete the line below:

'admin/setting/basic' => 'logicenter/customs/basic',
'user/auth/login' => 'logicenter/popup/login',