<?php
/*

PHP-MAZE v2.0


○ファイル設置後、ranking.txt(スコア記録用ファイル。初期状態ではサイズゼロの空ファイル)のパーミッションを666(rw-rw-rw-)に変更してください。
maze.phpをブラウザで開くとプレイできます。

【更新履歴】
2004/01/18  v1.0
2014/01/20  v1.1	charsetの出力追加、不具合修正、カーソルキー取得、Safari対策等
2017.04.06  v1.2    php7.0に対応させた延命処置。文字コードをEUC-JPからUTF-8に変更。その他不具合等の修正。
2017.08.09  v2.0    移動時にページ遷移せずajaxで画像を取得するように変更。


Author: Tomoya Kawabata
Home Page: https://planet-green.com/

License: GPL v3
*/


require_once "config.inc.php";

ini_set('mbstring.language', 'Japanese');
ini_set('mbstring.substitute_character', 'none');
ini_set('default_charset', "UTF-8");
mb_internal_encoding("UTF-8");
if ( (float)phpversion() < 5.6 ){ iconv_set_encoding('internal_encoding', 'utf-8'); }
setlocale(LC_ALL, 'ja_JP.UTF-8');


define ("MAZE_WALL", 1);
define ("MAZE_START", 2);
define ("MAZE_RAMEN", 100);
define ("MAZE_GOAL", 4);
define ("MAZE_LEFT_ALLOW", 5);
define ("MAZE_RIGHT_ALLOW", 6);

define("STATUS_PLAYING", 1);
define("STATUS_GAME_OVER", 2);
define("STATUS_GAME_CLEAR", 3);


$FLAG_CHECK_TEMAE = 0;

//キャッシュ防止用
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");


session_start();


if( !isset( $_SESSION["life"]) || isset($_GET["debug_reset"]) )
{
	$_SESSION['now_x']	    = 1;
	$_SESSION['now_y']	    = 1;
	$_SESSION['direction']  = 1;
	$_SESSION['life']	    = 100;
	$_SESSION['score']	    = 0;
	$_SESSION['GAME_OVER']  = 0;
	$_SESSION['GAME_CLEAR'] = 0;
	$_SESSION['game_status'] = STATUS_PLAYING;
	$_SESSION['rank_in'] = 0;
	$_SESSION['unique_key'] = sha1(uniqid("",true));
}


// "START"
$moji_start = array(
	//s
	130,50, 270,50, 270,60, 140,60, 140,70, 270,70, 270,100, 130,100, 130,90, 260,90,260,80, 130,80,
	-1,
	//t
	130,120, 270,120, 270,130, 210,130, 210,170, 190,170, 190,130, 130,130,
	-1,
	//a
	130,180, 270,180, 270,240, 250,240, 250,220, 150,220, 150,210,
	250,210, 250,190, 150,190, 150,240, 130,240,
	-1,
	//r
	130,250, 270,250, 270,280, 225,280, 270,300, 240,300, 200,280, 160,280,
	160,270, 250,270, 250,260, 150,260, 150,300, 130,300,
	-1,
	//t
	130,310, 270,310, 270,320, 210,320, 210,360, 190,360, 190,320, 130,320,
	-1
);

$moji_goal = array(
	//G
	110,00, 290,00, 300,10, 300,30, 270,30, 270,20, 130,20, 130,70, 270,70,
	270,60, 210,60, 210,40, 300,40, 300,80, 290,90, 110,90, 100,80, 100,10,
	-1,
	//O
	120,100, 280,100, 300,110, 300,170, 280,180, 260,180, 260,120, 140,120, 140,180,
	120,180, 100,170, 100,110,
	-1,
	120,160, 280,160, 280,180, 120,180,	//下部
	-1,
	//A
	110,190, 290,190, 300,200, 300,280, 270,280, 270,250, 130,250, 130,230, 270,230,
	270,210, 130,210, 130,280, 100,280, 100,200,
	-1,
	//L
	100,290, 130,290, 130,360, 300,360, 300,380, 100,380,
	-1
);

$moji_right = array(
	50,170, 280,170, 200,90, 240,90, 340,190, 240,290, 200,290, 280,210, 50,210,
	-1
);

$moji_left = array(
	40,190, 140,90, 180,90, 100,170, 330,170, 330,210, 100,210, 180,290, 140,290,
	-1
);
	
	 
				
