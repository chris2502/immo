<?php

/**
 *  @class TemplateLoader
 *  @Revision $Revision: 3708 $
 *
 */

class TemplateLoader
{
	protected static $_templates_cache = array();

	protected static function build($xml, &$root)
	{
		$text = '';
		while($xml->read())
		{
			switch ($xml->nodeType)
			{
				case XMLReader::END_ELEMENT:
					return;
				case XMLReader::ELEMENT:
					$raw = explode(':', $xml->name);

					$childs = array();
					if(!$xml->isEmptyElement)
					{
						self::build($xml, $childs);
					}

					if(isset($raw[1]))
					{
						$node_name = $raw[1];
						$node = array('tag' => $raw[0], 'markup' => $node_name, 'value' => $childs);
					}
					else
					{
						$node = array('tag' => 'html', 'markup' => $raw[0], 'value' => $childs);
					}
					$node['attributes'] = array();
					if($xml->hasAttributes)
					{
						while($xml->moveToNextAttribute())
						{
							$node['attributes'][$xml->name] = $xml->value;
						}
					}
					$root[] = $node;
					break;
				case XMLReader::TEXT:
				case XMLReader::CDATA:
					$node = array('tag' => 'html', 'attributes' => array(), 'markup' => 'text', 'value' => trim($xml->value));
					$root[] = $node;
			}
		}

		return;
	}

	public static function load($template_file)
	{
		if(!file_exists($template_file))
		{
			throw new NoTemplateException($template_file . ' doesnt exists');
		}

		if(!is_file($template_file))
		{
			throw new NoTemplateException($template_file . ' is not a file');
		}

		if(isset(self::$_templates_cache[$template_file]))
		{
			return self::$_templates_cache[$template_file];
		}

		$xml = new XMLReader();
		$xml->open($template_file);
		$root = array();
		self::build($xml, $root);
		$xml->close();

		self::$_templates_cache[$template_file] = $root;

		//self::showRec($root, 0);
		return self::$_templates_cache[$template_file];
	}

	/*
	 *
	 * DEBUG TRACE && DEV TOOLS
	 *
	 *
	 */

	// @codeCoverageIgnoreStart
	protected static function showRec($structure, $depth)
	{
		if(is_array($structure))
		{
			foreach($structure AS $element)
			{
				for($i = 0; $i < $depth; $i++)
				{
					echo "\t";
				}
				echo $element['tag'], ':', $element['markup'], ' -> ', json_encode($element['attributes']), PHP_EOL;
				self::showRec($element['value'], $depth+1);
			}
		}
		else
		{
			echo $structure;
		}

	}

	public static function show($template_file)
	{
		$structure = self::load($template_file);
		self::showRec($structure);
	}
	// @codeCoverageIgnoreEnd
}
