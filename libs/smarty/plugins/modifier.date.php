<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

function smarty_modifier_date($unixdate,$format)
{
	$d=new vpa_tpl_date;
	return $d->parse($unixdate,$format);
}

class vpa_tpl_date {

	var $months;

	function vpa_tpl_date()
	{
		$this->months=array(
			1=>'января',
			2=>'февраля',
			3=>'марта',
			4=>'апреля',
			5=>'мая',
			6=>'июня',
			7=>'июля',
			8=>'августа',
			9=>'сентября',
			10=>'октября',
			11=>'ноября',
			12=>'декабря',
		);
		
		$this->months_nominatif=array(
			1=>'январь',
			2=>'февраль',
			3=>'март',
			4=>'апрель',
			5=>'май',
			6=>'июнь',
			7=>'июль',
			8=>'август',
			9=>'сентябрь',
			10=>'октябрь',
			11=>'ноябрь',
			12=>'декабрь',
		);
	}

	/**
	* формат даты см. в доке по PHP для функции date
	**/
	function parse($date,$format)
	{
		if (strtotime($date)) {
			$date=strtotime($date);
		}
        if (intval($date)<13) {
            $year=date('Y');
            $month=$date;
            $day=date('d');
        } else {
            $year=date('Y',$date);
            $month=date('m',$date);
            $day=date('d',$date);
            $hour=date('H',$date);
            $minutes=date('i',$date);
            $seconds=date('s',$date);

        }
		//preg_match_all("|(\d{4})(\d{2})(\d{2})|is",$date,$out);
		// тут мы реализуем аналогичные методы форматирования дат, как в доке по функции date
		// реализованы далеко не все, а только те, что надо.
		// используется ручное форматирование, чтобы можно было реально называть месяца и дни недели в независомости от настроек локали на сервере.
		// можно выдумывать свои модификаторы, только тогда - документируйте плиз :)
		$date=$format;
		$date=str_replace("%H",$hour,$date);
		$date=str_replace("%i",$minutes,$date);
		$date=str_replace("%s",$seconds,$date);
		$date=str_replace("%Y",$year,$date);
		$date=str_replace("%m",$month,$date);
		$date=str_replace("%FN",$this->months_nominatif[intval($month)],$date);
        $date=str_replace("%F",$this->months[intval($month)],$date);
		$date=str_replace("%d",$day,$date);
		return $date;
	}

}


/* vim: set expandtab: */

?>