function GetMazeData($x,$y)
{
	global $maze_data;
	
	if($x<0 || $x>31 || $y<0 || $y>31) return MAZE_WALL;

	if( !isset($maze_data[$y]{$x}) )
	{
		trigger_error(" pos: {$y},{$x}");
	};

	$c = $maze_data[$y]{$x};
	
	if($c=="#") return MAZE_WALL;	//壁
	if($c=="S") return MAZE_START;	//スタート
		
	if($c=="o")				//ラーメン
	{
		$key = $x."-".$y;

		if( !empty($_SESSION["got_ramens"][$key]) == 0 )
		{
			return MAZE_RAMEN;
		}
		else return 0;
	}
	
	if($c=="G") return MAZE_GOAL;	//ゴール
	if($c=="<") return MAZE_LEFT_ALLOW;	//左矢印
	if($c==">") return MAZE_RIGHT_ALLOW;	//右矢印
	
	return 0;
}

function GetR_mazeData($x,$y, $vx,$vz, $direction)
{
	//$direction : 0:↑ 1:→ 2:↓ 3:←
	switch($direction)
	{
		case 0: return GetMazeData($x+$vx, $y-$vz);
		case 1: return GetMazeData($x+$vz, $y+$vx);
		case 2: return GetMazeData($x-$vx, $y+$vz);
		case 3: return GetMazeData($x-$vz, $y-$vx);
	}
}

//壁を描く際、手前に他の壁があるかチェックするために使うサブ関数
function WallCheckSub($x,$y, $vx, $vz, $direction)
{
	$mapdata = GetR_mazeData($x,$y, $vx, $vz, $direction);
	if($mapdata && $mapdata!=MAZE_GOAL && $mapdata!=MAZE_RAMEN) return 1; else return 0;
}



//奥行きに応じて暗くするための計算
function gcd($vx,$vz)	//GetColorDeep
{
	$cr = (1.0 - $vz * 0.12  - abs($vx)*0.05 );
	if($cr<0) $cr=0;
	return $cr;
}


//壁の視点X,Y
function GetXY($x,$z)
{
	/*
	全面		  右側面  左側面
	1 ---- 2	  1＼		／1
	|	   |	  |  2	   2  |
	|	   |	  |  3	   3  |
	4------3	  4／		＼4
	
	*/

	global $BlkSize;
	global $BlkSizeHalf;
	global $w_center;
	global $h_center;
	global $ViewDistance;	//視点との距離
	global $ViewReduction;	//縮小率
		
	//ブロック四隅の座標定義
	$x1 = $x * $BlkSize - $BlkSizeHalf;
	$x2 = $x * $BlkSize + $BlkSizeHalf;
	$x3 = $x2;
	$x4 = $x1;
	
	$y1 = $BlkSizeHalf;
	$y2 = $BlkSizeHalf;
	$y3 = -$BlkSizeHalf;
	$y4 = -$BlkSizeHalf;
	
	$z1 = $z * $BlkSize + $BlkSize;
	$zr = $z1 * $ViewReduction;
	
	$tmp = $ViewDistance / $zr; 
		
	$Vx1 = $w_center + (int)( $x1 * $tmp);
	$Vy1 = $h_center - (int)( $y1 * $tmp);
	
	$Vx2 = $w_center + (int)( $x2 * $tmp);
	$Vy2 = $h_center - (int)( $y2 * $tmp);
	
	$Vx3 = $w_center + (int)( $x3 * $tmp);
	$Vy3 = $h_center - (int)( $y3 * $tmp);
	
	$Vx4 = $w_center + (int)( $x4 * $tmp);
	$Vy4 = $h_center - (int)( $y4 * $tmp);
	
		
	//側面の座標定義
	if($x<0) {
		$sx2 = $x2;
		$sy2 = $y1;
		$sx3 = $x2;
		$sy3 = $y3;
		$Vsx1 = $Vx2;
		$Vsy1 = $Vy2;
		$Vsx4 = $Vx3;
		$Vsy4 = $Vy3;
	}
	
	elseif($x>0) {
			$sx2 = $x1;
			$sy2 = $y1;
			$sx3 = $x1;
			$sy3 = $y3;
			$Vsx1 = $Vx1;
			$Vsy1 = $Vy1;
			$Vsx4 = $Vx4;
			$Vsy4 = $Vy4;
	}
	elseif($x==0) {
		$points = array (0);
		return	array($Vx1, $Vy1, $Vx3, $Vy3, $points);
	}
		
	$tmp = $ViewDistance / ( (($z+2) * $BlkSize) * $ViewReduction);
	
	$Vsx2 = $w_center + (int)( $sx2 * $tmp );
	$Vsy2 = $h_center - (int)( $sy2 * $tmp );
	
	$Vsx3 = $w_center + (int)( $sx3 * $tmp );
	$Vsy3 = $h_center - (int)( $sy3 * $tmp );
	
		
	return	array($Vx1, $Vy1, $Vx3, $Vy3, array ($Vsx1,$Vsy1, $Vsx2,$Vsy2, $Vsx3,$Vsy3, $Vsx4,$Vsy4));

}


