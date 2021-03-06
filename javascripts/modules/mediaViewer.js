( function( M, $ ) {
	M.assertMode( [ 'alpha', 'beta' ] );

	var Overlay = M.require( 'Overlay' ),
		Api = M.require( 'api' ).Api,
		ImageApi, ImageOverlay, api;

	ImageApi = Api.extend( {
		initialize: function() {
			this._super();
			this._cache = {};
		},

		getThumb: function( title ) {
			var result = this._cache[title];

			if ( !result ) {
				this._cache[title] = result = $.Deferred();

				api.get( {
					action: 'query',
					prop: 'imageinfo',
					titles: title,
					iiprop: ['url', 'extmetadata'],
					// request an image two times bigger than the reported screen size
					// for retina displays and zooming
					iiurlwidth: $( window ).width() * 2,
					iiurlheight: $( window ).height() * 2
				} ).done( function( resp ) {
					if ( resp.query && resp.query.pages ) {
						// FIXME: API
						var data = $.map( resp.query.pages, function( v ) { return v; } )[0].imageinfo[0];
						result.resolve( data );
					}
				} );
			}

			return result;
		}
	} );

	api = new ImageApi();

	ImageOverlay = Overlay.extend( {
		className: 'mw-mf-overlay media-viewer',
		template: M.template.get( 'modules/ImageOverlay' ),
		closeOnBack: true,

		defaults: {
			detailsMsg: mw.msg( 'mobile-frontend-media-details' ),
			licenseLinkMsg: mw.msg( 'mobile-frontend-media-license-link' )
		},

		postRender: function( options ) {
			var self = this, $img;
			this._super( options );

			api.getThumb( options.title ).done( function( data ) {
				self.imgRatio = data.thumbwidth / data.thumbheight;

				self.$( '.container' ).removeClass( 'loading' );
				$img = $( '<img>' ).attr( 'src', data.thumburl ).attr( 'alt', options.caption );
				self.$( '.container div' ).append( $img );
				self._positionImage();
				self.$( '.details a' ).attr( 'href', data.descriptionurl );
				if ( data.extmetadata && data.extmetadata.LicenseShortName ) {
					self.$( '.license a' ).text( data.extmetadata.LicenseShortName.value );
				}

				self.$el.on( M.tapEvent( 'click' ), function() {
					self.$( '.details' ).toggleClass( 'visible' );
				} );
			} );

			$( window ).on( 'resize', $.proxy( this, '_positionImage' ) );
		},

		_positionImage: function() {
			var windowWidth = $( window ).width(),
				windowHeight = $( window ).height(),
				windowRatio = windowWidth / windowHeight;

			// display: table (which we use for vertical centering) makes the overlay
			// expand so simply setting width/height to 100% doesn't work
			if ( this.imgRatio > windowRatio ) {
				this.$( 'img' ).css( {
					width: windowWidth,
					height: 'auto'
				} );
			} else {
				this.$( 'img' ).css( {
					width: 'auto',
					height: windowHeight
				} );
			}
		}
	} );

	function init( $el ) {
		M.router.route( /^\/image\/(.+)$/, function( title ) {
			var caption = $( 'a[href*="' + title + '"]' ).siblings( '.thumbcaption' ).text();

			new ImageOverlay( {
				title: decodeURIComponent( title ),
				caption: caption
			} ).show();
		} );

		$el.find( 'a.image, a.thumbimage' ).each( function() {
			var $a = $( this ), match = $a.attr( 'href' ).match( /[^\/]+$/ );

			if ( match ) {
				$a.on( M.tapEvent( 'click' ), function( ev ) {
					ev.preventDefault();
					M.router.navigate( '#/image/' + match[0] );
				} );
			}
		} );
	}

	// FIXME: this should bind to only 1-2 events
	init( $( '#content_wrapper' ) );
	M.on( 'page-loaded', function( page ) {
		init( page.$el );
	} );
	M.on( 'section-rendered', init );
	M.on( 'photo-loaded', init );

}( mw.mobileFrontend, jQuery ) );
