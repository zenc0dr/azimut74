document.addEventListener('DOMContentLoaded', function(){
	var stars = document.querySelectorAll('.stars-select span');
	
	function nextAll(elem) {
	    var next = false;
	    return [].filter.call(elem.parentNode.children, function(child) {
	        if (child === elem) next = true;
	        return next && child !== elem
	    })
	};
	
	function prevAll(elem) {
	    var prev = true;
	    return [].filter.call(elem.parentNode.children, function(child) {
	        if (child === elem) prev = false;
	        return prev && child !== elem
	    })
	};
	
	[].forEach.call(stars, function(item){
		item.addEventListener('mouseover', function(){
			if (this.classList.contains('active')) {
				nextAll(this).forEach(function(el){
					el.classList.remove('active');
				});
			} else {
				this.classList.add('active');
				prevAll(this).forEach(function(el){
					el.classList.add('active');
				});
			}
		});
		item.addEventListener('mouseout', function(){
			if (this.parentNode.classList.contains('chosen')) {
				this.classList.add('active');
				prevAll(this).forEach(function(el){
					el.classList.add('active');
				});
			} else {
				this.classList.remove('active');
				prevAll(this).forEach(function(el){
					el.classList.remove('active');
				});
				nextAll(this).forEach(function(el){
					el.classList.remove('active');
				});
			}
		});
		item.addEventListener('click', function(){
			var active_count;
			this.parentNode.classList.add('chosen');
			prevAll(this).forEach(function(el){
				el.classList.add('active');
			});
			childrens = this.parentNode.children;
			function countChildrens() {
				var child = false;
				return [].filter.call(childrens, function(child){
					if(child.classList.contains('active')) {
						child = true;
					} else {
						child = false;
					}
					return child;
				})
			}
			active_count = countChildrens().length;
			this.parentNode.nextElementSibling.children[0].innerText = active_count;
			this.parentNode.nextElementSibling.children[0].dataset.count = active_count;
		});
	});

});