//x座標と交わる部分での簡易クリッピング
function ClipingFromX($x1,$y1,$x2,$y2, $clip_x)
{
	$a = $y2 - $y1;
	$b = $x1 - $x2;
	return round( (-$a * $clip_x - (-$a * $x1 - $b * $y1)) / $b );
}

//平面のポインタデータを三次元に簡易変換(Z座標に0を追加するだけ)
function Heimen_to_3D(&$points)
{
	$i=0;
	while($i<count($points))
	{
		$ret[] = $points[$i++];
		$ret[] = -$points[$i++];
		$ret[] = 0;
	}
	
	return $ret;
}

//三次元モデルデータの回転(簡易)
function ModelKaiten(&$points, $d)
//$dは0,1,2,3(0,90,180,270) のいずれか
{
	global $BlkSize;
	global $BlkSizeHalf;
	
	$cp = count($points);
	switch($d)
	{
		case 0:	return;
		
		case 1:	//90
				
				$i=0;
				while($i < $cp )
				{
					$points[$i+2]	= abs($points[$i] - $BlkSize); //$BlkSizeHalf;
					$points[$i] = $BlkSizeHalf;
					$i+=3;
				}
				break;
		
		case 2:	//180
				return;
								
		case 3:	//270
				$i=0;
				while($i < $cp )
				{
					$points[$i+2] = $points[$i] ;
					$points[$i ] = $BlkSizeHalf;
					$i+=3;
				}
				break;
	}
}

function ModelOffset(&$points, $x_offset, $y_offset,$z_offset)
{
	$cs = count($points);
	$i=0;
	while($i < $cs)
	{
		$points[$i++] += $x_offset;
		$points[$i++] += $y_offset;
		$points[$i++] += $z_offset;
	}
}


//点(三次元座標)を視点のx,yに変換
function PointHenkan($x,$y,$z)
{
	global $w_center;
	global $h_center;
	global $BlkSize;
	global $ViewDistance;	//視点との距離
	global $ViewReduction;	//縮小率
	
	$z = ($z + $BlkSize) * $ViewReduction;
	$tmp = $ViewDistance/$z;
	$vx = $w_center + (int)( $x * $tmp );
	$vy = $h_center - (int)( $y * $tmp );

	return array($vx, $vy);
}


function Make2Ddata_sub($img,&$src_points,$vx,$vz,$d,$x_offset, $y_offset, $z_offset)	//$vx, $vz, $d, $x, $y,$direction)
{
	global $BlkSizeHalf;
	
	$points = Heimen_to_3D($src_points);	//二次元のポインタデータを三次元に簡易変換
	ModelKaiten($points, $d);				//三次元モデルデータの回転(簡易)
	ModelOffset($points, $x_offset-$BlkSizeHalf, $y_offset+$BlkSizeHalf,$z_offset);	//三次元モデルデータのオフセット
	
	//3D座標を視点座標に変換
	$i=0;
	$flag_gamennai = 0;
	$cp = count($points);
	while($i < $cp )
	{
		list($wx,$wy) = PointHenkan($points[$i++],$points[$i++],$points[$i++]);
		
		if($wx>0 && $wy>0 && $wx<VIEW_WIDTH && $wy<VIEW_HEIGHT) $flag_gamennai=1;	//画面範囲内か
				
		$ViewPoints[] = $wx;
		$ViewPoints[] = $wy;
	}

	//画面範囲内かチェック
	if($flag_gamennai==0) return;

	//描写
	$cr = gcd($vx,$vz);
	
	$col = ImageColorAllocate ($img, 0, 200*$cr, 200*$cr);
	imagefilledpolygon ($img, $ViewPoints, count($ViewPoints)/2, $col);
}



