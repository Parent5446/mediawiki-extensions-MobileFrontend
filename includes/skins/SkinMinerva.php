<?php

/**
 * Minerva: Born from the godhead of Jupiter with weapons!
 * A skin that works on both desktop and mobile
 * @ingroup Skins
 */
class SkinMinerva extends SkinTemplate {
	public $skinname = 'minerva';
	public $template = 'MinervaTemplate';
	public $useHeadElement = true;
	/* @var string  describing the current stability of the skin, can be overriden by derivative experimental skins */
	protected $mode = 'stable';
	/** @var array of classes that should be present on the body tag */
	private $pageClassNames = array();

	protected function prepareQuickTemplate( OutputPage $out = null ) {
		global $wgAppleTouchIcon;
		wfProfileIn( __METHOD__ );
		// add head items
		if ( $wgAppleTouchIcon !== false ) {
			$out->addHeadItem( 'touchicon',
				Html::element( 'link', array( 'rel' => 'apple-touch-icon', 'href' => $wgAppleTouchIcon ) )
			);
		}
		$out->addHeadItem( 'viewport',
			Html::element( 'meta', array( 'name' => 'viewport', 'content' => 'initial-scale=1.0, user-scalable=yes, minimum-scale=0.25, maximum-scale=1.6' ) )
		);
		// hide chrome on bookmarked sites
		$out->addHeadItem( 'apple-mobile-web-app-capable',
			Html::element( 'meta', array( 'name' => 'apple-mobile-web-app-capable', 'content' => 'yes' ) )
		);
		$out->addHeadItem( 'loadingscript', Html::inlineScript(
			"document.documentElement.className += ' page-loading';"
		) );

		// Generate template after doing the above...
		$tpl = parent::prepareQuickTemplate( $out );
		$tpl->set( 'unstyledContent', $out->getProperty( 'unstyledContent' ) );

		$this->preparePageContent( $tpl );
		$this->prepareHeaderAndFooter( $tpl );
		$this->prepareSearch( $tpl );
		$this->prepareMenuButton( $tpl );
		$this->prepareBanners( $tpl );
		$this->prepareSiteLinks( $tpl );
		$this->prepareWarnings( $tpl );
		$this->preparePageActions( $tpl );
		$this->prepareUserButton( $tpl );
		$this->prepareDiscoveryTools( $tpl );
		$this->preparePersonalTools( $tpl );
		$this->prepareLanguages( $tpl );
		// FIXME: Remove need for a page-loading class
		$bottomScripts = Html::inlineScript(
			"document.documentElement.className = document.documentElement.className.replace( 'page-loading', '' );"
		);
		$bottomScripts .= $out->getBottomScripts();
		$tpl->set( 'bottomscripts', $bottomScripts );
		wfProfileOut( __METHOD__ );
		return $tpl;
	}

	/**
	 * Prepares the header and the content of a page
	 * Stores in QuickTemplate prebodytext, postbodytext keys
	 * @param QuickTemplate
	 */
	protected function preparePageContent( QuickTemplate $tpl ) {
		$title = $this->getTitle();

		// If it's a talk page, add a link to the main namespace page
		if ( $title->isTalkPage() ) {
			$tpl->set( 'subject-page', Linker::link(
				$title->getSubjectPage(),
				wfMessage( 'mobile-frontend-talk-back-to-page', $title->getText() ),
				array( 'class' => 'return-link' )
			) );
		}
	}

	/**
	 * @param string $className: valid class name
	 */
	protected function addPageClass( $className ) {
		$this->pageClassNames[ $className ] = true;
	}

	/**
	 * Overrides Skin::doEditSectionLink
	 */
	public function doEditSectionLink( Title $nt, $section, $tooltip = null, $lang = false ) {
		$lang = wfGetLangObj( $lang );
		$message = wfMessage( 'mobile-frontend-editor-edit' )->inLanguage( $lang )->text();
		return Html::element( 'a', array(
			'href' => '#editor/' . $section,
			'data-section' => $section,
			'class' => 'edit-page'
		), $message );
	}

