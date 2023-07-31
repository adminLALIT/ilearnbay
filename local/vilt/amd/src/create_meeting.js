$('document').ready(function () {
    $('#form_register').on('keyup keypress', function (e) {

        var keyCode = e.keyCode || e.which;
        if (keyCode === 13) {
            e.preventDefault();
            return false;
        }
    });

    $('#id_verify').click(function () {
        var email = $('#reg_email').val();
        var meetingid = $('#meetingid').val();
        if (IsEmail(email) == false) {
            alert("Enter valid email address");
            //$('label[for="reg_email"].error').html("Enter valid email address");
            $("#reg_email").focus();
            return false;
        }
        $.ajax({
            url: "ajax.php",
            method: "POST",
            dataType: "json",
            data: {
                email: email,
                mail: true,
                meetingid: meetingid
            },
            success: function (data) {
                if (data.send) {
                   
                    $('.downmsg').html("Enter OTP sent to your email ID.");
                    var timer2 = "05:00";
                    var interval = setInterval(function () {
                        var timer = timer2.split(':');
                        //by parsing integer, I avoid all extra string processing
                        var minutes = parseInt(timer[0], 10);
                        var seconds = parseInt(timer[1], 10);
                        --seconds;
                        minutes = (seconds < 0) ? --minutes : minutes;
                        if (minutes < 0) clearInterval(interval);
                        seconds = (seconds < 0) ? 59 : seconds;
                        //minutes = (minutes < 10) ?  minutes : minutes;
                        //$('.countdown').html(minutes + ':' + seconds);
                        $('.downtimer').html('OTP is Valid for (' + minutes + ':' + seconds + ') minutes');
                        timer2 = minutes + ':' + seconds;
                        if (minutes === 0 && seconds === 0) {
                            clearInterval(interval);
                            document.getElementById('id_verify').removeAttribute('disabled');
                            document.getElementById('id_verify').innerText = "Resend OTP";
                        } else {
                            document.getElementById('id_verify').setAttribute('disabled', true);
                        }

                    }, 1000);
                }
                if (data.exist) {
                    alert("Email already exist");
                    return false;
                }
                if (data.failed) {
                    alert("Due to some error mail not sent");
                    return false;
                }
            },
            error: function (e) { }
        });
    });

    function IsEmail(email) {
        var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        if (!regex.test(email)) {
            return false;
        } else {
            return true;
        }
    }

});