//3Dモデルの描写・(壁に貼るオブジェクト)
function Make2Ddata($img, &$src_points, $vx, $vz, $d, $x, $y,$direction)
//$dはモデルの向き
{
	global $BlkSize;
	global $BlkSizeHalf;
	global $FLAG_CHECK_TEMAE;

	//vx=0(センター)において、手前に別に壁があればスキップ
	
	if($vx==0 && $vz>1)
	{
		if($FLAG_CHECK_TEMAE && $FLAG_CHECK_TEMAE < $vz) return 0;
		
		for( $i = $vz-1 ; $i>0; $i--)
		{
			if( WallCheckSub($x, $y, 0, $i, $direction) ) {
				$FLAG_CHECK_TEMAE = $i;
				return;
			}
		}
	}
	
	//描かなくていい場合の判定
	if($vz==0 && $vx != 0 && $d) return;	//最も手前で、真正面以外
	if($vz>0 && $d==0 && WallCheckSub($x, $y, $vx, $vz-1, $direction) ) return;	//手前に他のブロックが隣接
	
	switch($d)
	{
		case 0:	$x_offset = $vx * $BlkSize;	break;
		case 1:
				if($vx<0) { $x_offset = $vx * $BlkSize + $BlkSizeHalf; }
				if($vx>0) { $x_offset = $vx * $BlkSize - $BlkSizeHalf; }
				break;
		case 2:	$x_offset = $vx * $BlkSize;	break;
		case 3:	
				if($vx<0) { $x_offset = $vx * $BlkSize + $BlkSizeHalf; }
				if($vx>0) { $x_offset = $vx * $BlkSize - $BlkSizeHalf; }
				break;
	}
	$y_offset = 0;
	$z_offset = $vz * $BlkSize;

	if($src_points)
	{
		$p_len = count($src_points);
		$p = 0;

		//"START"などで各文字の繰り返し処理
		while ($p < $p_len)
		{
			$i = 0;
			while ($src_points[$p] != -1)
			{
				$tmp_points[$i++] = $src_points[$p++];
			}
			$p++;

			Make2Ddata_sub($img, $tmp_points, $vx, $vz, $d, $x_offset, $y_offset, $z_offset);
			unset($tmp_points);
		}
	}
}


//壁の描写
function WriteBlock(&$img, $vx, $vz, $x, $y, $direction)
{
	global $FLAG_CHECK_TEMAE;
	
	//vx=0(センター)において、手前に別に壁があればスキップ
	if($vx==0 && $vz>1)
	{
		if($FLAG_CHECK_TEMAE && $FLAG_CHECK_TEMAE < $vz) return 0;
		
		for( $i = $vz-1 ; $i>0; $i--)
		{
			if( WallCheckSub($x, $y, 0, $i, $direction) ) {
				$FLAG_CHECK_TEMAE = $i;
				return 0;
			}
		}
	}
		
	list($f_x1, $f_y1, $f_x2, $f_y2, $points) =  GetXY($vx,$vz);
	
	//全面を描かなくていいブロックの判定
	$flag_wk=1;
	
	
	if($vz==0 && $vx != 0) $flag_wk=0;	//最も手前で、真正面以外
	elseif($f_x1 > VIEW_WIDTH || $f_x2<0) $flag_wk=0;	//最初から画面外
	elseif($vz>0 && WallCheckSub($x, $y, $vx, $vz-1, $direction) ) $flag_wk=0;	//手前に他のブロックが隣接
		
	if($flag_wk)
	{
		$cr = gcd($vx,$vz);
		
		if($_SESSION['GAME_OVER']) 	$col = ImageColorAllocate ($img, 255*$cr, 0, 0);
		else						$col = ImageColorAllocate ($img, 0, 0, 255*$cr);
		
		//簡易クリッピング
		if($f_x1<0) $f_x1=0;
		if($f_x2>VIEW_WIDTH) $f_x2 = VIEW_WIDTH;
		if($f_y1<0) $f_x1=0;
		if($f_y2>VIEW_HEIGHT) $f_x2 = VIEW_HEIGHT;
		
		imagefilledrectangle ( $img,$f_x1, $f_y1, $f_x2, $f_y2,$col);
		//imagerectangle ( $img, $f_x1, $f_y1, $f_x2, $f_y2,$col);
	}
	
	if( $vx==0) return $flag_wk;
	
	//ここから、ブロック側面の表示
	
	//横にブロックが隣接していたら省略
	if( $vx<0 && WallCheckSub($x, $y, $vx+1, $vz, $direction) ) return $flag_wk;
	if( $vx>0 && WallCheckSub($x, $y, $vx-1, $vz, $direction) ) return $flag_wk;
	
	
	if($f_x1 > VIEW_WIDTH || $f_x2<0)
	{
		if($vz==0)	//横のはみっちょの壁
		{
			if($vx==-1) { $points[0]=0;$points[1]=0;$points[6]=0;$points[7]=VIEW_HEIGHT-1; }
			if($vx==1) { $points[0] = VIEW_WIDTH-1; $points[1]=0; $points[6]=VIEW_WIDTH-1; $points[7] = VIEW_HEIGHT-1;}
		}
	}
	
	//四隅が全て画面外だったら省略
	if(   ($points[0] > VIEW_WIDTH && $points[2] > VIEW_WIDTH)  
	   || ($points[0] < 0			&& $points[2] < 0)			 ) return $flag_wk;
	
	
	//クリッピング
	if($points[0]<0)	//画面左での右側面
	{
		$cy1 = ClipingFromX($points[0],$points[1],$points[2],$points[3],0);
		$cy2 = ClipingFromX($points[4],$points[5],$points[6],$points[7],0);
		$points[0] = 0;
		$points[1] = $cy1;
		$points[6] = 0;
		$points[7] = $cy2;
	}
	if($points[0]>VIEW_WIDTH)	//画面右での左側面
	{
		$cy1 = ClipingFromX($points[0],$points[1],$points[2],$points[3],VIEW_WIDTH);
		$cy2 = ClipingFromX($points[4],$points[5],$points[6],$points[7],VIEW_WIDTH);
		$points[0] = VIEW_WIDTH;
		$points[1] = $cy1;
		$points[6] = VIEW_WIDTH;
		$points[7] = $cy2;
	}
	
	$cr = gcd($vx,$vz);
	
	if($_SESSION['GAME_OVER']) 	$col = ImageColorAllocate ($img, 255*$cr, 0, 0);
	else						$col = ImageColorAllocate ($img, 0, 0, 255*$cr);
		
	imagefilledpolygon ( $img, $points, 4, $col );
	//imagepolygon ( $img, $points, 4, $col );
	
	return $flag_wk + 2;
}

