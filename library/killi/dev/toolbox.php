<?php

    //-------------------------------------------------------------------------
    //
    // Retourne le contenu d'un tableau associatif format� pour affichage HTML
    //
    //-------------------------------------------------------------------------
    function display_array($array,$title='')
    {
        //---On vide le contenu du buffer actuel
        if (!isset($array))
        {
            return "array non definie !";
        }

        ob_start();

        $bt = array_slice(debug_backtrace(),0,1);
        $last=array_slice($bt,-1,1);

        //print_r($last[0]);

        //echo "\n[".$last[0]['file'].":".$last[0]['line']."]\n\n";
        print_r($array);
        $data = ob_get_contents();
        ob_end_clean();

        //$string = '<div z-index=1000000 style=\'word-wrap: normal; width: 100px; border: solid 2px #ffffff; background-color: #DDDDFF; text-align: left;\'><div style="font-size: 1.5em; background-color: #BBBBFF; border: dotted 1px #ffffff; text-align: center;">'.$title."&nbsp;&nbsp;&nbsp;&nbsp;[".$last[0]['file'].":".$last[0]['line']."]".'</div>'.str_replace("    ","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",nl2br(htmlentities(utf8_decode($data)))).'</div>';
        //$string = '<table><tr><td style=\'word-wrap: normal; width: 100px; border: solid 2px #ffffff; background-color: #DDDDFF; text-align: left;\'></td></tr><tr><td style="font-size: 1.5em; background-color: #BBBBFF; border: dotted 1px #ffffff; text-align: center;">'.$title."&nbsp;&nbsp;&nbsp;&nbsp;[".$last[0]['file'].":".$last[0]['line']."]".'</td></tr>'.str_replace("    ","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",nl2br(htmlentities(utf8_decode($data)))).'</div>';
        $string='<br /><table style="border: solid 2px #000066;"><tr><th style=\'font-size: 0.8em; border: solid 2px #ffffff; background-color: #DDDDFF; text-align: left;\'>'.$title.'&nbsp;&nbsp;&nbsp;&nbsp;['.$last[0]['file'].':'.$last[0]['line'].']</th></tr> <tr><td style="font-size: 0.6em; background-color: #BBBBFF; border: dotted 1px #ffffff; text-align: left;">'.str_replace("    ","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",nl2br(htmlentities(utf8_decode($data)))).'</td></tr></table>';
		Debug::printInException($string);
        return $string;
    }
    //-------------------------------------------------------------------------
    function mail_utf8($to, $subject = '(No subject)', $message = '', $header = '')
    {
  		$header_ = 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/plain; charset=UTF-8' . "\r\n";
  		mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $message, $header_ . $header);
	}
	//-------------------------------------------------------------------------
