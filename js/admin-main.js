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

    $("#snevercate_woo_order_post").on("click", function(e) {
        e.preventDefault();
        $("#snevercate_order_push_message").html("");
        var data = {
            "action": "evercate_push_woo_to_register",
            "woo_order_id": woocommerce_admin_meta_boxes.post_id //assuming woo is pluggedin.
        };

        $.post(
            ajaxurl,
            data,
            function(response) {
                console.log(response);
                if (response.data != "")
                    $("#snevercate_order_push_message").html(response.data);
                else
                    location.reload();

            }
        );
    });

});