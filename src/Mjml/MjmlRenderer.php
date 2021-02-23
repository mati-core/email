<?php

declare(strict_types=1);

namespace MatiCore\Email\Mjml;


use Latte\Engine;
use Latte\Loaders\StringLoader;
use MatiCore\Email\BaseTemplateRenderer;
use Nette\DI\Container;
use Nette\Utils\FileSystem;

class MjmlRenderer extends BaseTemplateRenderer
{

	/**
	 * @var string
	 */
	private string $tempDir;

	/**
	 * @var ApiClientAccessor
	 */
	private ApiClientAccessor $client;

	/**
	 * MjmlRenderer constructor.
	 * @param string $tempDir
	 * @param ApiClientAccessor $client
	 * @param Container $container
	 */
	public function __construct(string $tempDir, ApiClientAccessor $client, Container $container)
	{
		parent::__construct($container);
		$this->tempDir = $tempDir . '/cache/_mjmlRenderer';
		$this->client = $client;
	}

	/**
	 * @param string $templatePath
	 * @param mixed[] $parameters
	 * @return string
	 * @throws \Throwable
	 */
	public function render(string $templatePath, array $parameters = []): string
	{
		$template = file_get_contents($templatePath);
		$template = $this->processInclude($template, dirname($templatePath));
		$template = $this->processSpacer($template);

		FileSystem::createDir($this->tempDir);
		$cacheFilePath = $this->tempDir . '/' . md5($template) . '.latte';
		$cache = is_file($cacheFilePath) ? FileSystem::read($cacheFilePath) : null;

		if ($cache === null) {
			FileSystem::write(str_replace('.latte', '-before-compile.latte', $cacheFilePath), $template);
			$cache = $this->mjmlToHtml($template);
			$cache = $this->afterCompileProcess($cache);
			$cache = $this->beforeRenderProcess($cache);
			FileSystem::write($cacheFilePath, $cache);
		}

		return (new Engine)
			->setLoader(new StringLoader)
			->renderToString(
				$cache ?? '',
				array_merge($this->getBasicParameters(), $parameters)
			);
	}

	/**
	 * @param string $template
	 * @param string $path
	 * @return string
	 */
	private function processInclude(string $template, string $path): string
	{
		return (string) preg_replace_callback(
			'/\n(\s*)<mj-include path="([^"]*)"(?:\s*\/?)?>/',
			function (array $match) use ($path): string {
				$spacer = str_repeat("\t", strlen(str_replace('    ', "\t", (string) $match[1])));
				$filePath = $path . '/' . ltrim($match[2], '/');

				if (is_file($filePath)) {
					return "\n" . $spacer . '<!-- include "' . $this->escapeHtmlComment($match[2]) . '" start -->'
						. "\n" . $spacer
						. str_replace("\n", "\n" . $spacer,
							str_replace(["\r\n", "\r"], "\n", (string) file_get_contents($filePath))
						)
						. "\n" . $spacer . '<!-- include "' . $this->escapeHtmlComment($match[2]) . '" end -->';
				}

				return '<!-- can not include "' . $this->escapeHtmlComment($match[2]) . '" -->';
			},
			$template
		);
	}

	/**
	 * @param string $template
	 * @return string
	 */
	private function processSpacer(string $template): string
	{
		return (string) preg_replace_callback(
			'/<mj-spacer[^>]+?height="(\d+(?:px)?)"[^>]+?(?:\s*\/?)?>/',
			function (array $match): string {
				return '<!-- SPACER {' . $this->escapeHtmlComment($match[1]) . '} -->';
			},
			$template
		);
	}

	/**
	 * @param string $template
	 * @return string
	 */
	private function afterCompileProcess(string $template): string
	{
		$template = str_replace('&#36;', '$', $template);
		$template = (string) preg_replace_callback(
			'/<!-- SPACER {(\d+(?:px)?)} -->/',
			static function (array $match): string {
				return '<div style="height:' . $match[1] . '"></div>' . "\n" . '<!-- spacer ' . $match[1] . ' -->';
			}, $template
		);
		$template = (string) preg_replace('/><(\w{2,20})/', ">\n<$1", $template);

		return $template;
	}

	/**
	 * @param string $template
	 * @return string
	 * @throws MjmlRendererException
	 */
	private function mjmlToHtml(string $template): string
	{
		return $this->client->get()->mjmlToHtml($template);
	}

}