<?php

/**
 *
 *  @class WikiCode
 *  @Revision $Revision: 4198 $
 *
 */

class WikiCode
{
	protected $_text_html;
	protected $_text_wiki;

	//.....................................................................
	/**
	 * Transforme un texte wiki(redmine) en code html
	 * @param string $file
	 * @throws Exception
	 */
	public function __construct($text_wiki)
	{
		$this->_text_wiki = $text_wiki;
		return TRUE;
	}

	public function getHtml(&$text_html)
	{
		$this->wikiToHtml();
		$text_html = $this->_text_html;
		return TRUE;
	}

	//-------------------------------------------------------------------------------
	/**
	 * Retourne un tableau avec des caracteres sensibles et leurs equivalents hexa.
	 *
	 */
	public function getSpecial()
	{
		$a_special = array ('-'=>'%2D',
			'~'=>'%7E',
			'_'=>'%5F',
			'/'=>'%2F'
		);
		return $a_special;
	}

	protected function _encodeSpecial($str)
	{
		$a_special = $this->getSpecial();
		$str = str_replace(array_keys($a_special), array_values($a_special), $str);
		return $str;
	}

	protected function _decodeSpecial($str)
	{
		$a_special = $this->getSpecial();
		$str = str_replace(array_values($a_special), array_keys($a_special), $str);
		return $str;
	}
	//-------------------------------------------------------------------------------


	/**
	 * Recupere le code wiki avec l'html.
	 * On autorise le code html que dans les balise <code> et <pre>
	 * Puis transforme le code wiki en code html
	 *
	 */
	protected function wikiToHtml()
	{
		$this->_text_html = $this->_text_wiki;
		$this->_formatCode();

		//Ensuite striptag
		$this->_text_html = strip_tags($this->_text_html);

		$this->_formatLinks();

		$this->_formatText();

		//Cas des listes ul et ol
		$this->_text_html = preg_replace_callback("#(^\\*.*$\n?)+#m", array($this,"_format_ul_list"), $this->_text_html);
		$this->_text_html = preg_replace_callback("#(^\\#.*$\n?)+#m", array($this,"_format_ol_list"), $this->_text_html);

		$this->_formatImages();

		$this->_text_html = $this->_decodeSpecial($this->_text_html);
		$this->_text_html = nl2br($this->_text_html);

		return TRUE;
	}

	/**
	 * Transforme <code>code</code> <pre>code</pre> en @code@
	 * Ajoute egalement un htmlentities sur le code.
	 *
	 **/
	protected function _formatCode()
	{
		$this->_text_html = preg_replace_callback('/<pre.*?>(.*?)<\/pre>/imsu', function ($matches) {
			return str_replace($matches[1],htmlentities($matches[1]),$matches[0]);
		}, $this->_text_html);

		$this->_text_html = preg_replace_callback('/<code.*?>(.*?)<\/code>/imsu', function ($matches) {
			return str_replace($matches[1],htmlentities($matches[1]),$matches[0]);
		}, $this->_text_html);

		$this->_text_html = str_replace(array('<pre>', '</pre>', '<code>','</code>'),'@',$this->_text_html);
		return TRUE;
	}

	protected function _formatLinks()
	{
		//toto@toto.fr
		$this->_text_html = preg_replace_callback('/[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})/',function ($matches) {
			return '<a href="mailto:'.$this->_encodeSpecial($matches[0]).'">'.$this->_encodeSpecial($matches[0]).'</a>';
		}
		,$this->_text_html
		);

		//"Foo":http://www.foo.fr ou "Foo(title)":http://www.foo.fr
		$this->_text_html = preg_replace_callback('/"([^"]*?)(?:\((.*)\))?":(\b(?:(?:https?):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|])/i',function ($matches) {
			$b_protocol = (stripos($matches[3], '://'));
			$link = $matches[3];
			if ($b_protocol === FALSE)
			{
				$link = 'http://'.$this->_encodeSpecial($link);
			}
			return '<a href="'.$this->_encodeSpecial($link).'" title="'.$this->_encodeSpecial($matches[2]).'">'.$this->_encodeSpecial($matches[1]).'</a>';
		}
		,$this->_text_html
		);

		//http://www.goo.fr
		$this->_text_html = preg_replace_callback('/(^|\s)(\b(?:(?:https?):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|])/i',function ($matches) {
			$b_protocol = (stripos($matches[2], '://'));
			$link = $matches[2];
			if ($b_protocol === FALSE)
			{
				$link = 'http://'.$this->_encodeSpecial($link);
			}
			return '<a href="'.$link.'">'.$this->_encodeSpecial($matches[2]).'</a>';
		}
		,$this->_text_html
		);
		return TRUE;
	}

