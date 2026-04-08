// Force French language for consistency across all pages
tarteaucitronForceLanguage = 'fr';

tarteaucitron.init({
  "privacyUrl": "https://www.normandie.fr/protection-des-donnees-a-caractere-personnel", /* Privacy policy url */
  "bodyPosition": "bottom", /* top to bring it as first element for accessibility */
  "hashtag": "#tarteaucitron", /* Open the panel with this hashtag */
  "cookieName": "tarteaucitron", /* Cookie name */
  "orientation": "middle", /* Banner position (top - bottom) */
  "groupServices": false, /* Group services by category */
  "showDetailsOnClick": true, /* Click to expand the description */
  "serviceDefaultState": "wait", /* Default state (true - wait - false) */
  "showAlertSmall": false, /* Show the small banner on bottom right */
  "cookieslist": false, /* Show the cookie list */
  // "closePopup": true, /* Show a close X on the banner */
  "showIcon": true, /* Show cookie icon to manage cookies */
  "iconSrc": "", /* Optionnal: URL or base64 encoded image */
  "iconPosition": "BottomRight", /* Position of the cookie (BottomRight - BottomLeft - TopRight - TopLeft) */
  "adblocker": false, /* Show a Warning if an adblocker is detected */
  "DenyAllCta" : true, /* Show the deny all button */
  "AcceptAllCta" : true, /* Show the accept all button */
  "highPrivacy": true, /* HIGHLY RECOMMANDED Disable auto consent */
  "alwaysNeedConsent": false, /* Ask the consent for "Privacy by design" services */
  "handleBrowserDNTRequest": false, /* If Do Not Track == 1, disallow all */
  "removeCredit": false, /* Remove credit link */
  "moreInfoLink": true, /* Show more info link */
  "useExternalCss": false, /* Expert mode: do not load the tarteaucitron.css file */
  "useExternalJs": false, /* Expert mode: do not load the tarteaucitron js files */
  "cookieDomain": "", /* Shared cookie for multisite */
  "readmoreLink": "https://www.normandie.fr/protection-des-donnees-a-caractere-personnel", /* Change the default readmore link */
  "mandatory": true, /* Show a message about mandatory cookies */
  "mandatoryCta": false, /* Show the disabled accept button when mandatory on */
  "closePopup": false,
  "customCloserId": "", /* Optional a11y: Custom element ID used to open the panel */
  // "googleConsentMode": true, /* Enable Google Consent Mode v2 for Google ads & GA4 */
  //"bingConsentMode": true, /* Enable Bing Consent Mode for Clarity & Bing Ads */
  //"softConsentMode": false, /* Soft consent mode (consent is required to load the services) */
  //"dataLayer": false, /* Send an event to dataLayer with the services status */
  // "serverSide": false, /* Server side only, tags are not loaded client side */
  // "partnersList": true /* Show the number of partners on the popup/middle banner */
});

(tarteaucitron.job = tarteaucitron.job || []).push('youtube');

document.addEventListener("youtube_loaded", function(){
    var iframesYoutube = jQuery('iframe[src*="youtube"]');

    for (let cpt= 0; cpt < iframesYoutube.length; cpt++) {
        var styleOld = iframesYoutube[cpt].getAttribute('style');
        var styleNew = styleOld + 'border:0;';
        iframesYoutube[cpt].setAttribute('style', styleNew);
    }
});

tarteaucitron.user.matomoId = window.matomoConfig.matomo_site_id;
(tarteaucitron.job = tarteaucitron.job || []).push('matomocloud');

let u=(("https:" == document.location.protocol) ? window.matomoConfig.matomo_url_https : window.matomoConfig.matomo_url_http);
tarteaucitron.user.matomoHost = u;
tarteaucitron.user.matomoCustomJSPath = u + "matomo.js";
tarteaucitron.user.matomoDontTrackPageView = false;
tarteaucitron.user.matomoFullTracking = true;
