define(['text!templates/comic_slot.html'], function (template) {
	window.widget = window.widget || {};
	window.widget.comic_slot = _.template(template);
});