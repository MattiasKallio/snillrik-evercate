jQuery(document).ready(function($) {
    $('#p1_print_card').on('click', function() {
        window.scrollTo({ 'top': 0 });
        html2canvas(document.querySelector("#snp1_usercard")).then(canvas => {
            //document.body.appendChild(canvas)
            saveAs(canvas.toDataURL(), 'medlemskort.png');
        });

    });

    $('#Password1, #Password2').on('keyup', function() {
        $("#Password2")[0].setCustomValidity("");
        $("#Password1")[0].setCustomValidity("");
        let plen = $('#Password1').val().length;
        if (plen < 6) {
            $("#Password1")[0].setCustomValidity("För kort lösen måste vara minst 6 tecken." + $('#Password1').val());
            $('#snp1_password_error').html('Matching').css('color', 'red');
        } else if ($('#Password1').val() != $('#Password2').val()) {
            $("#Password2")[0].setCustomValidity("Lösenorden matchar inte.");
            $('#snp1_password_error').html('Not Matching').css('color', 'red');
        } else if ($('#Password0').val() === $('#Password1').val()) {
            $("#Password1")[0].setCustomValidity("Det är samma lösen som det gamla");
            //$("#Password2")[0].setCustomValidity("Det är samma lösen som det gamla");
            $('#snp1_password_error').html('Samma lösen').css('color', 'red');
        } else {
            $("#Password2")[0].setCustomValidity("");
            $("#Password1")[0].setCustomValidity("");
            $('#snp1_password_error').html('').css('color', 'green');
        }
    });

    $("#snp1_password").on("submit", function(e) {
        e.preventDefault();
        let password0 = $('#Password0').val();
        let password = $('#Password1').val();

        var data = {
            "action": "p1_updateuser",
            "userinfos": { "currentpass": password0, "WebPass": password }
        };

        $.post(
            snp1.ajax_url,
            data,
            function(response) {
                console.log(response);
                let data = response.data;
                if (data.reply == "OK") {
                    $(".snp1_password_error").html(data.html_out);
                } else if (data.reply == "Error") {
                    $(".snp1_password_error").html(data.html_out);
                }
            }
        );

    });
    $("#snp1_userlogin").on("submit", function(e) {
        e.preventDefault();
        let email = $('#Email').val();
        let password = $('#Password').val();

        var data = {
            "action": "p1_userlogin",
            "p1_email": email,
            "p1_password": password
        };

        $.post(
            snp1.ajax_url,
            data,
            function(response) {
                //console.log(response);
                let data = response.data;
                if (data.reply == "OK") {
                    $(".snp1_login_error").html(data.html_out);
                    let redirect_to = $('#snp1_login_redirect').val();
                    window.location.href = redirect_to;
                } else if (data.reply == "Error") {
                    $(".snp1_login_error").html(data.html_out);
                }
            }
        );

    });

    $("#snp1_userconnect").on("submit", function(e) {
        e.preventDefault();
        let email = $('#C_Email').val();
        let fname = $('#C_FirstName').val();
        let lname = $('#C_LastName').val();

        var data = {
            "action": "p1_userconnect",
            "p1_email": email,
            "p1_fname": fname,
            "p1_lname": lname
        };
        console.log(data);
        $.post(
            snp1.ajax_url,
            data,
            function(response) {
                //console.log(response);
                let data = response;
                if (data.reply == "OK") {
                    $(".snp1_connect_error").html(data.html_out);
                    //let redirect_to = $('#snp1_login_redirect').val();
                    //window.location.href = redirect_to;
                    let redirect_to = $('#snp1_login_redirect').val();
                    window.location.href = redirect_to;
                } else {
                    $(".snp1_connect_error").html(data.html_out);
                }
            }
        );

    });

    $("#snp1_userinfo").on("submit", function(e) {
        e.preventDefault();
        let all_input = $(this).find("input, select, textarea").get();
        let all_out = {};

        $.each(all_input, function() {
            all_out[$(this).attr("id")] = $(this).val();
        });

        var data = {
            "action": "p1_updateuser",
            "userinfos": all_out
        };

        $.post(
            snp1.ajax_url,
            data,
            function(response) {

                let data = response.data;

                if (data.reply == "OK") {
                    $(".snp1_userinfo_error").html(data.html_out);
                    let redirect_to = $('#snp1_login_redirect').val();
                    window.location.href = redirect_to;
                } else {
                    $(".snp1_userinfo_error").html(data.html_out);
                }
            },


        );

    });

    $("#snp1_userregister").on("submit", function(e) {
        e.preventDefault();
        let all_input = $(this).find("input, select, textarea").get();
        let all_out = {};

        $.each(all_input, function() {
            all_out[$(this).attr("id")] = $(this).val();
        });

        console.log(all_out);

        var data = {
            "action": "p1_registeruser",
            "userinfos": all_out
        };

        $.post(
            snp1.ajax_url,
            data,
            function(response) {
                console.log(response);
                let data = response.data;
                if (data.reply == "OK") {
                    $(".snp1_userinfo_error").html(data.html_out);
                    let redirect_to = $('#snp1_login_redirect').val();
                    //window.location.href = redirect_to;
                } else {
                    $(".snp1_userinfo_error").html(data);
                    console.log(response.data);
                }
            }
        );

    });

    $("#snp1_userregister").on("keyup", "#Referens", function() {
        let the_ref = $(this).val();
        if (the_ref.length > 2) {
            //console.log(the_ref);
            $("#snp1_reference_name").html("Hämtar...");
            var data = {
                "action": "p1_check_ref_username",
                "ref": the_ref
            };
            $.post(
                snp1.ajax_url,
                data,
                function(response) {
                    let datan = response.data;
                    $("#snp1_reference_name").html(response.data);
                    console.log(response);
                }
            );
        }
    });

});

function saveAs(uri, filename) {
    var link = document.createElement('a');
    if (typeof link.download === 'string') {
        link.href = uri;
        link.download = filename;
        //Firefox requires the link to be in the body
        document.body.appendChild(link);
        //simulate click
        link.click();
        //remove the link when done
        document.body.removeChild(link);
    } else {
        window.open(uri);
    }
}