	/**
	 * Takes a title and returns classes to apply to the body tag
	 * @param $title Title
	 * @return String
	 */
	public function getPageClasses( $title ) {
		if ( $title->isMainPage() ) {
			$className = 'page-Main_Page ';
		} else if ( $title->isSpecialPage() ) {
			$className = 'mw-mf-special ';
		} else {
			$className = '';
		}
		return $className . implode( ' ', array_keys( $this->pageClassNames ) );
	}

	/**
	 * @return string: The current mode of the skin [stable|beta|alpha] that is running
	 */
	protected function getMode() {
		return $this->mode;
	}

	/**
	 * @var MobileContext
	 */
	protected $mobileContext;

	public function __construct() {
		$this->mobileContext = MobileContext::singleton();
		$this->addPageClass( $this->getMode() );
		if ( !$this->getUser()->isAnon() ) {
			$this->addPageClass( 'is-authenticated' );
		}
	}

	/**
	 * Initializes output page and sets up skin-specific parameters
	 * @param $out OutputPage object to initialize
	 */
	public function initPage( OutputPage $out ) {
		parent::initPage( $out );

		$out->addJsConfigVars( $this->getSkinConfigVariables() );
	}

	/**
	 * Prepares the user button.
	 * @param $tpl BaseTemplate
	 */
	protected function prepareUserButton( BaseTemplate $tpl ) {
		$user = $this->getUser();
		$currentTitle = $this->getTitle();
		$notificationsTitle = SpecialPage::getTitleFor( 'Notifications' );
		// If Echo is available, the user is logged in, and they are not already on the
		// notifications archive, show the notifications icon in the header.
		if ( class_exists( 'MWEchoNotifUser' ) && $user->isLoggedIn()
			&& $currentTitle->getPrefixedText() !== $notificationsTitle->getPrefixedText()
		) {
			// FIXME: cap higher counts
			$count = MWEchoNotifUser::newFromUser( $user )->getNotificationCount();

			$tpl->set( 'secondaryButton',
				Html::openElement( 'a', array(
					'title' => wfMessage( 'mobile-frontend-user-button-tooltip' ),
					'href' => $notificationsTitle->getLocalURL( array( 'returnto' => $currentTitle->getPrefixedText() ) ),
					'class' => 'user-button',
					'id'=> 'secondary-button',
				) ) .
				Html::element( 'span', array( 'class' => $count ? '' : 'zero' ), $this->getLanguage()->formatNum( $count ) ) .
				Html::closeElement( 'a' )
			);
		} else {
			$tpl->set( 'secondaryButton', '' );
		}
	}

	/**
	 * Prepares urls and links used by the page
	 * @param QuickTemplate
	 */
	protected function preparePersonalTools( QuickTemplate $tpl ) {
		$returnToTitle = $this->getTitle()->getPrefixedText();
		$donateTitle = SpecialPage::getTitleFor( 'Uploads' );
		$watchTitle = SpecialPage::getTitleFor( 'Watchlist' );

		// watchlist link
		$watchlistQuery = array();
		$user = $this->getUser();
		if ( $user ) {
			$view = $user->getOption( SpecialMobileWatchlist::VIEW_OPTION_NAME, false );
			$filter = $user->getOption( SpecialMobileWatchlist::FILTER_OPTION_NAME, false );
			if ( $view ) {
				$watchlistQuery['watchlistview'] = $view;
			}
			if ( $filter && $view === 'feed' ) {
				$watchlistQuery['filter'] = $filter;
			}
		}

		$items = array(
			'watchlist' => array(
				'text' => wfMessage( 'mobile-frontend-main-menu-watchlist' )->escaped(),
				'href' => $this->getUser()->isLoggedIn() ?
					$watchTitle->getLocalUrl( $watchlistQuery ) :
					$this->getLoginUrl( array( 'returnto' => $watchTitle ) ),
				'class' => 'icon-watchlist',
			),
			'uploads' => array(
				'text' => wfMessage( 'mobile-frontend-main-menu-upload' )->escaped(),
				'href' => $this->getUser()->isLoggedIn() ? $donateTitle->getLocalUrl() :
					$this->getLoginUrl( array( 'returnto' => $donateTitle ) ),
				'class' => 'icon-uploads jsonly',
			),
			'settings' => array(
				'text' => wfMessage( 'mobile-frontend-main-menu-settings' )->escaped(),
				'href' => SpecialPage::getTitleFor( 'MobileOptions' )->
					getLocalUrl( array( 'returnto' => $returnToTitle ) ),
				'class' => 'icon-settings',
			),
			'auth' => $this->getLogInOutLink(),
		);
		$tpl->set( 'personal_urls', $items );
	}

