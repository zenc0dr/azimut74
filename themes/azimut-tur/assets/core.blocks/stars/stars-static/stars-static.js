document.addEventListener('DOMContentLoaded', function(){
	var starsstatic = document.querySelectorAll('.stars-static');
	[].forEach.call(starsstatic, function(item){
		var rt = item.dataset.rating;
		item.children[0].style.width = rt/5 * 100 + '%';
	});
});