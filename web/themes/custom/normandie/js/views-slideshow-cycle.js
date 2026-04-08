/**
 * @file
 * Views Slideshow Cycle compatibility layer for D10
 * 
 * This script replicates the D7 Views Slideshow Cycle behavior in Drupal 10
 * Rotates through slides with fade effect and pager controls (D7-like dots)
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.viewsSlideshowCycle = {
    attach: function (context, settings) {
      
      var element = context.querySelector ? context.querySelector('#views_slideshow_cycle_main_liste_actus-block_1') : null;
      
      if (!element) {
        element = document.querySelector('#views_slideshow_cycle_main_liste_actus-block_1');
      }
      
      if (!element) {
        return;
      }
            
      // Check if already initialized
      if (element.getAttribute('data-slideshow-init') === 'true') {
        return;
      }
      element.setAttribute('data-slideshow-init', 'true');
      
      var teaserSection = element.querySelector('#views_slideshow_cycle_teaser_section_liste_actus-block_1');
      if (!teaserSection) {
        return;
      }
      
      var slides = teaserSection.querySelectorAll('.views_slideshow_cycle_slide');
      
      if (slides.length === 0) {
        return;
      }
      
      // Make sure slides are visible initially
      slides.forEach(function(slide, idx) {
        if (!slide.id) {
          slide.id = 'slide_' + idx;
        }
        // Set initial state
        if (idx === 0) {
          slide.classList.add('active');
          slide.style.opacity = '1';
          slide.style.visibility = 'visible';
          slide.style.display = 'block';
          slide.style.zIndex = '4';
        } else {
          slide.classList.remove('active');
          slide.style.opacity = '0';
          slide.style.visibility = 'hidden';
          slide.style.display = 'none';
          slide.style.zIndex = '3';
        }
      });
      
      var pagerContainer = document.querySelector('#widget_pager_bottom_liste_actus-block_1');
      if (!pagerContainer) {
        return;
      }
      
      // Clear and create pager
      pagerContainer.innerHTML = '';
      var pagerItems = [];
      
      slides.forEach(function(slide, index) {
        var pagerItem = document.createElement('div');
        pagerItem.className = 'views-slideshow-pager-field-item';
        if (index === 0) {
          pagerItem.classList.add('active');
        }
        
        var link = document.createElement('a');
        link.href = '#';
        link.textContent = (index + 1);
        link.style.display = 'inline-block';
        link.style.width = '13px';
        link.style.height = '13px';
        link.style.borderRadius = '50%';
        link.style.background = index === 0 ? '#949292' : '#f2f2f2';
        link.style.cursor = 'pointer';
        link.style.margin = '0 4px';
        
        pagerItem.appendChild(link);
        pagerContainer.appendChild(pagerItem);
        pagerItems.push({item: pagerItem, link: link});
      });
            
      var currentSlide = 0;
      var autoplayInterval;
      var isHovering = false;
      
      function showSlide(index) {        
        slides.forEach(function(slide, i) {
          if (i === index) {
            slide.classList.add('active');
            slide.style.opacity = '1';
            slide.style.visibility = 'visible';
            slide.style.display = 'block';
            slide.style.zIndex = '4';
          } else {
            slide.classList.remove('active');
            slide.style.opacity = '0';
            slide.style.visibility = 'hidden';
            slide.style.display = 'none';
            slide.style.zIndex = '3';
          }
        });
        
        pagerItems.forEach(function(pager, i) {
          if (i === index) {
            pager.item.classList.add('active');
            pager.link.style.background = '#949292';
          } else {
            pager.item.classList.remove('active');
            pager.link.style.background = '#f2f2f2';
          }
        });
        
        // Update container height
        var activeSlide = slides[index];
        if (activeSlide && teaserSection) {
          teaserSection.style.height = activeSlide.scrollHeight + 'px';
        }
        
        currentSlide = index;
      }
      
      function nextSlide() {
        var next = (currentSlide + 1) % slides.length;
        showSlide(next);
      }
      
      function startAutoplay() {
        if (autoplayInterval) return;
        autoplayInterval = setInterval(function() {
          if (!isHovering) {
            nextSlide();
          }
        }, 5000);
      }
      
      function stopAutoplay() {
        if (autoplayInterval) {
          clearInterval(autoplayInterval);
          autoplayInterval = null;
        }
      }
      
      // Pager click handlers
      pagerItems.forEach(function(pager, index) {
        pager.link.addEventListener('click', function(e) {
          e.preventDefault();
          stopAutoplay();
          showSlide(index);
          startAutoplay();
        });
      });
      
      // Hover handlers
      element.addEventListener('mouseenter', function() {
        isHovering = true;
        stopAutoplay();
      });
      
      element.addEventListener('mouseleave', function() {
        isHovering = false;
        startAutoplay();
      });
      
      // Initialize
      showSlide(0);
      startAutoplay();
    }
  };
})(Drupal, once);