//読み込んだ画像の濃度調整
function ImageSelectiveColor($img, $red, $green, $blue)
{ 
	for($i=0 ; $i<imagecolorstotal($img) ; $i++) 
  	{	
  		$col	   = ImageColorsForIndex($img,$i);	
  		$red_set   = $red/100*$col['red'];	
  		$green_set = $green/100*$col['green'];	
  		$blue_set  = $blue/100*$col['blue']; 
  		
  		if($red_set   > 255)   $red_set=255; 
  		if($green_set > 255) $green_set=255; 
  		if($blue_set  > 255)  $blue_set=255;	
  		
  		imagecolorset($img,$i,$red_set,$green_set,$blue_set);	
	} 
 	return $img;
}

//PNGイメージをロードして表示
function PngLoadWrite($img, $filename, $vx, $vz,  $x,$y,$direction )
{
	list($f_x1, $f_y1, $f_x2, $f_y2, $points) =  GetXY($vx,$vz);	//$vz+0.5 オフセット
	
	if( ($f_x1<0 && $f_x2<0) || ($f_x1>VIEW_WIDTH && $f_x2>VIEW_WIDTH)) return;
	if( ($f_y1<0 && $f_y2<0) || ($f_y1>VIEW_HEIGHT && $f_y2>VIEW_HEIGHT)) return;
	if($vz>0 && WallCheckSub($x, $y, $vx, $vz-1, $direction) ) return;	//手前に他のブロックが隣接
		
	$pngimg =  imagecreatefrompng ( $filename);
	
	// now save the file
	$cr = 100 - $vz * 10 - abs($vx)*5;
	ImageSelectiveColor($pngimg, $cr,$cr,$cr);
	
	imagecopyresized( $img, $pngimg, $f_x1, $f_y1, 0, 0, abs($f_x2-$f_x1), abs($f_y2-$f_y1), 200,200);
	
	imagedestroy($pngimg);
	
}

function WriteView($img, $vx, $vz, $x, $y, $direction)
{
	global $moji_start;
	global $moji_goal;
	global $moji_right;
	global $moji_left;
	
	$mapdata = GetR_mazeData($x, $y,  $vx, $vz, $direction);
	
	if($mapdata && $mapdata!=MAZE_RAMEN && $mapdata!=MAZE_GOAL) {
		//ラーメン以外は壁を描く
		$wb_ret = WriteBlock($img, $vx, $vz, $x, $y, $direction);
	}
	//$wb_retは、壁の前面・側面を書いたかどうかのフラグ
				
	if( $mapdata && $mapdata != MAZE_RAMEN)
	{
		switch($mapdata)	//配列名をセット。可変変数で使う
		{
			case MAZE_START:		$obj = &$moji_start;	break;
			case MAZE_GOAL:			$obj = &$moji_goal;     break;
			case MAZE_LEFT_ALLOW:	$obj = &$moji_left;     break;
			case MAZE_RIGHT_ALLOW:	$obj = &$moji_right;	break;
			default:                $obj = false;           break;
		}

		if($mapdata==MAZE_GOAL)
		{
			$wb_ret=3;
			if($vx==0) {
				Make2Ddata($img, $moji_goal,$vx-1, $vz, 1, $x,$y,$direction);
				Make2Ddata($img, $moji_goal,$vx+1, $vz, 3, $x,$y,$direction);
			}
		}
		
		if($wb_ret & 1) Make2Ddata($img, $obj, $vx, $vz, 0, $x,$y,$direction);
		if($wb_ret & 2) 
		{
				if($vx==0) $d=0; 
			elseif($vx<0)  $d=3;
			elseif($vx>0)  $d=1;
			
			Make2Ddata($img, $obj, $vx, $vz, $d, $x,$y,$direction);
		}
		
		return;
	}
		
	if($mapdata==MAZE_RAMEN)
	{
		PngLoadWrite($img, "image1.png", $vx, $vz, $x,$y,$direction);
	}
}

