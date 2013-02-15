(function ( $ ) {
    $(document).ready(function() {
         $.Nethgui.Server.ajaxMessage({
                isMutation: false,
                url: "/Dashboard/SystemStatus"
            });
 
    });
} ( jQuery ));
