<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty plugin
 *
 * Type:     modifier<br>
 * Name:     hl_bbcode<br>
 * Date:     Feb 26, 2003
 * Purpose:  higlight BB code for forums (BB tags [b] [i] [u] [quote] [list] [list=] [img] [url] [code])
 * Input:<br>
 *         - contents = contents to higlight
 * Example:  {$text|hl_url}
 * @version  1.0
 * @author   Pahomov Andrey <andy@dtf.ru>
 * @param string
 * @return string
 */
function smarty_modifier_hl_bbcode($string)
{

	$msgtext = split("\n", $string);
	$tags[0]='b';
	$tags[1]='i';
	$tags[2]='quote';
	$tags[3]='u';
	$tags[4]='list';
	$tags[5]='list=';
	$tags[6]='img';
	$tags[7]='url';
	$tags[8]='url';
	$tags[9]='code';

	$patterns[0]="/\[b\](.*?)\[\/b\]/is";
	$patterns[1]="/\[i\](.*?)\[\/i\]/is";
	$patterns[2]="/\[quote\](.*?)\[\/quote\]/is";
	$patterns[3]="/\[u\](.*?)\[\/u\]/is";
	$patterns[4]="/\[list\](.*?)\[\/list\]/is";
	$patterns[5]="/\[list=\](.*?)\[\/list=\]/is";
	$patterns[6]="/\[img\](.*?)\[\/img\]/is";
	$patterns[7]="/\[url\](.*?)\[\/url\]/is";
	$patterns[8]="/\[url=(.*?)\](.*?)\[\/url\]/is";
	//$patterns[9]="/\[img_gal(?:\s(left|right))?\](\/img\/show\/(\d+)\/\d+\/\d+.jpg)\[\/img_gal\]/is";
	
	$replacements[0]="<span class=\"bbcode_b\">$1</span>";
	$replacements[1]="<span class=\"bbcode_i\">$1</span>";
	$replacements[2]="<div class=\"bbcode_quote\">$1</div>";
	$replacements[3]="<span class=\"bbcode_u\">$1</span>";
	$replacements[4]="<ul class=\"bbcode_ul\">$1</ul>";
	$replacements[5]="<ol class=\"bbcode_ol\">$1</ol>";
	$replacements[6]="<img src=\"$1\" class=\"bbcode_img\">";
	//$replacements[7]="<a href=\"$1\" class=\"bbcode_a\" target=\"_BLANK\">$1</a>";
	$replacements[8]="<a href=\"$1\" class=\"bbcode_a\" target=\"_BLANK\" rel=\"nofollow\">$2</a>";
	//$replacements[7]="$1";
	//$replacements[8]="$2";
	//$replacements[9]="<a href=\"/img/show/$3/400/$3.jpg\" onclick=\"window.open(this.href,'_blank','width=420,height=310,resizable');return false;\"><img src=\"$2\" align=\"$1\" class=\"bbcode_img_gal\"></a>";
	
	foreach ($patterns as $indx => $key)
	{
		$string=parse_BBcode($tags[$indx],$key,$replacements[$indx],$string);
	}
	
	$smiles=array(
		1=>':)',
		8=>':(',
		2=>':D',
		3=>';)',
		4=>':up:',
		5=>':down:',
		6=>':shock:',
		7=>':angry:',
		9=>':sick:',
	);
	foreach ($smiles as $key => $tag) {
		$img=sprintf('<img src="http://beautywm.ru/libs/widgets/wysibb/public_html/theme/default/img/smiles/sm%s.png" class="sm"  unselectable="on">',$key);
		$string=str_replace($tag,$img,$string);
	}
	
	
	
    return $string;
}

function parse_BBcode($tag,$pattern,$replacement,$str)
{
   switch ($tag)
	{
		case 'list':
			$msgtext = split("\n", $str);
			$str='';
			$tag_start=0;
			foreach ($msgtext as $indx => $line)
			{				
				preg_match("/\[\/list\]/", $line) && $tag_start-=1;
				
				$str.=($tag_start && strlen(trim($line))) ? '<li>'.$line."\n" : $line."\n";
				
				preg_match("/\[list\]/", $line) && $tag_start+=1;
			}
		break;
		case 'list=':
			$msgtext = split("\n", $str);
			$str='';
			$tag_start=0;
			foreach ($msgtext as $indx => $line)
			{				
				preg_match("/\[\/list=\]/", $line) && $tag_start-=1;
				
				$str.=($tag_start && strlen(trim($line))) ? '<li>'.$line."\n" : $line."\n";
				
				preg_match("/\[list=\]/", $line) && $tag_start+=1;
			}
		break;
	}

   while(preg_match($pattern,$str))
   {
		$str=preg_replace($pattern,$replacement,$str);
   }
   return $str;
}
/* vim: set expandtab: */

?>