//生命力のバーとスコアなどを描く
function drawLife(&$img)
{
	$col  = ImageColorAllocate ($img,200,0,0);
	$black	= ImageColorAllocate ($img, 0,0,0);
		
	ImageString ($img, 2, 340,18,"LIFE",$col);
	imageFilledrectangle ( $img, 340,30, 392,50, $col);
	imageFilledrectangle ( $img, 341,31, 391,49, $black);
	
	ImageFilledRectangle($img,341,31,341+$_SESSION['life']/2 , 49, $col);
	
	$col  = ImageColorAllocate ($img,200,200,0);
	ImageString ($img, 8, 354,32,sprintf("%03d",$_SESSION['life']),$col);
	
	ImageString ($img, 8, 16,18, sprintf("SCORE : %05d", $_SESSION['score'] ) ,$col);
	
	if( !empty($_SESSION['GAME_OVER']) )
	{
		$col  = ImageColorAllocate ($img,255,255,0);
		ImageString ($img, 8, 156, 180, "GAME OVER !",$col);
	}
	
	elseif( !empty($_SESSION['GAME_CLEAR']) )
	{
		$col  = ImageColorAllocate ($img,255,255,0);
		ImageString ($img, 8, 136, 180, "mission complete !",$col);
	}
	
	elseif( !empty($_SESSION["FLAG_GET_RAMEN"]) )
	{
		$col  = ImageColorAllocate ($img,255,255,0);
		ImageString ($img, 8, 150, 180, "GET RAMEN !",$col);
	
		$_SESSION["FLAG_GET_RAMEN"] = 0;
	}
}

//空の星を描く
function drawStars($img)
{
	//地面
	$y = (int)(VIEW_HEIGHT * 0.6);
	$step = (int)($y / 18);
	
	$r = 0.1;
	$rate = 0;

	while($y <= VIEW_HEIGHT)
	{
		$rate = $rate + 0.1; 
		$col  = ImageColorAllocate ($img,(int)(48*$rate),(int)(40*$rate),(int)(32*$rate));
		
		$y2 = $y + $step;
		if($y2>=VIEW_HEIGHT) $y2 = VIEW_HEIGHT-1;
		ImageFilledRectangle($img,0,$y, VIEW_WIDTH -1, $y2, $col);
		$y += $step;
	}
	
	//stars
	srand ( $_SESSION['direction'] + time()/86600 );

	$xr = (int)(VIEW_HEIGHT * 0.33);
	
	$col  = ImageColorAllocate ($img, 0, 200,200);
	for($i=0; $i<14; $i++)
	{
		imagesetpixel($img, rand(0,VIEW_WIDTH), rand(0,$xr), $col);
	}
	
	$col  = ImageColorAllocate ($img,200, 200,200);
	for($i=0; $i<14; $i++)
	{
		imagesetpixel($img, rand(0,VIEW_WIDTH), rand(0,$xr), $col);
	}
	
	srand(time());
}


function drawImageMain($x,$y,$direction)
{
	$img = imagecreate(VIEW_WIDTH,VIEW_HEIGHT); 	//空の画像を作成
	$bgc = ImageColorAllocate ($img,0,0,0);			//黒で塗りつぶし
	
	drawStars($img);
	
	for($vz = 6; $vz>=0; $vz--)
	{
		WriteView($img, -3, $vz, $x, $y, $direction);
		WriteView($img, -2, $vz, $x, $y, $direction);
		WriteView($img, -1, $vz, $x, $y, $direction);
		WriteView($img,  3, $vz, $x, $y, $direction);
		WriteView($img,  2, $vz, $x, $y, $direction);
		WriteView($img,  1, $vz, $x, $y, $direction);
		WriteView($img,  0, $vz, $x, $y, $direction);
	}
	
	drawLife($img);
	
	//header("Content-type: image/png");
	ImagePng($img);
	imagedestroy($img);
}

