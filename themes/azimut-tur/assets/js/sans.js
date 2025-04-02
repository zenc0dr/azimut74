document.addEventListener('DOMContentLoaded', function(){
	var sselimg = document.querySelectorAll('.s-sel__card-image'), w, h;
	for (var i=0;i<sselimg.length;i++) {
		w = sselimg[i].offsetWidth;
		h = w*0.75;
		sselimg[i].style.height = h + 'px';
	}
	
	var s_sw = document.querySelectorAll('.s-card__switch');
	var s_swc = document.querySelectorAll('.s-card__switcher-content-wrap .s-card__switcher-content');
	for(var i=0;i<s_sw.length;i++) {
		(function(i) {
			s_sw[i].onclick = function(){
				//console.log('aga');
				switchersClassRemove();
				this.classList.add('active');
				switcherContentClassRemove();
				s_swc[i].classList.add('active');
			};	
		})(i);
	}
	function switchersClassRemove() {
		for (var i=0;i<s_sw.length;i++) {
			s_sw[i].classList.remove('active');
		}
	}
	function switcherContentClassRemove() {
		for (var i=0;i<s_swc.length;i++) {
			s_swc[i].classList.remove('active');
		}
	}
	var scards = document.querySelectorAll('.s-card__big-image');
	for(var i=0;i<scards.length;i++) {
		var scard_w = scards[i].offsetWidth;
		scards[i].style.height = 0.789*scard_w + 'px';
	}

	var cardmore = document.querySelectorAll('.s-card__switcher-line_description .more-button');
	[].forEach.call(cardmore, function(item){
		item.onclick = function(){
			this.classList.toggle('active');
			this.previousElementSibling.classList.toggle('active');
		}
	})
	
	var roomsmore = document.querySelectorAll('.s-card__room-more .more-button');
	[].forEach.call(roomsmore, function(item){
		item.onclick = function(){
			this.classList.toggle('active');
			this.parentNode.parentNode.classList.toggle('active');
		}
	});
	
	var smimages = document.querySelectorAll('.s-card__small-images_add .btn');
	[].forEach.call(smimages, function(item){
		item.onclick = function(){
			this.parentNode.classList.toggle('active');
			this.parentNode.parentNode.classList.toggle('active');
		}
	});
	
	var roomsh = document.querySelectorAll('.s-card__room-name');
	[].forEach.call(roomsh, function(item){
		item.onclick = function() {
			this.classList.toggle('active');
			this.nextElementSibling.classList.toggle('active');
		}
	});
	
	var hash = window.location.hash;
	hash = hash.substr(1,hash.length);
	var swhash = document.querySelectorAll('.s-card__switch-el');
	[].forEach.call(swhash, function(el){
		if(hash.length>0 && hash.substr(0,2) == 'sw') {
			el.classList.remove('active');
			if(el.classList.contains(hash)) {
				el.classList.add('active');
			}
		}
	});
	
	var revbuttons = document.querySelectorAll('.s-sel__card-reviews--button');
	[].forEach.call(revbuttons, function(item){
		item.onclick = function(){
			hash = this.getAttribute('href');
			hash = hash.substr(1,hash.length);
			var revpositionY = document.querySelector('.'+hash+'').offsetTop - 100;
			var revpositionX = document.querySelector('.'+hash+'').offsetLeft;
			[].forEach.call(swhash, function(el){
				el.classList.remove('active');
				if(el.classList.contains(hash)) {
					el.classList.add('active');
				}
			});
			window.scrollTo(revpositionX, revpositionY);
		}
	})
	
	
	// Данная контсрукция перенсена в экземпляр Vue
	// var sel = document.querySelectorAll('div.s-sel__select');
	// [].forEach.call(sel, function(item){
	// 	item.children[0].onclick = function() {
	// 		if (this.parentNode.classList.contains('active')) {
	// 			this.parentNode.classList.remove('active');
	// 		} else {
	// 			for(var i=0;i<sel.length;i++) {
	// 				sel[i].classList.remove('active');
	// 			}
	// 			this.parentNode.classList.add('active');
	// 		}
	// 	}
	// 	var sel_items = item.children[2].children[0].children;
		
	// 	[].forEach.call(sel_items, function(el){
	// 		el.addEventListener('click', function(){
	// 			var attr = this.getAttribute('data-value');
	// 			if(this.parentNode.parentNode.parentNode.classList.contains('s-sel__select-checking')) {
	// 				if(this.children[0].checked === true) {
	// 					this.children[0].checked = false;
	// 				} else {
	// 					this.children[0].checked = true;
	// 				}
	// 				var inputs = this.parentNode.querySelectorAll('.s-sel__input');

	// 				function checkAll(elems) {
	// 					return [].filter.call(elems, function(ischeck){
	// 						checked = false;
	// 						if(ischeck.checked === true) {
	// 							checked = true;
	// 							return checked && ischeck;
	// 						}
	// 					});
	// 			    };
	// 			    var arr = [];
	// 			    checkAll(inputs).forEach(function(el){
	// 			    	var dataval = el.parentNode.dataset.value;
	// 			    	arr.push(dataval);
	// 			    });
	// 			    arr = arr.join(', ');
	// 				if (arr.length>0) {
	// 					this.parentNode.parentNode.parentNode.children[0].children[0].innerText = arr;
	// 					this.parentNode.parentNode.parentNode.children[0].children[0].setAttribute('title', arr);
	// 				} else {
	// 					//var label = this.parentNode.parentNode.parentNode.parentNode.children[0].innerText;
	// 					this.parentNode.parentNode.parentNode.children[0].children[0].innerText = 'Выберите профиль';
	// 					this.parentNode.parentNode.parentNode.children[0].children[0].setAttribute('title', 'Выберите профиль');
	// 				}

	// 			} else {
	// 				this.parentNode.parentNode.parentNode.children[1].setAttribute('value', attr);
	// 				this.parentNode.parentNode.parentNode.children[0].children[0].innerText = attr;
	// 				item.classList.remove('active');
	// 				//alert(this.parentNode.parentNode.parentNode.children[1].getAttribute('value'))
	// 			}
	// 		});
	// 	});
	// });
	
	
	
	// document.addEventListener('mouseup', function(){
		
	// });

});

