<?php

declare(strict_types=1);

namespace MatiCore\Email;


/**
 * Class Html2Text
 * @package MatiCore\Email
 */
class Html2Text
{

	/**
	 *  List of preg* regular expression patterns to search for,
	 *  used in conjunction with $replace.
	 *
	 * @var string[] $search
	 */
	private static $search = [
		"/\r/",                                  // Non-legal carriage return
		"/[\n\t]+/",                             // Newlines and tabs
		'/[ ]{2,}/',                             // Runs of spaces, pre-handling
		'/<title[^>]*>.*?<\/title>/i',           // <script>s -- which strip_tags supposedly has problems with
		'/<script[^>]*>.*?<\/script>/i',         // <script>s -- which strip_tags supposedly has problems with
		'/<style[^>]*>.*?<\/style>/i',           // <style>s -- which strip_tags supposedly has problems with
		//'/<!-- .* -->/',                       // Comments -- which strip_tags might have problem a with
		'/<p[^>]*>/i',                           // <P>
		'/<br[^>]*>/i',                          // <br>
		'/<i[^>]*>(.*?)<\/i>/i',                 // <i>
		'/<em[^>]*>(.*?)<\/em>/i',               // <em>
		'/(<ul[^>]*>|<\/ul>)/i',                 // <ul> and </ul>
		'/(<ol[^>]*>|<\/ol>)/i',                 // <ol> and </ol>
		'/<li[^>]*>(.*?)<\/li>/i',               // <li> and </li>
		'/<li[^>]*>/i',                          // <li>
		'/<hr[^>]*>/i',                          // <hr>
		'/(<table[^>]*>|<\/table>)/i',           // <table> and </table>
		'/(<tr[^>]*>|<\/tr>)/i',                 // <tr> and </tr>
		'/<td[^>]*>(.*?)<\/td>/i',               // <td> and </td>
		'/&(nbsp|#160);/i',                      // Non-breaking space
		'/&(quot|rdquo|ldquo|#8220|#8221|#147|#148);/i', // Double quotes
		'/&(apos|rsquo|lsquo|#8216|#8217);/i',   // Single quotes
		'/&gt;/i',                               // Greater-than
		'/&lt;/i',                               // Less-than
		'/&(amp|#38);/i',                        // Ampersand
		'/&(copy|#169);/i',                      // Copyright
		'/&(trade|#8482|#153);/i',               // Trademark
		'/&(reg|#174);/i',                       // Registered
		'/&(mdash|#151|#8212);/i',               // mdash
		'/&(ndash|minus|#8211|#8722);/i',        // ndash
		'/&(bull|#149|#8226);/i',                // Bullet
		'/&(pound|#163);/i',                     // Pound sign
		'/&(euro|#8364);/i',                     // Euro sign
		'/&[^&;]+;/i',                           // Unknown/unhandled entities
		'/[ ]{2,}/',                             // Runs of spaces, post-handling
	];

	/**
	 * List of pattern replacements corresponding to patterns searched.
	 *
	 * @var string[] $replace
	 */
	private static $replace = [
		'',                                     // Non-legal carriage return
		' ',                                    // Newlines and tabs
		' ',                                    // Runs of spaces, pre-handling
		'',                                     // Title
		'',                                     // <script>s -- which strip_tags supposedly has problems with
		'',                                     // <style>s -- which strip_tags supposedly has problems with
		//'',                                   // Comments -- which strip_tags might have problem a with
		"\n\n\t",                               // <P>
		"\n",                                   // <br>
		'_\\1_',                                // <i>
		'_\\1_',                                // <em>
		"\n\n",                                 // <ul> and </ul>
		"\n\n",                                 // <ol> and </ol>
		"\t* \\1\n",                            // <li> and </li>
		"\n\t* ",                               // <li>
		"\n-------------------------\n",        // <hr>
		"\n",                                   // <table> and </table>
		"\n",                                   // <tr> and </tr>
		"\\1",                                  // <td> and </td>
		' ',                                    // Non-breaking space
		'"',                                    // Double quotes
		"'",                                    // Single quotes
		'>', '<', '&', '(c)', '(tm)', '(R)', '--', '-', '*', 'ï¿½',
		'EUR',                                  // Euro sign. ï¿½ ?
		'',                                     // Unknown/unhandled entities
		' ',                                    // Runs of spaces, post-handling
	];

	/**
	 * @var string[]
	 */
	private static $searchReplaceCallback = [
		'/<h[123][^>]*>(.*?)<\/h[123]>/i',           // H1 - H3
		'/<h[456][^>]*>(.*?)<\/h[456]>/i',           // H4 - H6
		'/<b[^>]*>([^<]+)<\/b>/i',                   // <b>
		'/<strong[^>]*>(.*?)<\/strong>/i',           // <strong>
		'/<a [^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/i', // <a href="">
		'/<th[^>]*>(.*?)<\/th>/i',                   // <th> and </th>
	];

	/**
	 * Maximum width of the formatted text, in columns.
	 *
	 * Set this value to 0 (or less) to ignore word wrapping
	 * and not constrain text to a fixed-width column.
	 *
	 * @var int
	 */
	private $width = 120;

	/**
	 * Contains a list of HTML tags to allow in the resulting text.
	 *
	 * @var string|null
	 */
	private $allowedTags;