	/**
	 * Rewrites the language list so that it cannot be contaminated by other extensions with things other than languages
	 * See bug 57094.
	 * @param QuickTemplate
	 */
	protected function prepareLanguages( $tpl ) {
		$language_urls = $this->getLanguages();
		if ( count( $language_urls ) ) {
			$tpl->setRef( 'language_urls', $language_urls );
		} else {
			$tpl->set( 'language_urls', false );
		}
	}

	/**
	 * Prepares a list of links that have the purpose of discovery in the main navigation menu
	 * @param QuickTemplate
	 */
	protected function prepareDiscoveryTools( QuickTemplate $tpl ) {
		global $wgMFNearby;

		$items = array(
			'home' => array(
				'text' => wfMessage( 'mobile-frontend-home-button' )->escaped(),
				'href' => Title::newMainPage()->getLocalUrl(),
				'class' => 'icon-home',
			),
			'random' => array(
				'text' => wfMessage( 'mobile-frontend-random-button' )->escaped(),
				'href' => SpecialPage::getTitleFor( 'Randompage' )->getLocalUrl( array( 'campaign' => 'random' ) ),
				'class' => 'icon-random',
				'id' => 'randomButton',
			),
			'nearby' => array(
				'text' => wfMessage( 'mobile-frontend-main-menu-nearby' )->escaped(),
				'href' => SpecialPage::getTitleFor( 'Nearby' )->getLocalURL(),
				'class' => 'icon-nearby jsonly',
			),
		);
		if ( !$wgMFNearby ) {
			unset( $items['nearby'] );
		}
		$tpl->set( 'discovery_urls', $items );
	}

	/**
	 * Prepares a url to the Special:UserLogin with query parameters,
	 * taking into account $wgSecureLogin
	 * @param array $query
	 * @return string
	 */
	public function getLoginUrl( $query ) {
		return SpecialPage::getTitleFor( 'Userlogin' )->getFullURL( $query );
	}

	/**
	 * Creates a login or logout button
	 * @return Array: Representation of button with text and href keys
	*/
	protected function getLogInOutLink() {
		global $wgSecureLogin;
		wfProfileIn( __METHOD__ );
		$query = array();
		if ( !$this->getRequest()->wasPosted() ) {
			$returntoquery = $this->getRequest()->getValues();
			unset( $returntoquery['title'] );
			unset( $returntoquery['returnto'] );
			unset( $returntoquery['returntoquery'] );
		}
		$title = $this->getTitle();
		// Don't ever redirect back to the login page (bug 55379)
		if ( !$title->isSpecial( 'Userlogin' ) ) {
			$query[ 'returnto' ] = $title->getPrefixedText();
		}

		if ( $this->getUser()->isLoggedIn() ) {
			if ( !empty( $returntoquery ) ) {
				$query[ 'returntoquery' ] = wfArrayToCgi( $returntoquery );
			}
			$url = SpecialPage::getTitleFor( 'Userlogout' )->getFullURL( $query );
			$url = $this->mobileContext->getMobileUrl( $url, $wgSecureLogin );
			$text = wfMessage( 'mobile-frontend-main-menu-logout' )->escaped();
		} else {
			 // note returnto is not set for mobile (per product spec)
			// note welcome=yes in return to query allows us to detect accounts created from the left nav
			$returntoquery[ 'welcome' ] = 'yes';
			$query[ 'returntoquery' ] = wfArrayToCgi( $returntoquery );
			$url = $this->getLoginUrl( $query );
			$text = wfMessage( 'mobile-frontend-main-menu-login' )->escaped();
		}
		wfProfileOut( __METHOD__ );
		return array(
			'text' => $text,
			'href' => $url,
			'class' => 'icon-loginout',
		);
	}