	/**
	 * Transforme !url_image! en <img src="url_image" />
	 *
	 **/
	protected function _formatImages()
	{
		$this->_formatWikiImages();

		$this->_text_html = preg_replace_callback('/!(\b(?:(?:https?):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|](\.png|\.jpg|\.jpeg|\.gif))!/i',function ($matches) {

			return '<img src="'.$this->_encodeSpecial($matches[1]).'" alt="image !"/>';
		}
		,$this->_text_html
		);
		return TRUE;
	}

	protected function _formatText()
	{
		$patterns = array(
			'@'=>'code',
			'_'=>'em',
			'\*'=>'strong',
			'\+'=>'u',
			'\?\?'=>'cite',
			'-'=>'del',
			'\^'=>'sup',
			'~'=>'sub',
		);

		foreach ($patterns as $sign => $balise)
		{
			$pattern = '/'.$sign.'(.+?)'.$sign.'/';
			$this->_text_html = preg_replace($pattern, '<'.$balise.'>$1</'.$balise.'>', $this->_text_html, -1);
		}

		$patterns_title = array(
			'h1. '=>'h1',
			'h2. '=>'h2',
			'h3. '=>'h3',
			'h4. '=>'h4',
			'h5. '=>'h5',
			'h6. '=>'h6',
			'p. '=>'p',
			'bq. '=>'blockquote'
		);

		foreach ($patterns_title as $sign => $balise)
		{
			$pattern = '/(^|\s)'.$sign.'(.*)/';
			$this->_text_html = preg_replace($pattern, '<'.$balise.'>$2</'.$balise.'>', $this->_text_html, -1);
		}
		return TRUE;
	}

	protected function _format_ul_list($m)
	{
		static $lvl = 1;

		$txt = $m[0];

		$re = "(^\\*{". ($lvl + 1) ."}+.*$\n?)+";
		if (preg_match("#{$re}#m", $txt))
		{
			$lvl++;
			$txt = preg_replace_callback("#{$re}#m", array($this,"_format_ul_list"), $txt);
			$lvl--;
		}

		return  "<ul>". preg_replace( "#^\\*{". $lvl. "}(.*?(?:(\n)<ul>.*</ul>)?)(\n|$)#ms",
			"<li>\\1\\2</li>",
			$txt ).
			"</ul>";
	}

	protected function _format_ol_list($m)
	{
		static $lvl = 1;

		$txt = $m[0];

		$re = "(^\\#{". ($lvl + 1) ."}+.*$\n?)+";
		if (preg_match("#{$re}#m", $txt))
		{
			$lvl++;
			$txt = preg_replace_callback("#{$re}#m", array($this,"_format_ol_list"), $txt);
			$lvl--;
		}

		return  "<ol>". preg_replace( "#^\\#{". $lvl. "}(.*?(?:(\n)<ol>.*</ol>)?)(\n|$)#ms",
			"<li>\\1\\2</li>",
			$txt ).
			"</ol>";
	}

	private function _formatWikiImages()
	{
		$this->_text_html = preg_replace_callback('/!(wikimage([0-9]+)_([0-9]+).([a-z]+))!/',function ($matches) {
			ORM::getControllerInstance('WikiUploadDocument')->getImage($matches[1], $base64);
				return '<img src="data:image/'.$matches[4].';base64,'.$base64.'" />';
			} ,$this->_text_html
		);

		return TRUE;
	}

}
