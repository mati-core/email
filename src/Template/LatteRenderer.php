<?php

declare(strict_types=1);

namespace MatiCore\Email;


use Latte\Engine;
use Latte\Loaders\StringLoader;
use Nette\DI\Container;
use Nette\Utils\FileSystem;

/**
 * Class LatteRenderer
 * @package MatiCore\Email
 */
class LatteRenderer extends BaseTemplateRenderer
{

	/**
	 * @var string
	 */
	private $tempDir;

	/**
	 * @param string $tempDir
	 * @param Container $container
	 */
	public function __construct(string $tempDir, Container $container)
	{
		parent::__construct($container);
		$this->tempDir = $tempDir . '/cache/_latteRenderer';
	}

	/**
	 * @param string $templatePath
	 * @param mixed[] $parameters
	 * @return string
	 */
	public function render(string $templatePath, array $parameters = []): string
	{
		FileSystem::createDir($this->tempDir);

		$cacheFilePath = $this->tempDir . '/' . md5($templatePath) . '.latte';
		$cache = is_file($cacheFilePath) ? FileSystem::read($cacheFilePath) : null;

		if ($cache === null) {
			$cache = $this->beforeRenderProcess((string) file_get_contents($templatePath));
			FileSystem::write($cacheFilePath, $cache);
		}

		return (new Engine)
			->setLoader(new StringLoader)
			->renderToString(
				$cache ?? '',
				array_merge($this->getBasicParameters(), $parameters)
			);
	}

}