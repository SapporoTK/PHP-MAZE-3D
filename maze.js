var game_status = 0;    //1:playing 2:game over 3:game clear
var disable_cmd = false;

// カーソルキーで移動
function keyDown(event) {

    var keycode = event.keyCode;
    var cmd = "";

    switch(keycode)
    {
        case 37: cmd="left";	break;
        case 38: cmd="up";		break;
        case 39: cmd="right";	break;
        case 40: cmd="back";	break;
        default: return;
    }
    move(cmd);
}

function move(cmd)
{
    if( game_status > 1 || disable_cmd ) return;

    var data = {
        mode: "ajax-get-img",
        unique_key : unique_key,
        cmd: cmd
    };

    disable_cmd = true;

    $.ajax({
        type: "POST",
        url: "maze.php",
        dataType: 'json',
        data: data,
        cache: false,
        success: function(json) {

            if( json.error == 1)
            {
                alert("Error. please reload this page.");
                return;
            }

            $("#maze-img").attr("src", json.image);

            if(game_status == 0) {
                $("#maze-img").fadeIn(1200);
            }

            game_status = json.game_status;

            switch(json.game_status)
            {
                case 2:
                case 3:
                    if( json.flg_rank_in == 1) {
                        entry_high_score(json.message);
                    } else {
                        gameover(json.message);
                    }
                    break;
                default:
                    break;
            }
            disable_cmd = false;
        },
        error: function(xhr, textStatus, errorThrown) {
            console.dir(xhr);
            console.dir(textStatus);
            console.dir(errorThrown);
            alert("access error.");
            disable_cmd = false;
        }
    });
}

function gameover(messagae) {
    window.document.onkeydown = null;

    setTimeout(function () {
        alert(messagae);
        document.location = "maze.php?mode=game_end";
    }, 200);
}

function entry_high_score(messagae) {
    setTimeout( function(){
        user_name = prompt(messagae, "");
        $("#user_name").val(user_name);
        $("#form-entry-score").submit();
    }, 200 );
}

window.document.onkeydown = keyDown;
move("init");
