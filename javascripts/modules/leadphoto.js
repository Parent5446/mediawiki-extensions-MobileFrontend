( function( M, $ ) {

	var
		funnel = $.cookie( 'mwUploadsFunnel' ) || 'article',
		showCta = mw.config.get( 'wgMFEnablePhotoUploadCTA' ) || funnel === 'nearby',
		popup = M.require( 'notifications' ),
		PhotoUploaderPageActionButton = M.require( 'uploads/PhotoUploaderPageActionButton' ),
		PhotoUploaderButton = M.require( 'uploads/PhotoUploaderButton' ),
		isSupported = PhotoUploaderButton.isSupported,
		LeadPhoto = M.require( 'uploads/LeadPhoto' );

	function needsPhoto( $container ) {
		var $content_0 = $container.find( '#content_0' );
		// FIXME: workaround for https://bugzilla.wikimedia.org/show_bug.cgi?id=43271
		if ( $content_0.length ) {
			$container = $content_0;
		}

		return $container.find( mw.config.get( 'wgMFLeadPhotoUploadCssSelector' ) ).length === 0;
	}

	// reset the funnel cookie as it is no longer valid (this stops upload cta showing on further page loads)
	if ( funnel ) {
		$.cookie( 'mwUploadsFunnel', null );
	}

	function initialize() {
		// FIXME: make some general function for that (or a page object with a method)
		var namespaceIds = mw.config.get( 'wgNamespaceIds' ),
			namespace = mw.config.get( 'wgNamespaceNumber' ),
			validNamespace = ( namespace === namespaceIds[''] || namespace === namespaceIds.user ),
			$page = $( '#content' ),
			$pageHeading = $page.find( 'h1' ).first(),
			optionsPhotoUploader,
			photoUploader;

		if ( !validNamespace || mw.util.getParamValue( 'action' ) || !needsPhoto( $page ) || mw.config.get( 'wgIsMainPage' ) ) {
			return;
		}

		optionsPhotoUploader = {
			buttonCaption: mw.msg( 'mobile-frontend-photo-upload' ),
			insertInPage: true,
			pageTitle: mw.config.get( 'wgTitle' ),
			funnel: funnel
		};

		if ( $( '#ca-upload' ).length ) {
			optionsPhotoUploader.el = '#ca-upload';
			photoUploader = new PhotoUploaderPageActionButton( optionsPhotoUploader );
		// FIXME: Remove else clause when page actions go to stable
		} else {
			photoUploader = new PhotoUploaderButton( optionsPhotoUploader ).insertAfter( $pageHeading );
		}
		photoUploader.on( 'start', function() {
				photoUploader.$el.hide();
			} ).
			on( 'success', function( data ) {
				popup.show( mw.msg( 'mobile-frontend-photo-upload-success-article' ), 'toast' );
				// FIXME: workaround for https://bugzilla.wikimedia.org/show_bug.cgi?id=43271
				if ( !$( '#content_0' ).length ) {
					$( '<div id="content_0" >' ).insertAfter( $( '#section_0,#page-actions' ).last() );
				}
				new LeadPhoto( {
					url: data.url,
					pageUrl: data.descriptionUrl,
					caption: data.description
				} ).prependTo( '#content_0' ).animate();
			} ).
			on( 'error cancel', function() {
				photoUploader.$el.show();
			} );
	}

	if (
		isSupported && mw.config.get( 'wgIsPageEditable' ) &&
		( M.isLoggedIn() || showCta )
	) {
		$( initialize );
		M.on( 'page-loaded', function() {
			initialize();
		} );
	}

	M.define( '_leadphoto', {
		needsPhoto: needsPhoto
	} );

}( mw.mobileFrontend, jQuery ) );