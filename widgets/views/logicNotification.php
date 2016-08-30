<?php

use humhub\modules\logicenter\models\LogicEntry;

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
        var orderList = new Array('Knowledge', 'Mentor circles', 'Mentor circle', 'All circles', 'Live Chat', 'Menu');
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

        $.get('<?= Yii::getAlias("@web") . '/resources' ?>' + '/space/spacechooser.js', function (data) {
           $("body").append('<script type="text/javascript">' + data + '<\/script>');
        });
    });
</script>