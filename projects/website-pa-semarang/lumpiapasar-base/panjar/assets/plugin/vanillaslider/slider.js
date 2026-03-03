//Set slide time interval:

const slidetime = 5000;
//https://stackoverflow.com/questions/8126466/how-do-i-reset-the-setinterval-timer  
function Timer(fn, t) {
    var timerObj = setInterval(fn, t);
    this.stop = function() {
        if (timerObj) {
            clearInterval(timerObj);
            timerObj = null;
        }
        return this;
    }
    // start timer using current settings (if it's not already running)
    this.start = function() {
        if (!timerObj) {
            this.stop();
            timerObj = setInterval(fn, t);
        }
        return this;
    }
    // start with new interval, stop current interval
    this.reset = function(newT) {
        t = newT;
        return this.stop().start();
    }
}
const timer = new Timer(function(){nextSlide();}, slidetime);

//Button Next Slide

function nextSlide(){
  var active = document.querySelectorAll('li.active')[0],
        activeslide = document.querySelectorAll('.slides div.active')[0],
        number = active.dataset.link.split("-",2)[1],
        length = document.querySelectorAll(".slide-controller li").length;

  active.className = active.className.replace('active', '');
  activeslide.className = activeslide.className.replace('active', '');

  if( number < length){
      active.nextElementSibling.className += ' active';
      activeslide.nextElementSibling.className += ' active';
  } else {
      document.querySelectorAll(".slide-controller li")[0].className += ' active';
      document.getElementById("slide-1").className += ' active';
  }
  timer.reset(slidetime);
  
}
const button_next = document.querySelectorAll(".arrow-button-right")[0];
button_next.addEventListener('click', nextSlide);

//Button Previous Slide

function previousSlide(){
  var active = document.querySelectorAll('li.active')[0],
        activeslide = document.querySelectorAll('.slides div.active')[0],
        number = active.dataset.link.split("-",2)[1],
        length = document.querySelectorAll(".slide-controller li").length;

  active.className = active.className.replace('active', '');
  activeslide.className = activeslide.className.replace('active', '');

  if( number > length){
      active.previousElementSibling.className += ' active';
      activeslide.previousElementSibling.className += ' active';
  } else {
      document.querySelectorAll(".slide-controller li")[0].className += ' active';
      document.getElementById("slide-1").className += ' active';
  }
 timer.reset(slidetime);
}
const button_previous = document.querySelectorAll(".arrow-button-left")[0];
button_previous.addEventListener('click', previousSlide);

// Slide Circle Indicators

clicks = document.querySelectorAll(".slide-controller li");
for (each of clicks) {

	each.addEventListener('click', (e) => {
			var el = e.target,
					active = document.querySelectorAll('.active');
			for (each of active) {
					 each.className = each.className.replace('active', '');
			}
			el.className += ' active';
			document.getElementById(el.dataset.link).className += ' active';
 timer.reset(slidetime);
	});

}


