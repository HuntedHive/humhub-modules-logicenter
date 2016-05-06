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