// $(document).mouseup(function (e) {
//     var cont = $('div.s-sel__select');
//     if (!cont.is(e.target) && cont.has(e.target).length == 0) {
//         cont.removeClass('active');
//     }
// });

/*$(function () {
  $('.s-sel__select_curort').click(function(){
     // $('.main-menu__list-drop').fadeIn(100);
    $('.s-sel__select-list').slideToggle(100);
  })
  $('.s-sel__select-list ul li').click(function(){
    var txt = $(this).text();
    $('.s-sel__select-value').text(txt);
  })
  $('.s-sel__select_curort').mouseleave(function(){
    $('.s-sel__select-list').slideUp(100);
  })
});*/

$(document).ready(function(){
	var scard_owl = $('.s-card__big-images');
	scard_owl.owlCarousel({
		loop: false,
        margin: 0,
        items: 1,
        onDragged: highlighting,
        onInitialized: highlighting,
        onTranslated: highlighting,
	});
	// $('.s-card__big-images').magnificPopup({
	// 	delegate: 'a',
	// 	type: 'image',
	// 	gallery: {
	// 		enabled: true
	// 	},
	// })

	function highlighting() {
		var hash = $('.s-card__big-images .owl-item.active .s-card__big-image').attr('data-hash');
		//console.log(hash);
		$('.s-card__small-images a .s-card__small-image').removeClass('active');
		$('.s-card__small-images a[href="#'+hash+'"]').find('.s-card__small-image').addClass('active');
	}
	// $('.s-card__room-images div div').magnificPopup({
	// 	delegate: 'div',
	// 	type: 'image',
	// 	gallery: {
	// 		enabled: true
	// 	},
	// });
	
 //   $('.sliderNav .next').click(function (){
 //       cardimgs.trigger('next.owl.carousel');
 //   });
 //   $('.sliderNav .prev').click(function (){
 //       cardimgs.trigger('prev.owl.carousel');
 //   });
});
// $(document).on('click', '.s-card__small-images_add .btn', function(){
// 	$('.s-card__small-images > div > :nth-child(n+12)').slideToggle(150);
// 	$(this).parent().toggleClass('active');
// });
$(document).on('click', '.s-card__small-images a', function(){
	$('.s-card__small-images a').children().removeClass('active');
	$(this).children().addClass('active');
});
$(document).on('click', '.s-sel__button-more .btn', function(){
	$(this).closest('.s-sel__line').next().toggleClass('active');
});



