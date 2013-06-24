<?php

class SkinMobileAlpha extends SkinMobileBeta {
	public $template = 'MobileTemplateBeta';
	protected $mode = 'alpha';

	protected function getSearchPlaceHolderText() {
		return wfMessage( 'mobile-frontend-placeholder-alpha' )->escaped();
	}

	public function getDefaultModules() {
		$modules = parent::getDefaultModules();
		$modules['alpha'] = array( 'mobile.alpha' );
		// main page special casing
		if ( $this->getTitle()->isMainPage() ) {
			$out->addModuleStyles( 'mobile.mainpage.styles' );
			$modules['mainpage'] = array();
		}
		return $modules;
	}

	public function prepareData( BaseTemplate $tpl ) {
		parent::prepareData( $tpl );
		$this->prepareTalkLabel( $tpl );
		$this->prepareHistoryLink( $tpl );
	}

	protected function prepareHistoryLink( BaseTemplate $tpl ) {
		$revId = $this->getRevisionId();
		$h = $tpl->data['historyLink'];
		$h['href'] = SpecialPage::getTitleFor( 'MobileDiff', $revId )->getLocalUrl();
		$tpl->set( 'historyLink', $h );
	}

	protected function prepareTalkLabel( BaseTemplate $tpl ) {
		$title = $this->getTitle();
		$user = $this->getUser();
		$isSpecialPage = $title->isSpecialPage();

		// talk page link for logged in alpha users
		if ( !$isSpecialPage && !$title->isTalkPage() ) {
			$talkTitle = $title->getTalkPage();
			if ( $talkTitle->getArticleID() ) {
				$dbr = wfGetDB( DB_SLAVE );
				$numTopics = $dbr->selectField( 'page_props', 'pp_value',
					array( 'pp_page' => $talkTitle->getArticleID(), 'pp_propname' => 'page_top_level_section_count' ),
					__METHOD__
				);
			} else {
				$numTopics = 0;
			}
			if ( $numTopics ) {
				$talkLabel = $this->getLanguage()->formatNum( $numTopics );
				$class = 'count';
			} else {
				$talkLabel = wfMessage( 'mobile-frontend-talk-overlay-header' );
				$class = '';
			}
			$menu = $tpl->data['page_actions'];
			if ( isset( $menu['talk'] ) ) {
				$menu['talk']['text'] = $talkLabel;
				$menu['talk']['class'] = $class;
			}
			$tpl->set( 'page_actions', $menu );
		}
	}
}
