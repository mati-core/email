<?php

declare(strict_types=1);


namespace MatiCore\Email\Mjml;

use Nette\Utils\DateTime;
use Tracy\BlueScreen;
use Tracy\Debugger;

/**
 * Class ApiClient
 * @package MatiCore\Email\Mjml
 */
class ApiClient
{

	private const ENDPOINT = 'https://api.mjml.io/v1';

	/**
	 * @var string
	 */
	private string $applicationId;

	/**
	 * @var string
	 */
	private string $secretKey;

	/**
	 * @param string $applicationId
	 * @param string $secretKey
	 */
	public function __construct(string $applicationId, string $secretKey)
	{
		$this->applicationId = $applicationId;
		$this->secretKey = $secretKey;
	}

	/**
	 * @param string $mjml
	 * @return string
	 * @throws MjmlRendererException
	 */
	public function mjmlToHtml(string $mjml): string
	{
		$response = $this->request('/render', 'POST', json_encode([
			'mjml' => $mjml,
		]));

		if (isset($response['errors'], $response['errors'][0])) {
			Debugger::getBlueScreen()->addPanel(static function ($e): array {
				if (!$e instanceof MjmlRendererException) {
					return [];
				}

				$code = htmlspecialchars($e->getTemplate(), ENT_IGNORE, 'UTF-8');
				$code = str_replace(
					[
						' ',
						"\t",
					],
					[
						"<span class='tracy-dump-whitespace'>·</span>",
						"<span class='tracy-dump-whitespace'>→   </span>"],
					$code
				);


				return [
					'tab' => 'MJML renderer',
					'panel' => '<p>' . htmlspecialchars($e->getMessage()) . '</p>'
						. '<pre class="code"><div>'
						. BlueScreen::highlightLine($code, $e->getLine())
						. '</div></pre>',
				];
			});

			$error = $response['errors'][0];

			throw new MjmlRendererException(
				$error['formattedMessage']
				. (isset($response['mjml_version']) ? "\n" . 'Version: ' . $response['mjml_version'] : ''),
				$mjml,
				$error['line']
			);
		}

		return $response['html'];
	}

	/**
	 * @param string $path
	 * @param string $method
	 * @param string $body
	 * @param array|null $headers
	 * @param array $curlOptions
	 * @return array
	 * @throws \Exception
	 */
	private function request(
		string $path,
		string $method,
		string $body,
		?array $headers = null,
		array $curlOptions = []
	): array
	{
		if ($headers === null || $headers === []) {
			$headers = [
				'Content-Type' => 'application/json',
			];
		}

		$headers = array_map(function ($key, $value) {
			if (false !== strpos($value, ':')) {
				[$key, $value] = explode(':', $value);
			}

			return sprintf('%s: %s', $key, $value);
		}, array_keys($headers), $headers);

		$ch = curl_init(self::ENDPOINT . $path);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:%s', $this->applicationId, $this->secretKey));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

		foreach ($curlOptions as $option => $value) {
			curl_setopt($ch, $option, $value);
		}

		$response = curl_exec($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if (curl_errno($ch)) {
			throw new \RuntimeException(curl_error($ch));
		}

		curl_close($ch);

		$response = json_decode($response, true);
		if (json_last_error()) {
			throw new \RuntimeException(json_last_error_msg());
		}

		if ($statusCode !== 200) {
			throw new \Exception(
				$response['message'],
				$statusCode,
				null,
				$response['requestId'] ?? null,
				isset($response['startedAt']) ? DateTime::from($response['startedAt']) : null
			);
		}

		return $response;
	}

}