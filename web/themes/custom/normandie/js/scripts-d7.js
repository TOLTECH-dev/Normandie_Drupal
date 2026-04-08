jQuery( document ).ready(function() {

    /**
     * function to set cookie
     * @param name
     * @param value
     * @param days
     */
    function createCookie(name, value, days) {
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            var expires = "; expires=" + date.toGMTString();
        }
        else var expires = "";

        document.cookie = name + "=" + value + expires + "; path=/";
    }

    /**
     * Function to get by element in cookie
     * @param name
     * @returns {*}
     */
    function readCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    /**
     * Delete item in cookie
     * @param name
     */
    function eraseCookie(name) {
        createCookie(name, "", -1);
    }

    jQuery('.menu-mobile').on('click', function() {

      jQuery(this).next('.region').find('ul').toggleClass("open");

    });

    if(jQuery(".map-responsive iframe").length){
    	jQuery("body").addClass("google-map");
    }

    var largeur_screen = jQuery( document ).width();

    if(largeur_screen < 992){

      jQuery('.connect > a').on('click', function() {

        jQuery(this).parent('.connect').toggleClass("open");
        return false;

      });

    }
    else{
      jQuery('.connect > a').on('click', function() {

        return false;

      });
    }
    /**
     * Print part of page
     */
    jQuery("#printButton").click(function(){
        window.print();
    });

    /*******************************************************************
     *          MODAL IN HOMEPAGE
     ******************************************************************/
    window.onclose = function(){
        eraseCookie("isVisited");
    };
    var isVisited = readCookie("isVisited");
    if(isVisited) {
        jQuery("#modal-home").css("display","none");
    } else {
        jQuery("#modal-home").css("display","block");
//    	jQuery("#modal-home").css("display","none");
        createCookie("isVisited","ok");
    }

});