	/**
	 * Contains the base URL that relative links should resolve to.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Contains URL addresses from links to be rendered in plain text.
	 *
	 * @var string
	 */
	private $linkList = '';

	/**
	 * Number of valid links detected in the text, used for plain text
	 * display (rendered similar to footnotes).
	 *
	 * @var int
	 */
	private $linkCounter = 0;

	/**
	 * @param mixed[]|null $configuration
	 */
	public function __construct(array $configuration = null)
	{
		$configuration = $configuration ?? [];

		$this->setBaseUrl(
			isset($configuration['baseUrl'])
				? (string) $configuration['baseUrl']
				: null
		);

		if (isset($configuration['width'])) {
			$this->width = (int) $configuration['width'];
		}

		if (isset($configuration['allowedTags'])) {
			$this->allowedTags = (string) $configuration['allowedTags'];
		}
	}

	/**
	 * @param string $html
	 * @return string
	 */
	public static function convertHTMLToPlainText(string $html): string
	{
		return trim(
			str_replace(
				'^_#%^',
				'$',
				(new self())->process(str_replace('$', '^_#%^', $html))
			)
		);
	}

	/**
	 * Sets a base URL to handle relative links.
	 *
	 * @param string|null $url
	 * @return void
	 */
	public function setBaseUrl(string $url = null): void
	{
		if ($url === null) {
			$this->url = isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : '';
		} else {
			// Strip any trailing slashes for consistency
			// (relative URLs may already start with a slash like "/file.html")
			if (substr($url, -1) === '/') {
				$url = substr($url, 0, -1);
			}

			$this->url = $url;
		}
	}

	/**
	 * Workhorse function that does actual conversion.
	 *
	 * First performs custom tag replacement specified by $search and
	 * $replace arrays. Then strips any remaining HTML tags, reduces whitespace
	 * and newlines to a readable format, and word wraps the text to
	 * $width characters.
	 *
	 * @param string $text
	 * @return string
	 */
	public function process(string $text): string
	{
		// Variables used for building the link list
		$this->linkCounter = 0;
		$this->linkList = '';

		$text = trim(stripslashes($text));

		// Run our defined search-and-replace
		$text = (string) preg_replace(self::$search, self::$replace, $text);

		// Replace non-trivial patterns
		foreach (self::$searchReplaceCallback as $regexp) {
			$text = preg_replace_callback($regexp, function (array $matches): string {
				if ($matches === []) {
					return '?';
				}

				$result = $matches[1] ?? null;

				// H1 - H3
				if (preg_match('/<h[123][^>]*>/i', $matches[0]) === 1) {
					$result = mb_strtoupper("\n\n{$matches[1]}\n\n");
				} // H4 - H6
				elseif (preg_match('/<h[456][^>]*>/i', $matches[0]) === 1) {
					$result = ucwords("\n\n{$matches[1]}\n\n");
				} // B & STRONG
				elseif (preg_match('/<(b|strong)[^>]*>/i', $matches[0]) === 1) {
					$result = mb_strtoupper($matches[1]);
				} // A
				elseif (preg_match('/<a\s+[^>]*>/i', $matches[0]) === 1) {
					$result = $this->buildLinkList($matches[1], $matches[2]);
				} // TH
				elseif (preg_match('/<th[^>]*>/i', $matches[0]) === 1) {
					$result = mb_strtoupper("\t\t{$matches[1]}\n");
				}

				return $result;
			}, $text);
		}

		// Strip any other HTML tags
		$text = strip_tags($text, $this->allowedTags);

		// Bring down number of empty lines to 2 max
		$text = (string) preg_replace("/\n\s+\n/", "\n\n", $text);
		$text = (string) preg_replace("/[\n]{3,}/", "\n\n", $text);

		// Add link list
		if (!empty($this->linkList)) {
			$text .= "\n\nOdkazy:\n-------\n" . $this->linkList;
		}

		$text = (string) preg_replace('/\n[\t ]+/', "\n", trim($text)); // remove line-start whitespaces
		$text = (string) preg_replace('/(\S)[\t ]+$/m', '$1', trim($text)); // remove line-end whitespaces

		if ($this->width > 0) {
			$text = wordwrap($text, $this->width);
		}

		return $text;
	}

	/**
	 *  Helper function called by preg_replace() on link replacement.
	 *
	 *  Maintains an internal list of links to be displayed at the end of the
	 *  text, with numeric indices to the original point in the text they
	 *  appeared. Also makes an effort at identifying and handling absolute
	 *  and relative links.
	 *
	 * @param string $link URL of the link
	 * @param string $display Part of the text to associate number with
	 * @return string
	 */
	private function buildLinkList(string $link, string $display): string
	{
		$link = trim($link);

		if (preg_match('/^(https?|mailto):/i', $link) === 1) {
			$this->linkCounter++;
			$this->linkList .= '[' . $this->linkCounter . "] $link\n";
			$additional = ' [' . $this->linkCounter . ']';
		} elseif (strncmp($link, 'javascript:', 11)) {
			$additional = '';
		} else {
			$this->linkCounter++;
			$this->linkList .= '[' . $this->linkCounter . '] ' . $this->url;
			if (isset($link[0]) && $link[0] !== '/') {
				$this->linkList .= '/';
			}
			$this->linkList .= $link . "\n";
			$additional = ' [' . $this->linkCounter . ']';
		}

		return $display . $additional;
	}

}