	/**
	 * Prepare the content for the 'last edited' message, e.g. 'Last edited on 30 August
	 * 2013, at 23:31'. This message is different for the main page since main page
	 * content is typically transcuded rather than edited directly.
	 * @param Title $title The Title object of the page being viewed
	 */
	protected function getHistoryLink( Title $title ) {
		$user = $this->getUser();
		$isMainPage = $title->isMainPage();
		// add last modified timestamp
		$revId = $this->getRevisionId();
		$timestamp = Revision::getTimestampFromId( $this->getTitle(), $revId );
		// Main pages tend to include transclusions (see bug 51924)
		if ( $isMainPage ) {
			$lastModified = wfMessage( 'mobile-frontend-history' )->plain();
		} else {
			$lastModified = wfMessage(
				'mobile-frontend-last-modified-date',
				$this->getLanguage()->userDate( $timestamp, $user ),
				$this->getLanguage()->userTime( $timestamp, $user )
			)->parse();
		}
		$unixTimestamp = wfTimestamp( TS_UNIX, $timestamp );
		$historyUrl = $this->mobileContext->getMobileUrl( $title->getFullURL( 'action=history' ) );
		$link = array(
			'id' => 'mw-mf-last-modified',
			'data-timestamp' => $isMainPage ? '' : $unixTimestamp,
			'href' => $historyUrl,
			'text' => $lastModified,
		);
		$rev = Revision::newFromId( $this->getRevisionId() );
		if ( $rev ) {
			$userId = $rev->getUser();
			if ( $userId ) {
				$revUser = User::newFromId( $userId );
				$link += array(
					'data-user-name' => $revUser->getName(),
					'data-user-gender' => $revUser->getOption( 'gender' ),
				);
			} else {
				$link['data-user-gender'] = 'unknown';
			}
		}
		return $link;
	}

	protected function getSearchPlaceHolderText() {
		return wfMessage( 'mobile-frontend-placeholder' )->text();
	}

	protected function prepareHeaderAndFooter( BaseTemplate $tpl ) {
		$title = $this->getTitle();
		$user = $this->getUser();
		$out = $this->getOutput();
		$disableSearchAndFooter = $out->getProperty( 'disableSearchAndFooter' );
		$tpl->set( 'disableSearchAndFooter', $disableSearchAndFooter );
		if ( $title->isMainPage() ) {
			$out->setPageTitle( $user->isLoggedIn() ?
				wfMessage( 'mobile-frontend-logged-in-homepage-notification', $user->getName() )->text() : '' );
		}
		$pageHeading = $out->getPageTitle();

		if ( $title->isSpecialPage() ) {
			if ( $disableSearchAndFooter ) {
				$htmlHeader = $out->getProperty( 'mobile.htmlHeader' );
				if ( !$htmlHeader ) {
					$htmlHeader = Html::element( 'h1', array(), $pageHeading );
				}
				$tpl->set( 'specialPageHeader', $htmlHeader );
			}
		} else {
			$preBodyText = Html::rawElement( 'h1', array( 'id' => 'section_0' ), $pageHeading );
			$tpl->set( 'prebodytext', $preBodyText );

			// If it's a page that exists, add last edited timestamp
			if ( $this->getWikiPage()->exists() ) {
				$tpl->set( 'historyLink', $this->getHistoryLink( $title ) );
			}
		}

		// set defaults
		if ( !isset( $tpl->data['postbodytext'] ) ) {
			$tpl->set( 'postbodytext', '' ); // not currently set in desktop skin
		}
	}

	protected function prepareSearch( BaseTemplate $tpl ) {
		$searchBox = array(
			'id' => 'searchInput',
			'class' => 'search',
			'autocomplete' => 'off',
			// The placeholder gets fed to HTML::element later which escapes all
			// attribute values, so no need to escape the string here.
			'placeholder' =>  $this->getSearchPlaceHolderText(),
		);
		$tpl->set( 'searchBox', $searchBox );
	}

