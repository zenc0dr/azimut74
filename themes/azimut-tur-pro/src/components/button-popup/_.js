export function lightPopup(e) {
   console.log(e.children().first())
   e.children().first().toggleClass("show");
}