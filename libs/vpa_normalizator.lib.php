<?php
/**
* Класс для нормализации слов в тексте по словарю (оптимизированный словарь Ispell)
* может использоваться для полнотекстового поиска и проверки орфографии
**/
//setlocale(LC_ALL,'ru_RU.UTF-8');
class VPA_normalizator
{
	var $aff_index;
	var $aff_letters;
	var $letters;
	var $aff_data;
	var $dic_file;
	var $error_words;
	var $dic=array();
	var $stop=array();
	var $verb=array();
	var $big_word;
	var $lang;
	var $log;
	
	function &getInstance($dir='dicts/',$dic='russian')
	{
	  static $instance;
	  if (!isset($instance)) $instance = new VPA_normalizator($dir,$dic);
	  return $instance;
	}
	
	function __construct($dir='dicts/',$dic='russian')
	{
		$this->aff_index=unserialize(file_get_contents($dir.$dic.'_aff.indx'));
		$this->aff_data=unserialize(file_get_contents($dir.$dic.'_aff.data'));
		$this->dic_file=$dir.$dic.'_dic.data';
		$stop_file=$dir.$dic.'_stop.data';
		$verb_file=$dir.$dic.'_verb.data';
		asort ($this->aff_index);
		$v=array();
		$l='';
		foreach ($this->aff_index as $indx => $end)
		{
			$key_start=substr($end,0,1);
			$key=$key_start;
			$this->aff_letters[$key][$indx]=$end;
		}
		$this->dic=array_unique(file($this->dic_file));
		$this->stop=array_unique(file($stop_file));
		$this->verb=array_unique(file($verb_file));
		foreach ($this->dic as $i => $dw) {
			//$this->dic[$i]=iconv('Windows-1251','UTF-8',strtolower(trim($dw)));
			$this->dic[$i]=trim($dw);
		}
		foreach ($this->stop as $i => $dw) {
			$this->stop[$i]=trim($dw);
		}
		foreach ($this->verb as $i => $dw) {
			$this->verb[$i]=trim($dw);
		}
		$this->stop=array_flip($this->stop);
		$this->dic=array_flip($this->dic);
		$this->lang=$dic;
		mlog(get_class($this).": VPA_normalizator init successful",$start,$status);
	}
	
	function trim($var)
	{
		return trim($var);
	}
	
	/**
	*   Получение вариантов нормализовнных слов на базе анализа окончаний
	**/
	function get_variants($word,$log=true)
	{
		$big_word=strtoupper($word);
		$l=strlen($big_word);
		$base_len=($l-3>3) ? 3 : $l-3;
		$ends=strlen($big_word)-$base_len;
		$results=$q=array();
		// мы считаем, что базовая часть слова не менее 3-х букв
		if (isset($this->aff_letters['-']) && ($index=$this->aff_letters['-']))
		{
			$qm=array_keys($index,'-');
		}
		for ($i=0;$i<=$ends;$i++)
		{
			$end=substr($big_word,$base_len+($ends-$i),$i);
			$kk=substr($end,0,1);
			//echo "$end<br>";
			if (isset($this->aff_letters[$kk]) && ($index=$this->aff_letters[$kk]))
			{
				$q=array_merge($q,array_keys($index,$end));
			}
		}
		$words=array(strtolower($word));
		foreach ($q as $i => $id)
		{
			$result=$this->aff_data[$id];
			$base=preg_replace("/".$result['ending']."$/is",$result['del_part'],$big_word);
			$regexp_base="/(".$result['base'].")$/is";
			if (preg_match_all($regexp_base,$base,$out))
			{
				$words[]=strtolower($base);
			}
		}
		foreach ($qm as $i => $id)
		{
			$result=$this->aff_data[$id];
			$base=$big_word.$result['del_part'];
			$regexp_base="/(".$result['base'].")$/is";
			if (preg_match_all($regexp_base,$base,$out))
			{
				$words[]=strtolower($base);
			}
		}

		$words=array_unique($words);
		//var_dump($words);
		($log) && mlog(get_class($this).": get_variants('".$word."') successful",$start,$status);
		return $words;
	}
	

	/**
	* Получение из массива слов (полученных с помощью метода get_variants) слова, присутствующего в словаре
	**/
	function get_word_dic($words,&$ret_word,$log=true)
	{
		$dic=$this->dic;
		foreach ($words as $i => $word)
		{
			if (array_key_exists($word,$dic))
			{
				$ret_word=$word;
				($log) && mlog(get_class($this),"get_word_dic: слово найдено",$start,$status);
				return true;
			}
		}
		$ret_word=$words[0];
		($log) && mlog(get_class($this).": get_word_dic: слово не найдено (ошибка в написании или нет в словаре)",$start,$status);
		return false;
	}
	
	/**
	* Является суммарной функцией get_variants и get_word_dic
	**/
	function get($word,&$ret,$log=true)
	{
		$words=$this->get_variants($word,$log);
		return $this->get_word_dic($words,$ret,$log);
	}
	
	/**
	* Разбирает текстовую строку на массив слов, одновременно удаляя стоп-слова (короче 3-х символов)
	**/
	function parse_text($text)
	{
		$words=preg_split('/[\s\,\.\;\:\-]/',$text);
		foreach ($words as $indx => $word)
		{
			//$word=preg_replace("/[^а-яА-Я]*([а-яА-Я]*)[^а-яА-Я]*/is","$1",$word);
			$words[$indx]=trim($word,'"\'');
			if (strlen($word)<=3) unset($words[$indx]);
		}
		sort($words);
		mlog(get_class($this).": parse_text: текст разобран",$start,true);
		return $words;
	}
	
	/**
	* Проводит первичный анализ текста (подсчитывает частоту одинаковых слов (ненормализованных))
	**/
	function freq_analyze_first($words)
	{
		$unique=array_unique($words);
		$result=array();
		foreach ($unique as $indx => $word)
		{
			$result[$word]=count(array_keys($words,$word));
		}
		mlog(get_class($this).": freq_analyze_first: первичный частотный анализ проведен (".count($words)."=>".count($result).")",$start,true);
		return $result;
	}
	
	/**
	* Проводит вторичный анализ текста (находит нормальные формы слов и производит суммирование частот слов с одной нормальной формой)
	**/
	function freq_analyze_second($result)
	{
		$words=array_keys($result);
		$this->error_words=array();
		
		foreach ($words as $i => $word)
		{
			$freq=$result[$word];
			unset($result[$word]);
			if (!$this->get($word,$nw,false)) {
				$this->error_words[]=$word;
			}
			$result[$nw]=(isset($result[$nw])) ? $result[$nw]+$freq : $freq;
		}
		$res=array();
		foreach ($result as $word => $f) {
			if (!array_key_exists($word,$this->stop)) {
				$res[$word]=$f;
			}
		}
		mlog(get_class($this).": freq_analyze_second: вторичный частотный анализ проведен (".count($words)."=>".count($res).")",$start,true);
		return $res;
	}
	
	function freq_analyze_third($result) {
		$res=array();
		foreach ($result as $word => $f) {
			$s=0;
			foreach ($this->verb as $verb) {
				$regexp=sprintf("|%s$|is",$verb);
				$s+=preg_match($regexp,$word);
			}
			if ($s==0) $res[$word]=$f;
		}
		mlog(get_class($this).": freq_analyze_third: отброшены глаголы (".count($result)."=>".count($res).")",$start,true);
		return $res;
	}

}

?>