	protected function prepareMenuButton( BaseTemplate $tpl ) {
		// menu button
		$url = SpecialPage::getTitleFor( 'MobileMenu' )->getLocalUrl() . '#mw-mf-page-left';
		$tpl->set( 'menuButton',
			Html::element( 'a', array(
			'title' => wfMessage( 'mobile-frontend-main-menu-button-tooltip' ),
			'href' => $url,
			'id'=> 'mw-mf-main-menu-button',
			) )
		);
	}

	protected function prepareBanners( BaseTemplate $tpl ) {
		global $wgMFEnableSiteNotice;
		$banners = array();
		if ( $wgMFEnableSiteNotice ) {
			$banners[] = '<div id="siteNotice"></div>';
		}
		$tpl->set( 'banners', $banners );
	}

	protected function prepareSiteLinks( BaseTemplate $tpl ) {
		$aboutPageTitleText = $this->msg( 'aboutpage' )->inContentLanguage()->text();
		$disclaimerPageTitleText = $this->msg( 'disclaimerpage' )->inContentLanguage()->text();
		$urls = array();
		$t = Title::newFromText( $aboutPageTitleText );
		if ( $t ) {
			$urls[] = array(
				'href' => $t->getLocalUrl(),
				'text'=> $this->msg( 'aboutsite' )->text(),
			);
		}
		$t = Title::newFromText( $disclaimerPageTitleText );
		if ( $t ) {
			$urls[] = array(
				'href' => $t->getLocalUrl(),
				'text'=> $this->msg( 'disclaimers' )->text(),
			);
		}
		$tpl->set( 'site_urls', $urls );
	}

	protected function prepareWarnings( BaseTemplate $tpl ) {
		$out = $this->getOutput();
		if ( $out->getRequest()->getText( 'oldid' ) ) {
			$subtitle = $out->getSubtitle();
			$tpl->set( '_old_revision_warning',
				Html::openElement( 'div', array( 'class' => 'alert warning' ) ) . $subtitle . Html::closeElement( 'div' ) );
		}
	}

	protected function preparePageActions( BaseTemplate $tpl ) {
		$title = $this->getTitle();
		// Reuse template data variable from SkinTemplate to construct page menu
		$menu = array();
		$namespaces = $tpl->data['content_navigation']['namespaces'];
		$actions = $tpl->data['content_navigation']['actions'];

		// empty placeholder for edit and photos which both require js
		$menu['edit'] = array( 'id' => 'ca-edit', 'text' => '' );
		$menu['photo'] = array( 'id' => 'ca-upload', 'text' => '' );

		// FIXME [core]: This seems unnecessary..
		$subjectId = $title->getNamespaceKey( '' );
		$talkId = $subjectId === 'main' ? 'talk' : "{$subjectId}_talk";
		if ( isset( $namespaces[$talkId] ) ) {
			$menu['talk'] = $namespaces[$talkId];
		}

		if ( isset( $menu['talk'] ) ) {
			if ( isset( $tpl->data['_talkdata'] ) ) {
				$menu['talk']['text'] = $tpl->data['_talkdata']['text'];
				$menu['talk']['class'] = $tpl->data['_talkdata']['class'];
			}
		}
		// sanitize to avoid invalid HTML5 markup being produced
		unset( $menu['talk']['primary'] );
		unset( $menu['talk']['context'] );

		$watchTemplate = array(
			'id' => 'ca-watch',
			'class' => 'watch-this-article',
		);
		// standardise watch article into one menu item
		if ( isset( $actions['watch'] ) ) {
			$menu['watch'] = array_merge( $actions['watch'], $watchTemplate );
		} else if ( isset( $actions['unwatch'] ) ) {
			$menu['watch'] = array_merge( $actions['unwatch'], $watchTemplate );
			$menu['watch']['class'] .= ' watched';
		} else {
			// placeholder for not logged in
			$menu['watch'] = $watchTemplate;
			// FIXME: makeLink (used by makeListItem) when no text is present defaults to use the key
			$menu['watch']['text'] = '';
			$menu['watch']['href'] = $this->getLoginUrl( array( 'returnto' => $title ) );
		}

		$tpl->set( 'page_actions', $menu );
	}

