<?php
require_once __DIR__ . '/../config/env.php';

if (!function_exists('renderEmailTemplate')) {
	/**
	 * Render a branded, responsive HTML email layout around provided content.
	 * @param string $title        Title shown as the main heading inside the email
	 * @param string $contentHtml  Inner HTML content (already sanitized where appropriate)
	 * @param array  $options      Optional overrides: preheader, logoUrl, primaryColor, footerText, brandName
	 * @return string              Full HTML email ready to send
	 */
	function renderEmailTemplate(string $title, string $contentHtml, array $options = []): string {
		$brandName   = (string)($options['brandName'] ?? env('BRAND_NAME', 'Shelton Resort'));
		$primary     = (string)($options['primaryColor'] ?? env('EMAIL_PRIMARY_COLOR', '#7ab4a1'));
		$logoUrl     = (string)($options['logoUrl'] ?? env('EMAIL_LOGO_URL', 'cid:brandlogo'));
		$preheader   = (string)($options['preheader'] ?? '');
		$footerText  = (string)($options['footerText'] ?? ('© ' . date('Y') . ' ' . $brandName . '. All rights reserved.'));
		$cta         = is_array($options['cta'] ?? null) ? $options['cta'] : null; // ['text' => 'View booking', 'url' => 'https://...', 'color' => '#7ab4a1']

		$titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

		$logoBlock = '';
		if ($logoUrl !== '') {
			$logoEsc = htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8');
			$logoBlock = '<tr><td style="padding:24px 24px 0 24px;text-align:center;"><img src="' . $logoEsc . '" alt="' . htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') . '" style="max-width:140px;height:auto;border:0;outline:none;text-decoration:none;" /></td></tr>';
		}

		$preheaderBlock = $preheader !== ''
			? '<span style="display:none!important;visibility:hidden;mso-hide:all;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;color:transparent;">' . htmlspecialchars($preheader, ENT_QUOTES, 'UTF-8') . '</span>'
			: '';

		$ctaBlock = '';
		if ($cta && !empty($cta['text']) && !empty($cta['url'])) {
			$ctaText  = htmlspecialchars((string)$cta['text'], ENT_QUOTES, 'UTF-8');
			$ctaUrl   = htmlspecialchars((string)$cta['url'], ENT_QUOTES, 'UTF-8');
			$ctaColor = htmlspecialchars((string)($cta['color'] ?? $primary), ENT_QUOTES, 'UTF-8');
			$ctaBlock = '<div style="margin-top:18px;text-align:center;">'
				. '<a href="' . $ctaUrl . '" style="display:inline-block;background:' . $ctaColor . ';color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:6px;font-family:Arial,Helvetica,sans-serif;font-size:14px;">' . $ctaText . '</a>'
				. '</div>';
		}

		$html = <<<HTML
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta http-equiv="x-ua-compatible" content="ie=edge" />
	<title>{$titleEsc}</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f7fb;">
{$preheaderBlock}
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f5f7fb;">
	<tr>
		<td align="center" style="padding:24px;">
			<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
				<tr>
					<td style="background:{$primary};padding:16px 24px;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:16px;">
						<strong>{$brandName}</strong>
					</td>
				</tr>
				{$logoBlock}
				<tr>
					<td style="padding:24px;font-family:Arial,Helvetica,sans-serif;color:#263238;">
						<h1 style="margin:0 0 12px 0;font-size:20px;line-height:1.3;color:#111827;">{$titleEsc}</h1>
						<div style="font-size:14px;line-height:1.6;color:#374151;">
							{$contentHtml}
						</div>
						{$ctaBlock}
					</td>
				</tr>
				<tr>
					<td style="padding:16px 24px;border-top:1px solid #e5e7eb;background:#fafafa;font-family:Arial,Helvetica,sans-serif;color:#6b7280;font-size:12px;text-align:center;">
						{$footerText}
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</body>
</html>
HTML;

		return $html;
	}
}

?>


