/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import '../styles/app.scss';
// require('../css/app.scss');

// Need jQuery? Install it with "yarn add jquery", then uncomment to import it.
// var $ = require('jquery');
import $ from 'jquery';


// require('popper.js');
import 'popper.js';

// require('bootstrap');
import 'bootstrap';

import 'select2';

console.log('Hello Webpack Encore! Edit me in assets/js/app.js');
//


// $('button#main_search').on('click', function(e)
// $('button#main_search').addEventListener('click', function(e)
// $('button#main_search').on('click', function(e)
// {
// 	console.log('===== showSpinner ====', e.target );
// 	return true;
	
// });

function showSpinner(){

	console.log('showSpinner !waouh');

	// $('#search_button').append('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span class="sr-only">Rechercher...</span>');
	// alert('showSpinner');
}

function setProgressBar(){
	console.log('setProgressBar()');
	$('#progress-bar-container').css({ "display": "flex" });
}

function unsetProgressBar(){
	console.log('unsetProgressBar()');
	$('#progress-bar-container').css({ "display": "none" });
}

function renderProgressBar(){

	// $_HTTP_HOST ??

	console.log($(location).attr('href'));
	console.log($(location).attr('host')); // 127.0.0.1:8000
	console.log($(location).attr('protocol')); // http

	$.ajax({

		url: 'http://127.0.0.1:8000/api/book/getParsingProgress',
		// url: 'http://127.0.0.1:8000/api/book/getParsingProgress',
		method: 'GET',
		async: true,
		cache: false,
		dataType: 'json',
		success: function(data)
		{
			console.log(data);
			$('#progress-bar').css({ "width": data.parsingProgress });
		},
		error: function(object, error, errorThrown)
		{
			console.log('### error ** ' + error + ' (' + errorThrown + ')');
		},
		complete: function(object, status)
		{
			console.log('>> complete :-)' + status);
		}
	});
	

	


}

// $(document).ready(function(){
$(function () {

	// Form/SentenceSearchType
	$('#sentence_search_books').select2({
		width: '100%',
		placeholder: 'parmi les oeuvres ...',
	});
	$('#sentence_search_authors').select2({
		width: '100%',
		placeholder: 'parmi les auteurs ...',
	});
	
	$('.button-new-update').on('click', function(event) {
		event.stopPropagation();
		event.stopImmediatePropagation();

		console.log('click on button-new-update');
		setProgressBar();
		renderProgressBar();
	});



	console.log('Document Ready !!');
	// alert();
});
