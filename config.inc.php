<?php

define("PHP_MAZE_VERSION", "2.0");

//画面関係初期設定
define("VIEW_WIDTH", 400);
define("VIEW_HEIGHT", 400);

/*
 Mmaze Data(32x32)

 # : Wall
 S : Start point
 G : Goal point
 o : ramen
 < : arrow left
 > : arrow right
*/
$maze_data = array (
	"################################",
	"#   #         o ##     o# #  o #",
	"# S ###### #### ## ###### #  G #",
	"#            o#           # ## #",
	"# ############# ######### # ## #",
	"#         ># ##        ## # ## #",
	"#<####   o## ## ######o##   ## #",
	"######              ## ####### #",
	"#       # ## ######            #",
	"# ##### # ## #o##o######## #####",
	"# # o # # ##               #   #",
	"# # # # # ################## # >",
	"# # # # #       >#           # #",
	"# # # # #<##### ## ########### >",
	"#   #   #######    #o          #",
	"##### ###          ########### >",
	"#     ###   #o# ##  ##   ##### #",
	"#  o        o#o              # >",
	"<     ###   #o# G   ##   ### #o#",
	"#     ###        o  ######## # >",
	"# ######## ###### ###        # #",
	"#        # ######     ###### # >",
	"###### # # ##   ############ # #",
	"#G     # #      ##  o        # >",
	"######## ####   ##     ####### #",
	"#         # ######## ####G o## >",
	"# ###o# # #      o## #        o#",
	"# # ### # ####### ##   ##### # #",
	"#              ## ## #    o# # #",
	"#o###### ###      ############ #",
	"#Go        ###o##              #",
	"################################",
);


define('MESSAGE_GAME_OVER', '無念・・・ゲームオーバー！' );
define('MESSAGE_GAME_CLEAR', '脱出成功！' );
define('MESSAGE_INPUT_YOUR_NAME', 'スコアを登録します。あなたのお名前は？');
define('NO_NAME', 'ななしのごんべ');

$BlkSize = 380;
$BlkSizeHalf= (int)( $BlkSize / 2 );
$w_center = (int)(VIEW_WIDTH  /2);
$h_center = (int)(VIEW_HEIGHT /2);
$ViewDistance	= 900;	//視点との距離
$ViewReduction	= 1.25;	//縮小率


session_name("phpMazeV2ssn");
session_set_cookie_params(600);