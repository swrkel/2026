var scroll = new SmoothScroll('a[href*="#"]');
function closeCookie() {
    // Guarded: not every page has a cookie box, and calling this where there
    // is none would throw.
    var cookieBox = document.getElementById("cookieBox");
    if (cookieBox) {
        cookieBox.innerHTML = "";
    }
}

// Current year.
//
// This was an unguarded assignment. Pages without a #year element - the login
// page among them - got "Cannot set properties of null" thrown at the TOP LEVEL
// of this file. Everything after it stopped, and scripts loaded afterwards
// could fail to run at all. On the login page that silently disabled the banner
// carousel: two slides, correct durations, no rotation.
var yearEl = document.getElementById("year");
if (yearEl) {
    yearEl.innerHTML = new Date().getFullYear();
}


(function($) {
    "use strict";
var openmodal = document.querySelectorAll('.modal-open')
    for (var i = 0; i < openmodal.length; i++) {
      openmodal[i].addEventListener('click', function(event){
    	event.preventDefault()
    	toggleModal()
      })
    }
    
    const overlay = document.querySelector('.modal-overlay')
    overlay.addEventListener('click', toggleModal)
    
    var closemodal = document.querySelectorAll('.modal-close')
    for (var i = 0; i < closemodal.length; i++) {
      closemodal[i].addEventListener('click', toggleModal)
    }
    
    document.onkeydown = function(evt) {
      evt = evt || window.event
      var isEscape = false
      if ("key" in evt) {
    	isEscape = (evt.key === "Escape" || evt.key === "Esc")
      } else {
    	isEscape = (evt.keyCode === 27)
      }
      if (isEscape && document.body.classList.contains('modal-active')) {
    	toggleModal()
      }
    };
    
    
    function toggleModal () {
      const body = document.querySelector('body')
      const modal = document.querySelector('.modal')
      modal.classList.toggle('opacity-0')
      modal.classList.toggle('pointer-events-none')
      body.classList.toggle('modal-active')
    }
})(jQuery);