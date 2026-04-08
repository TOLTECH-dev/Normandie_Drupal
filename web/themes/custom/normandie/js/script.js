/**
 * @file
 * Normandie Theme - Main JavaScript
 * Migrated from Drupal 7 to Drupal 10
 */

(function (Drupal, jQuery) {
  'use strict';

  /**
   * Theme initialization behaviors
   */
  Drupal.behaviors.normandieTheme = {
    attach: function (context, settings) {
      // Cookie management functions
      var createCookie = function(name, value, days) {
        if (days) {
          var date = new Date();
          date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
          var expires = "; expires=" + date.toGMTString();
        } else {
          var expires = "";
        }
        document.cookie = name + "=" + value + expires + "; path=/";
      };

      var readCookie = function(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
          var c = ca[i];
          while (c.charAt(0) == ' ') c = c.substring(1, c.length);
          if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
      };

      var eraseCookie = function(name) {
        createCookie(name, "", -1);
      };

      // Mobile menu toggle
      jQuery('.menu-mobile').on('click', function() {
        jQuery(this).closest('.container').find('ul').toggleClass("open");
      });

      // Google Maps responsive container
      if (jQuery(".map-responsive iframe").length) {
        jQuery("body").addClass("google-map");
      }

      // Responsive navigation toggle
      var largeur_screen = jQuery(document).width();
      if (largeur_screen < 992) {
        jQuery('.connect > a').on('click', function() {
          jQuery(this).parent('.connect').toggleClass("open");
          return false;
        });
      } else {
        jQuery('.connect > a').on('click', function() {
          return false;
        });
      }

      // Print functionality
      jQuery("#printButton").on('click', function() {
        window.print();
      });

      // Homepage modal management
      window.onclose = function() {
        eraseCookie("isVisited");
      };
      
      var isVisited = readCookie("isVisited");
      if (isVisited) {
        jQuery("#modal-home").css("display", "none");
      } else {
        jQuery("#modal-home").css("display", "block");
        createCookie("isVisited", "ok");
      }

      // Fermeture de la modal
      jQuery("#modal-home .close, #modal-home .btn-close").on("click", function() {
        jQuery("#modal-home").css("display", "none");
      });
    }
  };

})(Drupal, jQuery);
