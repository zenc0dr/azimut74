(self["webpackChunkazimut_tur_pro"] = self["webpackChunkazimut_tur_pro"] || []).push([["/js/references"],{

/***/ "./src/components/result-menu/left-menu.js":
/*!*************************************************!*\
  !*** ./src/components/result-menu/left-menu.js ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
var leftMenu = {
  last_width: 0,
  changeWidth: function changeWidth(width) {
    if (this.last_width) {
      var diff = Math.abs(this.last_width - width);
      this.last_width = width;

      if (diff < 20) {
        return;
      }
    }

    this.last_width = width;

    if (width < 992) {
      this.closeAllChapters();
    } else {
      this.openAllChapters();
    }
  },
  closeAllChapters: function closeAllChapters() {
    $('.left-menu__chapter__items').hide();
    $('.left-menu__chapter__title').addClass('closed');
  },
  openAllChapters: function openAllChapters() {
    $('.left-menu__chapter__items').show();
    $('.left-menu__chapter__title').removeClass('closed');
  }
};
$(document).ready(function () {
  leftMenu.changeWidth($('body').width());
  $('.left-menu').show();
});
$(window).resize(function () {
  leftMenu.changeWidth($('body').width());
});
$(document).on('click', '.left-menu__chapter__title', function () {
  if ($(this).next('.left-menu__chapter__items').css('display') === 'block') {
    $(this).next().slideUp(100);
    $(this).addClass('closed');
  } else {
    $(this).next().slideDown(100);
    $(this).removeClass('closed');
  }
});

/***/ }),

/***/ "./src/pages/references/references.js":
/*!********************************************!*\
  !*** ./src/pages/references/references.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _src_components_result_menu_left_menu_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @src/components/result-menu/left-menu.js */ "./src/components/result-menu/left-menu.js");
/* harmony import */ var _src_components_result_menu_left_menu_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_src_components_result_menu_left_menu_js__WEBPACK_IMPORTED_MODULE_0__);