function GetRanking($fp)
{
	fseek ($fp,0,SEEK_SET);
	
	$i=0;
	while( $tmp = fgets($fp,1024) )
	{
		list( $r_score[$i], $r_name[$i] ) = sscanf($tmp,"%d\t%s");
		$i++;
	}

	return array( $r_score, $r_name );
	
	//fputs($fp, $str );
	//fclose($fp);
}

function check_score_rank_in($score)
{
	if( !file_exists ("ranking.txt") ) return 1;
	
	$fp = fopen ("ranking.txt","a+");
	if( !flock($fp, LOCK_EX) ) exit();
	
	list( $r_score, $r_name ) = GetRanking($fp);
	fclose($fp);
	
	if( count($r_score) < 20 || $r_score[ count($r_score)-1 ] < $score ) return 1; else return 0;
}



function InsertScore($r_score, $r_name, $name, $score )
{
	for($i=0; $i< count($r_score); $i++) {
		if( $r_score[$i] < $score ) break;
	}

	if( $i == count($r_score) && count($r_score)>=20) return array( $r_score, $r_name);

	//挿入
	$rcount = count($r_score);
	
	if($rcount)
	{
		$tmp_score = array_slice ($r_score, 0, $i);
		$tmp_name  = array_slice ($r_name,	0, $i);

		$tmp_score[] = $score;		
		$tmp_name[]  = $name;	
		
		$tmp_score = array_merge ( $tmp_score, array_slice($r_score, $i)  );
		$tmp_name  = array_merge ( $tmp_name,  array_slice($r_name, $i)   );


		if(count($tmp_score) > 20) {
			$tmp_score = array_slice ($tmp_score, 0, 20);
			$tmp_name  = array_slice ($tmp_name,  0, 20);
		}
	}
	else
	{
		$tmp_score[0] = $score;
		$tmp_name[0]  = $name;
	}

	return array( $tmp_score, $tmp_name);
}

//
function printScore($r_name, $r_score)
{
	print "<strong>スコア一覧</strong><br />";
	
	print "<table id=\"score-table\" cellpadding=\"2\" cellspacing=\"1\">";
	print "<tr bgcolor=\"#CCCCFF\"><th>順位</th><th>名前</th><th>スコア</th></th>\n";
	
	$r=1;
	for($i=0; $i< count($r_score); $i++)
	{
		if( $i && $r_score[$i-1] != $r_score[$i] ) $r++;

		$name  = htmlspecialchars($r_name[$i], ENT_QUOTES);
		print "<tr><td>{$r}</td><td>{$name}</td><td>";
		print sprintf("%05d", $r_score[$i] );
		print "</td></tr>\n";
	}
	print "</table>\n";
}



$mode = empty($_REQUEST['mode']) ? "" : $_REQUEST['mode'];


//名前とスコア登録
if( $mode == "game_end")
{
	if ( $_SESSION['game_status']!=STATUS_GAME_OVER && $_SESSION['game_status']!=STATUS_GAME_CLEAR )
	{
		$scheme = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
		$host  = $_SERVER['HTTP_HOST'];
		$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra = basename(__FILE__);
		header( "Location: $scheme$host$uri/$extra" );
		exit;
	}

	if( !file_exists ("ranking.txt") )
	{
		$fp = @fopen ("ranking.txt","w");
		if(!$fp)
		{
			echo "can't create ranking.txt !";
			exit;
		}
		if( !flock($fp, LOCK_EX) ) exit();
	}
	else
	{
		$fp = fopen ("ranking.txt","a+");
		if(!$fp)
		{
			echo "can't append to ranking.txt ! (Please check file permission.)";
			exit;
		}

		if( !flock($fp, LOCK_EX) ) exit();
		list( $r_score, $r_name ) = GetRanking($fp);
	}

	if( $_SESSION['rank_in'] )
	{
		$name = isset($_POST['user_name']) ? trim($_POST['user_name']) : "";
		$name = htmlspecialchars($name, ENT_QUOTES);
		$name = mb_convert_kana ( $name, "KV"); //半角仮名を全角に
		if($name=="" || $name=="null") $name = NO_NAME;

		list( $r_score, $r_name ) = InsertScore($r_score, $r_name, $name, $_SESSION['score'] );
		ftruncate($fp, 0);
		fseek($fp, 0, SEEK_SET);

		for($i=0; $i< count($r_score); $i++)
		{
			fputs($fp, $r_score[$i]. "\t" . $r_name[$i] . "\n" );
		}

		fclose($fp);
	}

	$_SESSION['rank_in'] = 0;

	require_once "view-game-end.php";

	session_destroy();
	exit();
}


