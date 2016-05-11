<?php
    $cs = Yii::app()->getClientScript();
    $cs->registerScriptFile("http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");

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
        var orderList = new Array('Knowledge', 'My circles', 'Live Chat');
        var menuPanelList = $("#topbar-second ul.nav:first li:visible").clone();

        $("#topbar-second ul.nav:first").empty();
        $.each(orderList, function( index, orderItem ) {
            $.each(menuPanelList, function (index, value) {
                var string = value.children[0].innerText;
                if ($.trim(string) == orderItem) {
                    $("#topbar-second ul.nav:first").append(value);
                }
            });
        });

        $(".listOrderLoad").animate({opacity: 1}, 500);
    });
</script>
