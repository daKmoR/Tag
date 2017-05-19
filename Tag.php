<?php
	if (!defined('BOWER_PATH')) {
		define('BOWER_PATH', __DIR__ . '/..');
	}

	require_once(BOWER_PATH . '/OptionsClass/OptionsHelper.php');
	require_once(BOWER_PATH . '/Url/Url.php');
	require_once(BOWER_PATH . '/Text/Text.php');
	require_once(BOWER_PATH . '/ArrayHelper/ArrayHelper.php');

	/**
	 * Helper for Html Tags
	 *
	 **/
	Class Tag extends OptionsHelper {

		static $openingTagOptionsStack = array();

		/**
		 * Allows to create generic Tags. Is also used if any undefined Tag:: function is called.
		 * With tagAttributes you can define ANY attribute you want. Be it an array or a string. Also there is a shortcut
		 * for 'class'. To create a shortcode tag set content to NULL.
		 *
		 * Examples:
		 *   Tag::h4('something');
		 *   => <h4>something</h4>
		 *
		 *   Tag::span('more');
		 *   => <span>more</span>
		 *
		 *   Tag::p('Author: Me', array('tagAttributes' => array(
		 *     'data-action' => 'highlight',
		 *     'class' => 'more'
		 *   )));
		 *   => <p class="more" data-action="highlight">Author: Me</p>
		 *
		 *   // class shortcut
		 *   Tag::p('Author: Me', array('class' => 'power'));
		 *   => <p class="power">Author: Me</p>
		 *
		 *   // tagAttributes as string
		 *   Tag::p('Author: Me', array('class' => 'power', 'tagAttributes' => 'class="more" data-stuff="true"'));
		 *   => <p class="power more" data-stuff="true">Author: Me</p>
		 *
		 *   // full example
		 *   Tag::p('Author: Me', array(
		 *     'class' => array('power', 'stuff'),
		 *     'tagAttributes' => array(
		 *       'data-stuff' => 'something'
		 *     ),
		 *     'contentWrap' => '<span>L: %s!</span>',
		 *     'tagWrap' => '<div>%</div>'
		 *   ));
		 *   => <div><p class="power-me" data-stuff="something"><span>L: Author: Me!</span></p></div>
		 *
		 *   // shortcode tag
		 *   Tag::genericTag('meta', NULL, array('tagAttributes' => array(
		 *     'charset' => 'utf-8'
		 *   )));
		 *   => <meta charset="utf-8" />
		 *
		 * @param string $tag            Tag to generate
		 * @param string $content        [default to ''] Text/Html inside the tag - set to NULL for closed Tag (<meta .. />)
		 * @param array  $options        ['class', '_defaultClass', 'tagAttributes', 'contentWrap', 'tagWrap']
		 * @return string
		 */
		public static function genericTag($tag, $content = '', $options = array()) {
			$options = ArrayHelper::merge(array(
				'_contentWrap' => '%s',
				'_tagWrap' => '%s',
				'_newLineAfterTag' => true,
				'_tag' => $tag,
				'_content' => $content,
				'_enabled' => true,
				'_copyStyleToDataStyle' => true
			), $options);

			$output = '';
			if ($options['_enabled'] === true) {
				$output = '<' . $options['_tag'] . static::getTagAttributesAsString($options);
				if ($options['_content'] !== NULL) {
					$output .= '>' . sprintf($options['_contentWrap'], $options['_content']) . '</' . $options['_tag'] . '>';
				} else {
					$output .= ' />';
				}
				$output .= $options['_newLineAfterTag'] === true ? PHP_EOL : '';
				$output = sprintf($options['_tagWrap'], $output);
			}

			return static::output($output, $options);
		}

		/**
		 * Example:
		 *   Tag::ulOpen();
		 *   => <ul>
		 *
		 *   Tag::divOpen(array(
		 *     'class' => 'something',
		 *     'data-test' => 'true'
		 *   ));
		 *   => <div class="something" data-test="true">
		 *
		 * @param       $tag
		 * @param array $options
		 * @return mixed
		 */
		public static function openTag($tag, $options = array()) {
			$options = ArrayHelper::merge(array(
				'_tag' => $tag,
				'_enabled' => true,
				'_echo' => true,
				'_copyStyleToDataStyle' => true
			), $options);
			$prepend = '';
			if (Is::notEmptyString($options, '_tagWrap')) {
				$split = explode('|||||', sprintf($options['_tagWrap'], '|||||'));
				if (isset($split) && is_array($split) && count($split) === 2) {
					$prepend = $split[0];
				}
			}
			$contentPrepend = '';
			if (Is::notEmptyString($options, '_contentWrap')) {
				$split = explode('|||||', sprintf($options['_contentWrap'], '|||||'));
				if (isset($split) && is_array($split) && count($split) === 2) {
					$contentPrepend = $split[0];
				}
			}

			array_push(static::$openingTagOptionsStack, $options);
			$output = '';
			if ($options['_enabled'] === true) {
				$output = $prepend .'<' . $options['_tag'] . static::getTagAttributesAsString($options) . '>' . $contentPrepend;
			}
			return static::output($output, $options);
		}

		/**
		 * Example:
		 *   Tag::divClose();
		 *   => </div>
		 *
		 * @param       $tag
		 * @param array $options
		 * @return mixed
		 */
		public static function closeTag($tag, $options = array()) {
			$options = ArrayHelper::merge(array(
				'_tag' => $tag,
				'_enabled' => true
			), $options);

			if (isset(static::$openingTagOptionsStack) && is_array(static::$openingTagOptionsStack) && count(static::$openingTagOptionsStack) > 0) {
				$openingTagOptions = array_pop(static::$openingTagOptionsStack);
			} else {
				die('Closing a "' . $options['_tag'] . '" tag is not allowed here. You have no unclosed opening tag. You need to have matching opening and closing tags.');
			}

			if ($openingTagOptions['_tag'] === $options['_tag']) {
				$options = ArrayHelper::merge($options, $openingTagOptions);
			}
			$output = '';
			if ($options['_enabled'] === true) {
				$append = '';
				if (Is::notEmptyString($options, '_tagWrap')) {
					$split = explode('|||||', sprintf($options['_tagWrap'], '|||||'));
					if (isset($split) && is_array($split) && count($split) === 2) {
						$append = $split[1];
					}
				}
				$contentAppend = '';
				if (Is::notEmptyString($options, '_contentWrap')) {
					$split = explode('|||||', sprintf($options['_contentWrap'], '|||||'));
					if (isset($split) && is_array($split) && count($split) === 2) {
						$contentAppend = $split[1];
					}
				}

				$output = $contentAppend . '</' . $options['_tag'] . '>' . $append;
				if ($openingTagOptions['_tag'] !== $options['_tag']) {
					die('Closing a "' . $options['_tag'] . '" tag is not allowed here. You have an unclosed "' . $openingTagOptions['_tag'] . '" tag. You need to have matching opening and closing tags.');
				}
			}
			return static::output($output, $options);
		}

		/**
		 * This converts an tag option array into tag Attributes as a string
		 *
		 * Example:
		 *   Tag::getTagAttributesAsString(array(
		 *     'class' => array('myClass', 'myClass2'),
		 *     '--my-css-variable' => '10px',
		 *     '--my-stuff' => 'left',
		 *     'dataInfo' => 'Details'
		 *   );
		 *   => class="myClass myClass2" style="--my-css-variable: 10px; --my-stuff: left;" data-info="Details"
		 *
		 * @param $options
		 * @return string
		 */
		public static function getTagAttributesAsString($options = array()) {
			$options = ArrayHelper::merge(array(
				'_defaultClass' => ''
			), $options);

			if (isset($options['class']) && is_string($options['class']) && $options['class'] !== '') {
				$options['class'] = explode(' ', $options['class']);
			}
			if ($options['_defaultClass'] !== '') {
				if(!isset($options['class'])) {
					$options['class'] = array();
				}
				array_unshift($options['class'], $options['_defaultClass']);
			}

			foreach($options as $style => $styleValue) {
				if (strpos($style, '--') === 0) {
					if (!isset($options['style'])) {
						$options['style'] = array();
					}
					$options['style'][$style] = $styleValue;
					// IE11 won't allow raw reading of inline style; unknown properties (like every css variable) will be discarded
					// so to access them we have to copy them somewhere "plain" where no IE11 magic is happening
					if (isset($options['_copyStyleToDataStyle']) && $options['_copyStyleToDataStyle'] === true) {
						if (!isset($options['data-style'])) {
							$options['data-style'] = array();
						}
						$options['data-style'][$style] = $styleValue;
					}
				}
			}

			$tagAttributesString = '';
			foreach($options as $tagAttribute => $tagAttributeValue) {
				if ($tagAttribute !== '' && strpos($tagAttribute, '_') !== 0 && strpos($tagAttribute, '--') !== 0) {
					if (is_array($tagAttributeValue)) {
						$newTagAttributeValue = '';
						foreach($tagAttributeValue as $key => $value) {
							if (is_string($key)) {
								$newTagAttributeValue .= $key . ': ' . $value . ';';
							} else {
								$newTagAttributeValue .= ' ' . $value;
							}
						}
						$tagAttributeValue = $newTagAttributeValue;
					}
					$tagAttributesString .= ' ' . Text::camelCaseToCharSeparated($tagAttribute) . '="' . trim($tagAttributeValue) . '"';
				}
			}
			return $tagAttributesString;
		}

		/**
		 * Renders the most appropriate link by calling the aSubFunctions if needed.
		 *
		 * Examples:
		 *   Tag::a('office@domain.com', null, array('class' => 'power ranger'));
		 *   => <a href="mailto:office@domain.com" class="email power ranger"><span>office@domain.com</span></a>
		 *
		 *   $classArray = array('something', 'to');
		 *   Tag::a('user/login/', 'Details', array('class' => $classArray));
		 *   => <a href="http://domain.com/user/login/" class="something to"><span>Details</span></a>
		 *
		 *   Tag::a($post, 'Details');
		 *   => <a href="http://domain.com/" class="permalink"><span>Details</span></a>
		 *   Tag::a($post->ID, 'Details');
		 *   => <a href="http://domain.com/" class="permalink"><span>Details</span></a>
		 *
		 *   $classArray[] = 'do';
		 *   Tag::a('http://google.com', 'Details', array('class' => $classArray));
		 *   => <a href="http://google.com" target="_blank" class="something to do link--external"><span>Details</span></a>
		 *
		 *   Tag::a('+43 699 123456', null);
		 *   => <a href="tel:0043699123456" class="tel"><span>+43 699 123456</span></a>
		 *
		 * @param string $url
		 * @param string $content
		 * @param array  $_options ['type']
		 * @return string
		 */
		public static function a($url, $content = null, $_options = array()) {
			$options = ArrayHelper::merge(array(
				'_type' => 'auto',
				'_onlyOpenTag' => false,
				'_contentWrap' => '<span>%s</span>',
				'_defaultClass' => 'link',
				'href' => $url
			), $_options);

			if ($options['_type'] === 'auto') {
				$options['_type'] = 'url';
				if (is_string($url)) {
					if (substr($url, 0, 4) === 'http') {
						$options['_type'] = 'url';
					} else if (filter_var($url, FILTER_VALIDATE_EMAIL)) {
						$options['_type'] = 'email';
					} else {
						$possibleCleanedPhoneNumber = str_replace(array(' ', '+', '-', '/', '(', ')', '\\'), '', $url);
						if (is_numeric($possibleCleanedPhoneNumber)) {
							$options['_type'] = 'phone';
						}
					}
				} else if (is_numeric($url) || is_a($url, 'WP_Post')) {
					$options['_type'] = 'permalink';
				}
			}

			if ($options['_type'] !== 'render') {
				$functionName = 'a' . ucfirst($options['_type']);
				return call_user_func(array(new static(), $functionName), $url, $content, $_options);
			}

			if ($options['_onlyOpenTag'] === true) {
				return static::openTag('a', $options);
			}
			return static::genericTag('a', $content, $options);
		}


		/**
		 * Special function for aOpen as it has needs a special option to work.
		 *
		 * @param       $url
		 * @param array $options
		 * @return string
		 */
		public static function aOpen($url, $options = array()) {
			$options['onlyOpenTag'] = true;
			return static::a($url, '', $options);
		}

		/**
		 * Renders a link only if url is provided. Adds class link by default. link--external or link--document if
		 * appropriated. target="_blank" will be added for documents and external links.
		 *
		 * Defaults to:
		 *   Tag::aUrl('http://google.com', 'go', array(
		 *     '_defaultClass' => 'link',
		 *     'externalClass' => 'link--external',
		 *     'documentClass' => 'link--document'
		 *   ));
		 *
		 * Examples:
		 *   Tag::aUrl('http://google.com', 'go');
		 *   => <a class="link link--external" target="_blank">go</a>
		 *
		 *   Tag::aUrl('http://google.com', 'go', array(
		 *     '_defaultClass' => 'link',
		 *     'externalClass' => 'link--external',
		 *     'documentClass' => 'link--document',
		 *     // following options are from Tag::genericTag()
		 *     'class' => 'link--my',
		 *     'tagAttributes' => array(
		 *       'data-stuff' => 'something'
		 *     ),
		 *     'contentWrap' => '<span>L: %s!</span>',
		 *     'tagWrap' => '<div>%</div>'
		 *   ));
		 *   => <div><a class="link--my link link--external" target="_blank" data-stuff="something"><span>L: go!</span></a></div>
		 *
		 * @param string $url     Url to link to
		 * @param string $content [default to ''] Text/Html inside the link; if null copy $url to content
		 * @param array  $options ['_defaultClass', 'externalClass', 'documentClass']
		 * @return string         Html Tag a
		 */
		public static function aUrl($url, $content = '', $options = array()) {
			$url = Url::relativeToFullUrl($url);
			$options = ArrayHelper::merge(array(
				'class' => array(),
				'_externalClass' => 'link--external',
				'_documentClass' => 'link--document',
				'_fileClass' => 'link--file'
			), $options);
			if (Url::isNewWindow($url)) {
				$options['target'] = '_blank';
			}
			$options['class'] = is_string($options['class']) && $options['class'] !== '' ? explode(' ', $options['class']) : $options['class'];
			$content = isset($content) ? $content : $url;

			if (Url::isExternal($url)) {
				$options['class'][] = $options['_externalClass'];
			}
			if (Url::isDocument($url)) {
				$options['class'][] = $options['_documentClass'];
			}
			if (Url::isFile($url)) {
				$options['class'][] = $options['_fileClass'];
			}

			$options['_type'] = 'render';
			return static::a($url, $content, $options);
		}

		/**
		 * Outputs the given href as a
		 *
		 * @param       $plainHref
		 * @param null  $content
		 * @param array $options
		 * @return string
		 */
		public static function aPlain($plainHref, $content = null, $options = array()) {
			$options['_defaultClass'] = isset($options['_defaultClass']) ? $options['_defaultClass'] : 'link link--plain';

			$options['_type'] = 'render';
			return static::a($plainHref, $content, $options);
		}

		/**
		 * Converting given phone number into linkable number string.
		 *
		 * Example:
		 *   Tag::aPhone('+43 123 / 456789');
		 *   => <a class="tel" href="tel:0043123456789"><span>+43 123 / 456789</span></a>
		 *
		 *   Tag::aPhone('123 / 456789', array('defaultCountry' => '001'));
		 *   => <a class="tel" href="tel:001123456789"><span>123 / 456789</span></a>
		 *
		 * @param string $number  human-readable phone number
		 * @param string $content [default to null] if null it will set the number as content
		 * @param array  $options ['defaultCountry'] + options from Tag::a
		 * @return string Html Tag a
		 */
		public static function aPhone($number, $content = null, $options = array()) {
			$options['_defaultClass'] = isset($options['_defaultClass']) ? $options['_defaultClass'] : 'link link--tel tel';
			$defaultCountry = isset($options['_defaultCountry']) ? $options['_defaultCountry'] : '0043';
			$url = 'tel:' . Text::getCleanPhoneNumber($number, $defaultCountry);
			$content = isset($content) ? $content : $number;

			$options['_type'] = 'render';
			return static::a($url, $content, $options);
		}

		/**
		 * Creates a link to a given valid email
		 *
		 * Example:
		 *   Tag::aEmail('office@domain.com');
		 *   => <a class="email" href="mailto:office@domain.com">office@domain.com</a>
		 *
		 *   Tag::aEmail('office');
		 *   => <a class="email">office is not a valid email</a>
		 *
		 *   Tag::aEmail('office(at)domain.com', array(
		 *     'validate' => false,
		 *      // all other options from Tag::a
		 *   ));
		 *   => <a href="mailto:office(at)domain.com">office(at)domain.com</a>
		 *
		 * @param string   $email Email you wish to link to
		 * @param string $content [default to null] if null it will set the email as content
		 * @param array $options  ['_validate'] + options from Tag::a
		 * @return string         Html Tag a
		 */
		public static function aEmail($email, $content = null, $options = array()) {
			$options['_defaultClass'] = isset($options['_defaultClass']) ? $options['_defaultClass'] : 'link link--email email';
			$validate = isset($options['_validate']) ? $options['_validate'] : true;
			$url = 'mailto:' . $email;
			$content = isset($content) ? $content : $email;

			if ($validate && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$content .= ' is not a valid email';
				$url = '';
			}

			$options['_type'] = 'render';
			return static::a($url, $content, $options);
		}

		/**
		 * Creates a link to a given post
		 *
		 * Example:
		 *   Tag::aPermalink($post, 'Details');
		 *   => <a class="permalink" href="http://..."><span>Details</span></a>
		 *
		 *   Tag::aPermalink($post, 'Details' array(
		 *     // all options from Tag::a
		 *   ));
		 *   => <a class="permalink" href="http://..."><span>Details</span></a>
		 *
		 * @param mixed     $post    Post you wish to link to
		 * @param string    $content [default to ''] Text/Html inside the link
		 * @param array     $options all options from Tag::a
		 * @return string            Html Tag a
		 */
		public static function aPermalink($post, $content = '', $options = array()) {
			$options['_defaultClass'] = isset($options['_defaultClass']) ? $options['_defaultClass'] : 'link link--permalink permalink';
			$url = Url::getPermalink($post);

			$options['_type'] = 'render';
			return static::a($url, $content, $options);
		}

		/**
		 * Renders an img Tag
		 *
		 * Examples
		 *   Tag::img('http://domain.com/image.jpg');
		 *   => <img src="http://domain.com/image.jpg" alt="image.jpg" />
		 *
		 * @param string  $url     Image Url
		 * @param array   $options all options from Tag::genericTag
		 * @return string          Html Tag img
		 */
		public static function img($url, $options = array()) {
			$options = ArrayHelper::merge(array(
				'src' => $url,
				'alt' => basename($url)
			), $options);

			$filePath = Url::toPath($url);
			if (is_file($filePath)) {
				$size = getimagesize(Url::toPath($url));
				$options['width'] = $size[0];
				$options['height'] = $size[1];
			}

			return static::genericTag('img', NULL, $options);
		}

		/**
		 * Outputs a valid HTML5 time tag with the appropriate datetime
		 *
		 * Example:
		 *   Tag::timePost($post);
		 *   => <time datetime="2015-06-15T12:36:34">15.06.2015</time>
		 *
		 * @param mixed   $post    Post you with the time to be used from
		 * @param array   $options ['_format', '_datetimeFormat'] + options from Tag::genericTag
		 * @return string          Html Tag time
		 */
		public static function timePost($post, $options = array()) {
			$timeString = get_the_time('d/m/Y', $post);
			$time = date_create_from_format('d/m/Y', $timeString);
			return static::_time($time, $options);
		}

		/**
		 * Outputs a valid HTML5 time tag with the appropriate datetime
		 *
		 * Example:
		 *   Tag::timeString('15/06/2015');
		 *   => <time datetime="2015-06-15T12:36:34">15.06.2015</time>
		 *
		 * @param string  $timeString Time to display as string
		 * @param array   $options    ['_format', '_datetimeFormat'] + options from Tag::genericTag
		 * @return string             Html Tag time
		 */
		public static function timeString($timeString, $options = array()) {
			$time = date_create_from_format('d/m/Y', $timeString);
			return static::_time($time, $options);
		}

		/**
		 * Internal Function to be called by timePost and timeString
		 *
		 * @param DateTime $time     Actual Datetime Object you with to display
		 * @param array    $options  ['_format', '_datetimeFormat'] + options from Tag::genericTag
		 * @return string            Html Tag time
		 */
		public static function _time($time, $options = array()) {
			$datetimeFormat = isset($options['_datetimeFormat']) ? $options['_datetimeFormat'] : 'Y-m-d\TH:i:s';
			$options = ArrayHelper::merge(array(
				'_format' => 'd. M Y',
				'datetime' => $time->format($datetimeFormat)
			), $options);

			$format = $options['_format'];
			if (strpos($format, 'F') !== false) {
				$format = str_replace('F', '!x!', $format);
				$string = $time->format($format);
				$string = str_replace('!x!', Lang::translateMonth($time->format('F')), $string);
			} else if (strpos($format, 'M') !== false) {
				$format = str_replace('M', '!x!', $format);
				$string = $time->format($format);
				$string = str_replace('!x!', Lang::translateMonth($time->format('M')), $string);
			} else {
				$string = $time->format($format);
			}
			return static::genericTag('time', $string, $options);
		}

		/**
		 * This converts a string of tag Attributes to an array
		 *
		 * Example:
		 *   Tag::tagAttributeStringToArray('class="something" target="_blank"');
		 *   => array('class' => 'something', 'target' => '_blank')
		 *
		 * @param $tagAttributeString
		 * @return array
		 */
		public static function tagAttributeStringToArray($tagAttributeString) {
			preg_match_all('#([a-zA-Z\-_]*?)\s*=\s*"([a-zA-Z\-_\.\s]*?)"#', $tagAttributeString, $matches);
			$data = array();
			for ($i = 0; $i < count($matches[0]); $i++) {
				$data[$matches[1][$i]] = $matches[2][$i];
			}
			return $data;
		}

		/**
		 * If you try to call an undefined function it will assume you mean to create a tag instead.
		 * Examples:
		 *   Tag::h4('something', array('contentWrap' => '<span>%s</span>')); => <h4><span>something</span></h4>
		 *   Tag::span('more'); => <span>more</span>
		 *   Tag::spanIf(''); => // no output
		 *
		 * Allows you to easily add a 'If' at the end of every Tag:: function so it will only produce an output if the first
		 * parameter has a valid value.
		 * Examples:
		 *   Tag::aEmailIf($this->get('email'));
		 *   => if $this->get('email') returns '' or false there will be no output
		 *
		 * @param $method
		 * @param $args
		 * @return mixed
		 */
		public static function __callStatic($method, $args) {
			if (substr($method, -2, 2) === 'If') {
				if (isset($args[0]) && $args[0]) {
					$functionName = substr($method, 0, -2);
					if (!method_exists(new static(), $functionName)) {
						array_unshift($args, $functionName);
						$functionName = 'genericTag';
					}
					return call_user_func_array(array(new static(), $functionName), $args);
				}

			} else if (substr($method, -4, 4) === 'Open') {
				array_unshift($args, substr($method, 0, -4));
				return call_user_func_array(array(new static(), 'openTag'), $args);

			} else if (substr($method, -5, 5) === 'Close') {
				array_unshift($args, substr($method, 0, -5));
				return call_user_func_array(array(new static(), 'closeTag'), $args);

			} else {
				array_unshift($args, $method);
				$functionName = 'genericTag';
				return call_user_func_array(array(new static(), $functionName), $args);

			}
			return '';
		}

		/**
		 * Echos the $output variable if in the options the 'echo' is either missing or set to true.
		 * Returns the output in any case.
		 *
		 * Examples:
		 *   return Tag::output($output, $options);
		 *
		 * @param string $output
		 * @param array  $options
		 * @return mixed
		 */
		public static function output($output, $options = array()) {
			$echo = (isset($options['echo']) && !$options['echo']) || (isset($options['_echo']) && !$options['_echo']) ? false : true;
			if ($echo) {
				echo $output;
			}
			return $output;
		}

	}