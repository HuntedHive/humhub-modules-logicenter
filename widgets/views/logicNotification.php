<?php
    $cs = Yii::app()->getClientScript();
    $cs->registerScriptFile("http://ajax.googleapis.com/ajax/libs/jquery/1.10.4/jquery.min.js");

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
        var orderList = new Array('Knowledge', 'Home', 'События', 'My circles', 'Live Chat', 'Каталог');
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

        $.post('<?= $assetPrefix ?>' + '/spacechooser.js', function (data) {
           $("body").append('<script type="text/javascript">' + data + '<\/script>');
        });
    });
</script>