if( $mode == "ajax-get-img" )
{
	$x = $_SESSION['now_x'];
	$y = $_SESSION['now_y'];
	$direction = $_SESSION['direction'];
	$message = "";

	$cmd = empty($_POST["cmd"]) ? "" : $_POST["cmd"];
	$unique_key = empty($_POST["unique_key"]) ? "" : $_POST["unique_key"];


	if( $unique_key == "" || $_SESSION["unique_key"] != $unique_key )
	{
		header("Content-Type: text/javascript; charset=utf-8");
		echo json_encode( array( "error" => 1) );
		exit();
	}

	//移動
	switch($cmd)
	{
		case "up":
			switch ($_SESSION['direction'])
			{
				case 0:
					$tmp_x = $x;
					$tmp_y = $y - 1;
					break;
				case 1:
					$tmp_x = $x + 1;
					$tmp_y = $y;
					break;
				case 2:
					$tmp_x = $x;
					$tmp_y = $y + 1;
					break;
				case 3:
					$tmp_x = $x - 1;
					$tmp_y = $y;
					break;
			}

			$gmd = GetMazeData($tmp_x, $tmp_y);

			if ($gmd == 0 || $gmd == MAZE_RAMEN || $gmd == MAZE_GOAL)
			{
				$_SESSION['now_x'] = $tmp_x;
				$_SESSION['now_y'] = $tmp_y;
				$_SESSION['life'] -= 2;
				$_SESSION['score'] += 1;

				//ラーメン発見
				if ($gmd == MAZE_RAMEN)
				{
					$_SESSION['score'] += 100;
					$_SESSION['life'] += 20;
					$key = $tmp_x . "-" . $tmp_y;
					$_SESSION["got_ramens"][$key] = 1;                //ラーメンゲットフラグon

					if ($_SESSION['life'] > 100) $_SESSION['life'] = 100;

					$_SESSION["FLAG_GET_RAMEN"] = 1;

				}
				elseif ($gmd == MAZE_GOAL)
				{
					//ゴール到着
					$_SESSION['score'] += 1000 + ($_SESSION['life'] * 10);
					if ($_SESSION['life'] <= 0) $_SESSION['life'] = 1;
					$_SESSION['GAME_CLEAR'] = 1;
					$_SESSION['game_status'] = STATUS_GAME_CLEAR;
					$message = MESSAGE_GAME_CLEAR;
				}
			}
			break;

		case "right":
			$_SESSION['direction']++;
			$_SESSION['life'] -= 1;
			if ($_SESSION['direction'] > 3) $_SESSION['direction'] = 0;
			break;

		case "left":
			$_SESSION['direction']--;
			$_SESSION['life'] -= 1;
			if ($_SESSION['direction'] < 0) $_SESSION['direction'] = 3;
			break;

		case "back":
			switch ($_SESSION['direction'])
			{
				case 0:
					$_SESSION['direction'] = 2;
					break;
				case 1:
					$_SESSION['direction'] = 3;
					break;
				case 2:
					$_SESSION['direction'] = 0;
					break;
				case 3:
					$_SESSION['direction'] = 1;
					break;
			}
			$_SESSION['life'] -= 1;
			break;

		default:
		case "init":
			break;
	}

	if($_SESSION['life'] <= 0)
	{
		//gamve over
		$_SESSION['life']=0;	
		$_SESSION['GAME_OVER'] = 1;
		$_SESSION['game_status'] = STATUS_GAME_OVER;
		$message = MESSAGE_GAME_OVER;
	}

	//get png raw data
	ob_start();
	drawImageMain( $_SESSION['now_x'], $_SESSION['now_y'], $_SESSION['direction'] );
	$image_binary = ob_get_clean();

	if ( ( $_SESSION['game_status']==STATUS_GAME_OVER || $_SESSION['game_status']==STATUS_GAME_CLEAR )
		&& check_score_rank_in($_SESSION['score'])
	)
	{
		$flg_rank_in = 1;
		$message .= "\n" . MESSAGE_INPUT_YOUR_NAME;
	}

	$_SESSION['rank_in'] = $flg_rank_in;
	
	$json_data = array(
		"game_status" => $_SESSION['game_status'],
		"error" => 0,
		"flg_rank_in" => $flg_rank_in,
		"message" => $message,
		"image" => "data:image/png;base64," . base64_encode($image_binary)
	);

	header("Content-Type: text/javascript; charset=utf-8");
	echo json_encode($json_data);
	exit();
}


//
// Main Page
//
require_once "view-main.php";
