<?

function smarty_function_paging($params, &$smarty) {
	if (!$smarty->_display_called) 
		return;
	$sourcename=$params[source];
	$itemsperpage=is_numeric($params[itemsperpage]) ? $params[itemsperpage] : ( is_numeric($_SESSION["itemsperpage_$sourcename"]) ? $_SESSION["itemsperpage_$sourcename"] : 1000);
	$_SESSION["itemsperpage_$sourcename"]=$itemsperpage;

	$type=is_string($params[type]) ? $params[type] : "simple";
	$strip_data=is_numeric($params[strip_data]) ? $params[strip_data] : 1;
	//$source=is_array($smarty->_tpl_vars[$sourcename]) ? $smarty->_tpl_vars[$sourcename] : array();
	$source=is_array($smarty->getTemplateVars($sourcename)) ? $smarty->getTemplateVars($sourcename) : array();
	$total_items=count($source);
	$firstitemname="firstitem_$sourcename";
	$first_item=is_numeric($_POST[$firstitemname]) && $_POST[$firstitemname]<=$total_items ? $_POST[$firstitemname] : 1;

	if  ($strip_data==1) {
		$source=array_slice( $source, $first_item-1, $itemsperpage);
		$smarty->assign($sourcename,$source);
	}

	$href=preg_replace("/[?&]$firstitemname=\S+/","",$_SERVER[REQUEST_URI]);
	$href.=(strpos($href,'?')>0) ? "&" : "?";


	if (!empty($params[itemsoptions])) {
		$a=explode(',',$params[itemsoptions]);
		$iopts.="<form method=get action=\"$href\">\n";
		$iopts.=$params['itemsoptions_message'];
		$iopts.="<select onChange='submit();' name=\"itemoption_$sourcename\">\n";
		if (is_array($a)) foreach ($a as $i) {
			$iopts.="<option value=\"$i\" ".($i==$itemsperpage?"selected":"").">$i</option>\n";
		}
		$iopts.="</select>";
		$b=parse_url($href);
		parse_str($b['query'],$c);
		if (is_array($c)) foreach($c as $k=>$v) {
			if ($k!="itemoption_$sourcename") $iopts.="<input type=hidden name=\"$k\" value=\"$v\">\n" ;
		}
		$iopts.="</form>\n";
	}

	switch ($type)  {
		case "pagetens" :
			for ($i=1;$i<=$total_items;$i=$i+$itemsperpage) {
				$j=$i+$itemsperpage-1<$total_items? $i+$itemsperpage-1 : $total_items;
				$a=ceil($i/$itemsperpage);
				$nh="[$a] ";
				$h="[<a class=atable href=\"$href$firstitemname=$i\">$a</a>] ";
				if ($i>=$first_item+$itemsperpage || $j<$first_item ) {
					if ( ($a%10==0 && abs($i-$first_item)<50*$itemsperpage) || $a%100==0 || $a==1 || $j==$total_items || abs($i-$first_item)<3*$itemsperpage) {
						$ret.="[<a class=atable href=\"$href$firstitemname=$i\">$a</a>] ";
					}
				} else {
					$ret.="<b>[$a]</b> ";
				}
			}
			break;
		case "pagenums" :
			for ($i=1;$i<=$total_items;$i=$i+$itemsperpage) {
				$j=$i+$itemsperpage-1<$total_items? $i+$itemsperpage-1 : $total_items;
				$a=ceil($i/$itemsperpage);
				if ($i>=$first_item+$itemsperpage || $j<$first_item) {
					$ret.="[<a class=atable href=\"$href$firstitemname=$i\">$a</a>] ";
				} else {
					$ret.="<b>[$a]</b> ";
				}
			}
			break;
		case "simple" :
			for ($i=1;$i<=$total_items;$i=$i+$itemsperpage) {
				$j=$i+$itemsperpage-1<$total_items? $i+$itemsperpage-1 : $total_items;
				if ($i>=$first_item+$itemsperpage || $j<$first_item) {
					$ret.="<a class=atable href=\"$href$firstitemname=$i\">$i-$j</a> | ";
				} else {
					$ret.="[$i-$j] ";
				}
			}
			break;
		break;
	}


	$ret=chop($ret);
	$smarty->assign("pages_$sourcename",$ret);
	$smarty->assign("itemoption_$sourcename",$iopts);

//	return $ret;
}
	
?>
