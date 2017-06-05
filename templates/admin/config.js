"use strict";

module.exports = {
	css: [
		"custom/bootstrap-pre.css",
		"./node_modules/bootstrap/dist/css/bootstrap.css",
		"custom/bootstrap-post.css"
	],
	scss: [],
	font: [
		"./node_modules/bootstrap/fonts/*",
		"github/Font-Awesome/fonts/*"
	],
	js: [
		"custom/bootstrap-pre.js",
		"custom/jquery.min.js",
		"./node_modules/bootstrap/dist/js/bootstrap.js",
		"custom/bootstrap-post.js",
	],
	copy: {
		"css": [
			"css/alert.css",
			"css/datepicker.css",
			"css/datetimepicker.css",
			{
				"bootstrap-theme.min.css": "./node_modules/bootstrap/dist/css/bootstrap-theme.css",
				"font-awesome.min.css": "github/Font-Awesome/css/font-awesome.css",
				"colorpicker.css": "github/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.css"
			}
		],
		"js": [{
			"alert.js": "js/alert.js",
			"datepicker.js": "js/datepicker.js",
			"datetimepicker.js": "js/datetimepicker.js",
			"rating.js": "js/rating.js",
			"colorpicker.js": "github/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.js"
		}],
		"img": [
			"img/spinner.gif",
			"github/bootstrap-colorpicker/dist/img/**"
		]
	},
	source: "/Users/me/Sites/php/bootstrap/",
	dest: {
		path: __dirname + "/bootstrap/",
		css: "bootstrap.min.css",
		js: "bootstrap.min.js"
	},
	jscompress : 2, // 1=uglify, 2=packer
	watch : 0 // 1=recompile when changes, 0=compile only
}