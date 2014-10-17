<?php

function gs_controller_paging($firstitemname, $type,$total_items,$itemsperpage,$first_item=0) {
	if($first_item>$total_items) $first_item=0;
	if (!$itemsperpage || $itemsperpage==0) $itemsperpage=1;
	

	$href=preg_replace("/[?&]$firstitemname=\S+/","",$_SERVER['REQUEST_URI']);
	$href.=(strpos($href,'?')>0) ? "&" : "?";

	$ret=array();

	switch ($type)  {
		case "pagenums" :
			$cp=floor($first_item/$itemsperpage)+1;
			$tp=ceil($total_items/$itemsperpage);
			for ($i=1; $i<=$tp; $i++) {
				$fi=($i-1)*$itemsperpage;
				$ret[$i]="<a href=\"$href$firstitemname=$fi\">$i</a>";
			}
			$ret[$cp]="<a id=\"curr\" href=\"$href$firstitemname=$first_item\">$cp</a>";
			$ret=implode(" | ",$ret);
			return $ret;
			break;
		case "list" :
			$cp=floor($first_item/$itemsperpage)+1;
			$tp=ceil($total_items/$itemsperpage);
			for ($i=1; $i<=$tp; $i++) {
				$fi=($i-1)*$itemsperpage;
				$ret[$i]="<li><a href=\"$href$firstitemname=$fi\">$i</a></li>";
			}
			$ret[$cp]="<li><a id=\"curr\" href=\"$href$firstitemname=$first_item\">$cp</a></li>";
			$ret='<ul class="pager">'.implode("",$ret).'</ul>';
			return $ret;
			break;
		case "itemsnav" :
			$skip=2;
			$cp=max(1,floor($first_item/$itemsperpage)+1);
			$tp=ceil($total_items/$itemsperpage);
			$fsp=max(1,$cp-$skip);
			$lsp=min($tp,$fsp+$skip*2+1);

			for ($i=1; $i<=$tp; $i++) {
				$fi=($i-1)*$itemsperpage;
				$ci=($i-1)*$itemsperpage+1;
				$ni=($i)*$itemsperpage;

				if ($i==$cp) {
					$ret[$i]="<span>$ci-$ni</span>";
				} else if ($i<=2  or ($i==3 &&  $fsp==3) OR $i==$tp or ($i>$fsp && $i<$lsp) ) {
					$ret[$i]="<a href=\"$href$firstitemname=$fi\">$ci-$ni</a>";
				} else if ($i==$fsp || $i==$lsp) {
					$ret[$i]='...';
				}
			}
			if ($cp>1 && $tp>1) 
				array_unshift($ret,"&#9668;<a href=\"$href$firstitemname=".($cp-2)*$itemsperpage."\" class=\"prev\">Prev</a>");
			else 
				array_unshift($ret,"&#9668;<span class=\"prev\">Prev</a>");

			if ($cp<$tp && $tp>1) 
				array_push($ret,"<a href=\"$href$firstitemname=".($cp)*$itemsperpage."\" class=\"next\">Next</a>&#9658;");
			else 
				array_push($ret,"<span class=\"next\">Next</a>&#9658;");
			$ret=implode(" | ",$ret);
			return "<div class=\"pagenav\">$ret</div>";
			break;
	}

}
	
?>
