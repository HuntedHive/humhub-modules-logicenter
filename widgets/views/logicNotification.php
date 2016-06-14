<?php
    if(Yii::app()->controller->id == "customs") {
        $cs = Yii::app()->getClientScript();
        $cs->registerScriptFile("http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
    }

    $assetPrefix = Yii::app()->assetManager->publish(Yii::getPathOfAlias("application") . '/modules_core/space/resources', true, 0, defined('YII_DEBUG'));

if(LogicEntry::getStatusHomeOfUser()) {
?>

<script>
    $(document).ready(function() {
        $("#topbar-second ul.nav li.dashboard").remove();
    });
</script>

<?php } ?>

<script>
    $(document).ready(function() {
        var orderList = new Array('Knowledge', 'Mentor circles', 'All circles', 'Live Chat', 'Menu');
        arrayString = orderList.toString();
        arrayLowerString = arrayString.toLowerCase();
        arrayLower = arrayLowerString.split(",");
        var menuPanelList = $("#topbar-second ul.nav:first li:visible").clone();
        var orderListOther = {};

        $("#topbar-second ul.nav:first").empty();
        $.each(orderList, function( index, orderItem ) {
            $.each(menuPanelList, function (index2, value) {
                var string = value.children[0].innerText;
                if ($.trim(string) == orderItem) {
                    $("#topbar-second ul.nav:first").append(value);
                } else {
                    if($.inArray($.trim(string).toLowerCase(), arrayLower) == -1) {
                        orderListOther[$.trim(string).toLowerCase()] = value;
                    }
                }
            });
        });
        $("#topbar-second ul.nav:last").after("<ul class='pull-right nav-right nav'></ul>");
        $.each(orderListOther, function (index, orderItem) {
            $(".nav-right").append(orderItem);
        });

        $(".listOrderLoad").animate({opacity: 1}, 500);

        $.post('<?= $assetPrefix ?>' + '/spacechooser.js', function (data) {
           $("body").append('<script type="text/javascript">' + data + '<\/script>');
        });
    });
</script>