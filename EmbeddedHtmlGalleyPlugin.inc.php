<?php

/**
 * @file plugins/generic/embeddedHtmlGalley/EmbeddedHtmlGalleyPlugin.inc.php
 *
 * Copyright (c) University of Pittsburgh
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class EmbeddedHtmlGalleyPlugin
 * @ingroup plugins_generic_embeddedHtmlGalley
 *
 * @brief Class for EmbeddedHtmlGalley plugin
 */
use PKP\facades\Locale;

import('plugins.generic.htmlArticleGalley.HtmlArticleGalleyPlugin');

class EmbeddedHtmlGalleyPlugin extends HtmlArticleGalleyPlugin {
	/**
	 * @see Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		if (!$success) return false;
		if ($success && $this->getEnabled()) {
			// Add button to article view page
			HookRegistry::register('Templates::Article::Main', array($this, 'addReadButton'));
		}
		return true;
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.generic.embeddedHtmlGalley.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.generic.embeddedHtmlGalley.description');
	}

        /**
         * Add button to article view page
         *
         * Hooked to `Templates::Article::Main`
         * @param $hookName string
         * @param $params array [
         *  @option Smarty
         *  @option string HTML output to return
         * ]
         */
        function addReadButton($hookName, $params) {
                $templateMgr =& $params[1];
                $output =& $params[2];

                $request = $this->getRequest();
                $context = $request->getContext();
                $primaryLocale = $context->getPrimaryLocale();


		if ($templateMgr && $request) {
			$router = $request->getRouter();
			if ($router->getRequestedPage($request) === 'article' && $router->getRequestedOp($request) === 'view') {
				$submission = $templateMgr->getTemplateVars('article');
				$galleys = $submission->getGalleys();
				foreach ($galleys as $galley) {
					if ($galley->getFileType() == 'text/html') {
						$templateMgr->assign('submissionId', $submission->getBestArticleId());
						$templateMgr->assign('galleyId', $galley->getBestGalleyId());
						// TODO: Always display language or only if not primary?
						//if ($galley->getLocale() != $primaryLocale) {
                                                if ($galley->getLocale()) {
                                                        //error_log ("galleyLocale: " . var_export($galley->getLocale(),true));
                                                        //$galleyLabel = ' (' . Locale::getMetadata($galley->getLocale())->getDisplayName() . ')';
                                                        $galleyLabel = ' - ' . $galley->getGalleyLabel();
                                                } else {
                                                        $galleyLabel = '';
                                                }
                                                $templateMgr->assign('galleyLabel', $galleyLabel);

						$output = $templateMgr->fetch($this->getTemplateResource('button.tpl')) . $output;
					}
				}
			}
		}
	}


	/**
	 * Present the article wrapper page.
	 * @param string $hookName
	 * @param array $args
	 */
	function articleViewCallback($hookName, $args) {
		$request =& $args[0];
		$issue =& $args[1];
		$galley =& $args[2];
		$article =& $args[3];

		$htmlGalleyStyle = '';

		if ($galley && $galley->getFileType() == 'text/html') {
			$fileId = $galley->getFileId();
			if (!HookRegistry::call('HtmlArticleGalleyPlugin::articleDownload', array($article,  &$galley, &$fileId))) {


				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->assign(array(
					'issue' => $issue,
					'article' => $article,
					'galley' => $galley,
					'hasAccess' => 1,
				));
				//TODO - hasAccess: what if user actually has no access?
				
				$embeddedHtmlGalley = $this->_getHTMLContents($request, $galley);
				$embeddedHtmlGalleyBody = $this->_extractBodyContents($embeddedHtmlGalley, $htmlGalleyStyle);
				$templateMgr->assign('embeddedHtmlGalley', $embeddedHtmlGalleyBody);

				$baseImportPath = $request->getBaseUrl() . '/' . $this->getPluginPath();

				// styles
				$templateMgr->addStyleSheet('HtmlGalleyStyle', $baseImportPath . '/' . '/style/htmlGalley.css');

				// insert extracted style
				$templateMgr->addStyleSheet('embeddedHtmlGalleyStyle', $htmlGalleyStyle, ['inline' => true]);

				// javascript to popup images
                                $templateMgr->addJavaScript('EmbeddedHtmlGalley', $baseImportPath . '/js/embeddedHtmlGalley.js', array( 'contexts' => 'frontend'));

				$returner = true;
				HookRegistry::call('HtmlArticleGalleyPlugin::articleDownloadFinished', array(&$returner));

				$templateMgr->display($this->getTemplateResource('displayInline.tpl'));

				return true;
			}
		}
		return false;
	}

	/**
	 * Return string containing the contents of the HTML body
	 * @param $html string
	 * @return string
	 */
	function _extractBodyContents($html, &$htmlGalleyStyle) {
		$bodyContent = '';
		try {
			if (!function_exists('libxml_use_internal_errors') || !class_exists('DOMDocument')) {
				throw new Exception('Missing libxml/dom requirements');
			}
			$errorsEnabled = libxml_use_internal_errors();
			libxml_use_internal_errors(true);
			$dom = new DOMDocument();
			$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8"));

			// get galley style 
			$styles = $dom->getElementsByTagName('style');
			foreach ($styles as $style) {
				$htmlGalleyStyle .= $style->nodeValue;
			}

			$tags = $dom->getElementsByTagName('body');
			foreach ($tags as $body) {
				foreach ($body->childNodes as $child) {
					$bodyContent .= $dom->saveHTML($child);
				}
			}
			libxml_use_internal_errors($errorsEnabled);

		} catch (Exception $e) {

			$html = preg_replace('/.*<body[^>]*>/isA', '', $html);
			$html = preg_replace('/<\/body\s*>.*$/isD', '', $html);
			$bodyContent = $html;
		}

		// delete title and subtitle
		$bodyContent = preg_replace('#<header id="title-header">(.*?)</header>#is', '', $bodyContent);

		return $bodyContent;
	}
}
