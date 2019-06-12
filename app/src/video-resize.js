
let allVideos    = document.querySelectorAll( 'iframe[src*="//player.vimeo.com"], iframe[src*="//www.youtube.com"], object, embed' ),
    fluidElement = document.querySelector( '.f-video-editor' );

allVideos.forEach( function( item ) {
    item.setAttribute( 'data-aspectRatio', item.height / item.width );
    item.removeAttribute( 'height' );
    item.removeAttribute( 'width' );
} );

window.onresize = function() {

    if ( ! fluidElement ) {
        return;
    }

    let newWidth = fluidElement.width;

    allVideos.forEach( function( item ) {
        item.width  = newWidth;
        item.height = ( newWidth * item.getAttribute( 'data-aspectRatio' ) );
    } );
};

// Trigger window resize
let el    = document;
let event = document.createEvent( 'HTMLEvents' );
event.initEvent( 'resize', true, false );
el.dispatchEvent( event );