jQuery( document ).ready( function(){

    var OneMedia =  {
        setAttachment: function( attachment ){
            this.attachment = attachment;
        },
        addParamsURL: function( url, data ) {
            if ( ! $.isEmptyObject(data) )
            {
                url += ( url.indexOf('?') >= 0 ? '&' : '?' ) + $.param(data);
            }
            return url;
        },
        getThumb: function( attachment ){
            var control = this;
            if ( typeof attachment !== "undefined" ) {
                this.attachment = attachment;
            }
            var t = new Date().getTime();
            if ( typeof this.attachment.sizes !== "undefined" ) {
                if ( typeof this.attachment.sizes.medium !== "undefined" ) {
                    return control.addParamsURL( this.attachment.sizes.medium.url, { t : t } );
                }
            }
            return control.addParamsURL( this.attachment.url, { t : t } );
        },
        getURL: function( attachment ) {
            if ( typeof attachment !== "undefined" ) {
                this.attachment = attachment;
            }
            var t = new Date().getTime();
            return this.addParamsURL( this.attachment.url, { t : t } );
        },
        getID: function( attachment ){
            if ( typeof attachment !== "undefined" ) {
                this.attachment = attachment;
            }
            return this.attachment.id;
        },
        getInputID: function( attachment ){
            $( '.attachment-id', this.preview ).val( );
        },
        setPreview: function( $el ){
            this.preview = $el;
        },
        insertImage: function( attachment ){
            if ( typeof attachment !== "undefined" ) {
                this.attachment = attachment;
            }

            var url = this.getURL();
            var id = this.getID();
            var mime = this.attachment.mime;
            $( '.tophive-image-preview', this.preview ).addClass( 'tophive--has-file' ).html(  '<img src="'+url+'" alt="">' );
            $( '.attachment-url', this.preview ).val( this.toRelativeUrl( url ) );
            $( '.attachment-mime', this.preview ).val( mime );
            $( '.attachment-id', this.preview ).val( id ).trigger( 'change' );
            this.preview.addClass( 'attachment-added' );
            this.showChangeBtn();

        },
        toRelativeUrl: function( url ){
            return url;
        },
        showChangeBtn: function(){
            $( '.tophive--add', this.preview ).addClass( 'tophive--hide' );
            $( '.tophive--change', this.preview ).removeClass( 'tophive--hide' );
            $( '.tophive--remove', this.preview ).removeClass( 'tophive--hide' );
        },
        insertVideo: function(attachment ){
            if ( typeof attachment !== "undefined" ) {
                this.attachment = attachment;
            }

            var url = this.getURL();
            var id = this.getID();
            var mime = this.attachment.mime;
            var html = '<video width="100%" height="" controls><source src="'+url+'" type="'+mime+'">Your browser does not support the video tag.</video>';
            $( '.tophive-image-preview', this.preview ).addClass( 'tophive--has-file' ).html( html );
            $( '.attachment-url', this.preview ).val( this.toRelativeUrl( url ) );
            $( '.attachment-mime', this.preview ).val( mime );
            $( '.attachment-id', this.preview ).val( id ).trigger( 'change' );
            this.preview.addClass( 'attachment-added' );
            this.showChangeBtn();
        },
        insertFile: function( attachment ){
            if ( typeof attachment !== "undefined" ) {
                this.attachment = attachment;
            }
            var url = attachment.url;
            var mime = this.attachment.mime;
            var basename = url.replace(/^.*[\\\/]/, '');

            $( '.tophive-image-preview', this.preview ).addClass( 'tophive--has-file' ).html( '<a href="'+url+'" class="attachment-file" target="_blank">'+basename+'</a>' );
            $( '.attachment-url', this.preview ).val( this.toRelativeUrl( url ) );
            $( '.attachment-mime', this.preview ).val( mime );
            $( '.attachment-id', this.preview ).val( this.getID() ).trigger( 'change' );
            this.preview.addClass( 'attachment-added' );
            this.showChangeBtn();
        },
        remove: function( $el ){
            if ( typeof $el !== "undefined" ) {
                this.preview = $el;
            }
            $( '.tophive-image-preview', this.preview ).removeAttr( 'style').html( '' ).removeClass( 'tophive--has-file' );
            $( '.attachment-url', this.preview ).val( '' );
            $( '.attachment-mime', this.preview ).val( '' );
            $( '.attachment-id', this.preview ).val( '' ).trigger( 'change' );
            this.preview.removeClass( 'attachment-added' );

            $( '.tophive--add', this.preview ).removeClass( 'tophive--hide' );
            $( '.tophive--change', this.preview ).addClass( 'tophive--hide' );
            $( '.tophive--remove', this.preview ).addClass( 'tophive--hide' );
        }

    };

    OneMedia.controlMediaImage = wp.media({
        title: wp.media.view.l10n.addMedia,
        multiple: false,
        library: {type: 'image' }
    });

    OneMedia.controlMediaImage.on('select', function () {
        var attachment = OneMedia.controlMediaImage.state().get('selection').first().toJSON();
        OneMedia.insertImage( attachment );
    });

    OneMedia.controlMediaVideo = wp.media({
        title: wp.media.view.l10n.addMedia,
        multiple: false,
        library: {type: 'video' }
    });

    OneMedia.controlMediaVideo.on('select', function () {
        var attachment = OneMedia.controlMediaVideo.state().get('selection').first().toJSON();
        OneMedia.insertVideo( attachment );
    });

    OneMedia.controlMediaFile = wp.media({
        title: wp.media.view.l10n.addMedia,
        multiple: false
    });

    OneMedia.controlMediaFile.on('select', function () {
        var attachment = OneMedia.controlMediaFile.state().get('selection').first().toJSON();
        OneMedia.insertFile( attachment );
    });


    $( '.tophive-mt-media' ).on( 'click',  '.tophive--add', function( e ) {
        e.preventDefault();
        var p = $( this ).closest('.tophive-mt-media');
        console.log( p );
        OneMedia.setPreview( p );
        OneMedia.controlMediaImage.open();
    } );

    $( '.tophive-mt-media' ).on( 'click',  '.tophive--remove', function( e ) {
        e.preventDefault();
        var p = $( this ).closest('.tophive-mt-media');
        OneMedia.remove(p);
    } );
    

} );