<?php

declare(strict_types=1);

namespace MatiCore\Email;


use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;
use Nette\DI\Container;
use Nette\Localization\Translator;
use Nette\Utils\Strings;
use MatiCore\Utils\Http;

/**
 * Class BaseTemplateRenderer
 * @package MatiCore\Email
 */
abstract class BaseTemplateRenderer implements TemplateRenderer
{

	/**
	 * @var Container
	 */
	protected $container;

	/**
	 * @var LinkGenerator
	 */
	private $linkGenerator;

	/**
	 * @var Translator
	 */
	private $translator;

	/**
	 * @param Container $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
		$this->linkGenerator = $container->getByType(LinkGenerator::class);
		$this->translator = $container->getByType(Translator::class);
	}

	/**
	 * @return mixed[]
	 */
	public function getBasicParameters(): array
	{
		return [
			'basePath' => Http::getBaseUrl(),
			'baseUrl' => Http::getBaseUrl(),
			'linkGenerator' => $this->linkGenerator,
			'translator' => $this->translator,
		];
	}

	/**
	 * @param string $template
	 * @return string
	 */
	protected function beforeRenderProcess(string $template): string
	{
		$template = (string) preg_replace_callback(
			'/n:href="(?<link>[^"]*)"/',
			function (array $match): string {
				try {
					$route = Route::createByPattern($match['link']);
					$parameters = $route->getParams();
					if (isset($parameters['locale']) === false || $parameters['locale'] === null) {
						$parameters['locale'] = TranslatorManager::getLocale();
					}

					$renderParameters = [];

					foreach ($parameters as $parameterName => $parameterValue) {
						$escapeParamValue = static function (BaseTemplateRenderer $renderer, $value) {
							if (Strings::startsWith($value, '$')) {
								return $value;
							}

							if (is_string($value)) {
								return '"' . $renderer->saveHtmlSpecialChars($value) . '"';
							}

							return $value;
						};

						$renderParameters[] = '"' . $parameterName . '" => ' . $escapeParamValue($this, $parameterValue);
					}

					$route = ($route->getModule() ?? 'Front')
						. ':' . $route->getPresenterName(true)
						. ':' . $route->getActionName();

					return 'href="{$linkGenerator->link('
						. '"' . htmlspecialchars(str_replace('Front:Front:', 'Front:', $route), ENT_QUOTES) . '", '
						. '[' . implode(', ', $renderParameters) . '])}"';
				} catch (InvalidLinkException|InvalidRouteException $e) {
					return 'href="' . Http::getBaseUrl() . '"';
				}
			},
			$template
		);

		$template = (string) preg_replace_callback(
			'/(\{_\})(?<haystack>.+?)(\{\/_\})/', // {_}hello{/_}
			function (array $match): string {
				$haystack = $match['haystack'];

				return '{$translator->translate('
					. (Strings::startsWith($haystack, '$') ? $haystack : '"' . $this->saveHtmlSpecialChars($haystack) . '"')
					. ')}';
			},
			$template
		);

		$template = (string) preg_replace_callback(
			'/\{_(?:(?<haystack>.*?))\}/', // {_hello}, {_'hello'}, {_"hello"}
			function (array $match): string {
				$haystack = trim($match['haystack'], '\'"');

				return '{$translator->translate('
					. (Strings::startsWith($haystack, '$') ? $haystack : '"' . $this->saveHtmlSpecialChars($haystack) . '"')
					. ')}';
			},
			$template
		);

		return $template;
	}

	/**
	 * Escapes string for use inside HTML comments.
	 *
	 * @param  string $s plain text
	 * @return string HTML
	 */
	protected function escapeHtmlComment(string $s): string
	{
		if ($s && (strpos($s, '-') === 0 || strpos($s, '>') === 0 || strpos($s, '!') === 0)) {
			$s = ' ' . $s;
		}

		$s = str_replace('--', '- - ', $s);
		if (substr($s, -1) === '-') {
			$s .= ' ';
		}

		return $s;
	}

	/**
	 * @param string $haystack
	 * @return string
	 */
	private function saveHtmlSpecialChars(string $haystack): string
	{
		$haystack = htmlspecialchars($haystack, ENT_QUOTES);
		$haystack = (string) str_replace('-&gt;', '->', $haystack);

		return $haystack;
	}

}