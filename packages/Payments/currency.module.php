<?php 

class currency_converter {
    static function convert($sum,$curr1,$curr2) {
        $rates=array(
                   'RUR'=>1,
                   'RUB'=>1,
                   'USD'=>30,
                   'EUR'=>40,
               );
        $curr1=strtoupper($curr1);
        $curr2=strtoupper($curr2);
        $r1=isset($rates[$curr1]) ? $rates[$curr1] : 1;
        $r2=isset($rates[$curr2]) ? $rates[$curr2] : 1;
        return $sum*$r1/$r2;
    }
    static function convert_google($sum,$curr) {
        if (is_numeric($sum)) $sum.=' '.$curr;
        $url=sprintf("http://www.google.com/ig/calculator?q=%s=?%s",urlencode($sum),$curr);
        $res=file_get_contents($url);
        $res=str_replace(urldecode('%A0'),'',$res);
        $res=preg_replace('/([a-z]+):\s*/','"\1":',$res);
        $j=json_decode($res,1);
        return ($j && isset($j['rhs']) && floatval($j['rhs'])) ? sprintf("%.02f",floatval($j['rhs'])) : sprintf("%.02f",$sum) ;
    }
}
