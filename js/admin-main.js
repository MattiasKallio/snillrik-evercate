jQuery(document).ready(function($) {
    $(".switch").on("click", function() {
        var inputten = $(this).find("input[type=checkbox]");
        if (inputten.is(':checked'))
            inputten.prop('checked', false)
        else {
            inputten.prop('checked', true)
        }

        //console.log(inputten.attr("id"));
    });

    $("#snevercate_testcall").on("click", function() {
        console.log("klkii");
        var data = {
            "action": "snev_testcall",
            "userid": $("#snevercate_testcall_id").val()
        };

        $.post(
            ajaxurl,
            data,
            function(response) {
                console.log(response);
            }
        );
    });

    $(".evercate-select-tag").on("click", function() {
        let thisid = $(this).data("tagid");
        console.log(thisid);
        $("#evercate_course_tag").val(thisid);
    });


});