	/**
	 * Returns array of config variables that should be added only to this skin
	 * for use in JavaScript.
	 * @return Array
	 */
	public function getSkinConfigVariables() {
		global $wgMFLeadPhotoUploadCssSelector, $wgMFEnableCssAnimations,
			$wgMFUseCentralAuthToken,
			$wgMFDeviceWidthTablet,
			$wgMFAjaxUploadProgressSupport,
			$wgMFAnonymousEditing,
			$wgMFPhotoUploadEndpoint, $wgMFPhotoUploadAppendToDesc;

		$title = $this->getTitle();
		$user = $this->getUser();
		$userCanCreatePage = !$title->exists() && $title->quickUserCan( 'create', $user );

		$vars = array(
			'wgMFUseCentralAuthToken' => $wgMFUseCentralAuthToken,
			'wgMFAjaxUploadProgressSupport' => $wgMFAjaxUploadProgressSupport,
			'wgMFAnonymousEditing' => $wgMFAnonymousEditing,
			'wgMFPhotoUploadAppendToDesc' => $wgMFPhotoUploadAppendToDesc,
			'wgMFLeadPhotoUploadCssSelector' => $wgMFLeadPhotoUploadCssSelector,
			'wgMFEnableCssAnimations' => $wgMFEnableCssAnimations,
			'wgMFPhotoUploadEndpoint' => $wgMFPhotoUploadEndpoint ? $wgMFPhotoUploadEndpoint : '',
			'wgPreferredVariant' => $title->getPageLanguage()->getPreferredVariant(),
			'wgIsPageEditable' => $title->quickUserCan( 'edit', $user ) || $userCanCreatePage,
			'wgMFDeviceWidthTablet' => $wgMFDeviceWidthTablet,
			'wgMFMode' => $this->getMode(),
		);
		if ( !$user->isAnon() ) {
			$vars['wgWatchedPageCache'] = array(
				$title->getPrefixedDBkey() => $user->isWatched( $title ),
			);
		}
		// mobile specific config variables
		if ( $this->mobileContext->shouldDisplayMobileView() ) {
			$vars['wgImagesDisabled'] = $this->mobileContext->imagesDisabled();
		}
		return $vars;
	}

	public function getDefaultModules() {
		$modules = parent::getDefaultModules();
		// flush unnecessary modules
		$modules['content'] = array();
		$modules['legacy'] = array();

		$modules['mobile'] = array(
			'mobile.head',
			'mobile.startup',
			'mobile.site',
			// FIXME: separate mobile.stable into more meaningful groupings
			'mobile.stable',
		);

		$modules['notifications'] = array( 'mobile.notifications' );
		$modules['watch'] = array();
		$modules['search'] = array( 'mobile.search.stable' );
		$modules['stableonly'] = array( 'mobile.lastEdited.stable' );
		$modules['issues'] = array( 'mobile.issues' );
		$modules['editor'] = array( 'mobile.editor' );
		$modules['languages'] = array( 'mobile.languages' );

		$title = $this->getTitle();

		// specific to current context
		if ( $title->inNamespace( NS_FILE ) ) {
			$modules['file'] = array( 'mobile.file.scripts' );
		}
		return $modules;
	}

	/**
	 * This will be called by OutputPage::headElement when it is creating the
	 * "<body>" tag, - adds output property bodyClassName to the existing classes
	 * @param $out OutputPage
	 * @param $bodyAttrs Array
	 */
	public function addToBodyAttributes( $out, &$bodyAttrs ) {
		// does nothing by default
		$classes = $out->getProperty( 'bodyClassName' );
		$bodyAttrs[ 'class' ] .= ' ' . $classes;
	}

	protected function getSkinStyles() {
		return array(
			'mobile.styles',
			'mobile.styles.page',
			'mobile.pagelist.styles',
		);
	}

	/**
	 * Add skin-specific stylesheets
	 * @param $out OutputPage
	 */
	public function setupSkinUserCss( OutputPage $out ) {
		parent::setupSkinUserCss( $out );
		// Add the ResourceLoader module to the page output

		$out->addModuleStyles( $this->getSkinStyles() );
	}
}