/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["/js/vendor"], () => (__webpack_exec__("./src/pages/references/references.js")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiL2pzL3JlZmVyZW5jZXMuanMiLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7O0FBQUEsSUFBSUEsUUFBUSxHQUFHO0VBQ1hDLFVBQVUsRUFBRSxDQUREO0VBRVhDLFdBRlcsdUJBRUNDLEtBRkQsRUFHWDtJQUNJLElBQUksS0FBS0YsVUFBVCxFQUFxQjtNQUNqQixJQUFJRyxJQUFJLEdBQUdDLElBQUksQ0FBQ0MsR0FBTCxDQUFTLEtBQUtMLFVBQUwsR0FBa0JFLEtBQTNCLENBQVg7TUFDQSxLQUFLRixVQUFMLEdBQWtCRSxLQUFsQjs7TUFDQSxJQUFJQyxJQUFJLEdBQUcsRUFBWCxFQUFlO1FBQ1g7TUFDSDtJQUNKOztJQUVELEtBQUtILFVBQUwsR0FBa0JFLEtBQWxCOztJQUVBLElBQUlBLEtBQUssR0FBRyxHQUFaLEVBQWlCO01BQ2IsS0FBS0ksZ0JBQUw7SUFDSCxDQUZELE1BR0s7TUFDRCxLQUFLQyxlQUFMO0lBQ0g7RUFDSixDQXBCVTtFQXFCWEQsZ0JBckJXLDhCQXNCWDtJQUNJRSxDQUFDLENBQUMsNEJBQUQsQ0FBRCxDQUFnQ0MsSUFBaEM7SUFDQUQsQ0FBQyxDQUFDLDRCQUFELENBQUQsQ0FBZ0NFLFFBQWhDLENBQXlDLFFBQXpDO0VBRUgsQ0ExQlU7RUEyQlhILGVBM0JXLDZCQTRCWDtJQUNJQyxDQUFDLENBQUMsNEJBQUQsQ0FBRCxDQUFnQ0csSUFBaEM7SUFDQUgsQ0FBQyxDQUFDLDRCQUFELENBQUQsQ0FBZ0NJLFdBQWhDLENBQTRDLFFBQTVDO0VBQ0g7QUEvQlUsQ0FBZjtBQWtDQUosQ0FBQyxDQUFDSyxRQUFELENBQUQsQ0FBWUMsS0FBWixDQUFrQixZQUFZO0VBQzFCZixRQUFRLENBQUNFLFdBQVQsQ0FBcUJPLENBQUMsQ0FBQyxNQUFELENBQUQsQ0FBVU4sS0FBVixFQUFyQjtFQUNBTSxDQUFDLENBQUMsWUFBRCxDQUFELENBQWdCRyxJQUFoQjtBQUNILENBSEQ7QUFLQUgsQ0FBQyxDQUFDTyxNQUFELENBQUQsQ0FBVUMsTUFBVixDQUFpQixZQUFXO0VBQ3hCakIsUUFBUSxDQUFDRSxXQUFULENBQXFCTyxDQUFDLENBQUMsTUFBRCxDQUFELENBQVVOLEtBQVYsRUFBckI7QUFDSCxDQUZEO0FBSUFNLENBQUMsQ0FBQ0ssUUFBRCxDQUFELENBQVlJLEVBQVosQ0FBZSxPQUFmLEVBQXdCLDRCQUF4QixFQUF1RCxZQUFZO0VBRS9ELElBQUlULENBQUMsQ0FBQyxJQUFELENBQUQsQ0FBUVUsSUFBUixDQUFhLDRCQUFiLEVBQTJDQyxHQUEzQyxDQUErQyxTQUEvQyxNQUE4RCxPQUFsRSxFQUEyRTtJQUN2RVgsQ0FBQyxDQUFDLElBQUQsQ0FBRCxDQUFRVSxJQUFSLEdBQWVFLE9BQWYsQ0FBdUIsR0FBdkI7SUFDQVosQ0FBQyxDQUFDLElBQUQsQ0FBRCxDQUFRRSxRQUFSLENBQWlCLFFBQWpCO0VBQ0gsQ0FIRCxNQUlLO0lBQ0RGLENBQUMsQ0FBQyxJQUFELENBQUQsQ0FBUVUsSUFBUixHQUFlRyxTQUFmLENBQXlCLEdBQXpCO0lBQ0FiLENBQUMsQ0FBQyxJQUFELENBQUQsQ0FBUUksV0FBUixDQUFvQixRQUFwQjtFQUNIO0FBQ0osQ0FWRCIsInNvdXJjZXMiOlsid2VicGFjazovL2F6aW11dC10dXItcHJvLy4vc3JjL2NvbXBvbmVudHMvcmVzdWx0LW1lbnUvbGVmdC1tZW51LmpzIiwid2VicGFjazovL2F6aW11dC10dXItcHJvLy4vc3JjL3BhZ2VzL3JlZmVyZW5jZXMvcmVmZXJlbmNlcy5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyJsZXQgbGVmdE1lbnUgPSB7XHJcbiAgICBsYXN0X3dpZHRoOiAwLFxyXG4gICAgY2hhbmdlV2lkdGgod2lkdGgpXHJcbiAgICB7XHJcbiAgICAgICAgaWYgKHRoaXMubGFzdF93aWR0aCkge1xyXG4gICAgICAgICAgICBsZXQgZGlmZiA9IE1hdGguYWJzKHRoaXMubGFzdF93aWR0aCAtIHdpZHRoKVxyXG4gICAgICAgICAgICB0aGlzLmxhc3Rfd2lkdGggPSB3aWR0aFxyXG4gICAgICAgICAgICBpZiAoZGlmZiA8IDIwKSB7XHJcbiAgICAgICAgICAgICAgICByZXR1cm5cclxuICAgICAgICAgICAgfVxyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgdGhpcy5sYXN0X3dpZHRoID0gd2lkdGhcclxuXHJcbiAgICAgICAgaWYgKHdpZHRoIDwgOTkyKSB7XHJcbiAgICAgICAgICAgIHRoaXMuY2xvc2VBbGxDaGFwdGVycygpXHJcbiAgICAgICAgfVxyXG4gICAgICAgIGVsc2Uge1xyXG4gICAgICAgICAgICB0aGlzLm9wZW5BbGxDaGFwdGVycygpXHJcbiAgICAgICAgfVxyXG4gICAgfSxcclxuICAgIGNsb3NlQWxsQ2hhcHRlcnMoKVxyXG4gICAge1xyXG4gICAgICAgICQoJy5sZWZ0LW1lbnVfX2NoYXB0ZXJfX2l0ZW1zJykuaGlkZSgpXHJcbiAgICAgICAgJCgnLmxlZnQtbWVudV9fY2hhcHRlcl9fdGl0bGUnKS5hZGRDbGFzcygnY2xvc2VkJylcclxuXHJcbiAgICB9LFxyXG4gICAgb3BlbkFsbENoYXB0ZXJzKClcclxuICAgIHtcclxuICAgICAgICAkKCcubGVmdC1tZW51X19jaGFwdGVyX19pdGVtcycpLnNob3coKVxyXG4gICAgICAgICQoJy5sZWZ0LW1lbnVfX2NoYXB0ZXJfX3RpdGxlJykucmVtb3ZlQ2xhc3MoJ2Nsb3NlZCcpXHJcbiAgICB9XHJcbn1cclxuXHJcbiQoZG9jdW1lbnQpLnJlYWR5KGZ1bmN0aW9uICgpIHtcclxuICAgIGxlZnRNZW51LmNoYW5nZVdpZHRoKCQoJ2JvZHknKS53aWR0aCgpKVxyXG4gICAgJCgnLmxlZnQtbWVudScpLnNob3coKVxyXG59KVxyXG5cclxuJCh3aW5kb3cpLnJlc2l6ZShmdW5jdGlvbigpIHtcclxuICAgIGxlZnRNZW51LmNoYW5nZVdpZHRoKCQoJ2JvZHknKS53aWR0aCgpKVxyXG59KVxyXG5cclxuJChkb2N1bWVudCkub24oJ2NsaWNrJywgJy5sZWZ0LW1lbnVfX2NoYXB0ZXJfX3RpdGxlJywgIGZ1bmN0aW9uICgpIHtcclxuXHJcbiAgICBpZiAoJCh0aGlzKS5uZXh0KCcubGVmdC1tZW51X19jaGFwdGVyX19pdGVtcycpLmNzcygnZGlzcGxheScpID09PSAnYmxvY2snKSB7XHJcbiAgICAgICAgJCh0aGlzKS5uZXh0KCkuc2xpZGVVcCgxMDApXHJcbiAgICAgICAgJCh0aGlzKS5hZGRDbGFzcygnY2xvc2VkJylcclxuICAgIH1cclxuICAgIGVsc2Uge1xyXG4gICAgICAgICQodGhpcykubmV4dCgpLnNsaWRlRG93bigxMDApXHJcbiAgICAgICAgJCh0aGlzKS5yZW1vdmVDbGFzcygnY2xvc2VkJylcclxuICAgIH1cclxufSlcclxuIiwiaW1wb3J0ICdAc3JjL2NvbXBvbmVudHMvcmVzdWx0LW1lbnUvbGVmdC1tZW51LmpzJ1xyXG4iXSwibmFtZXMiOlsibGVmdE1lbnUiLCJsYXN0X3dpZHRoIiwiY2hhbmdlV2lkdGgiLCJ3aWR0aCIsImRpZmYiLCJNYXRoIiwiYWJzIiwiY2xvc2VBbGxDaGFwdGVycyIsIm9wZW5BbGxDaGFwdGVycyIsIiQiLCJoaWRlIiwiYWRkQ2xhc3MiLCJzaG93IiwicmVtb3ZlQ2xhc3MiLCJkb2N1bWVudCIsInJlYWR5Iiwid2luZG93IiwicmVzaXplIiwib24iLCJuZXh0IiwiY3NzIiwic2xpZGVVcCIsInNsaWRlRG93biJdLCJzb3VyY2VSb290IjoiIn0=