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

var protocol_host;
var progressFileName;

console.log('Hello Webpack Encore! Edit me in assets/js/app.js');
//


function showSpinner(){

	console.log('showSpinner !waouh');
	// $('body').css({ 'cursor': 'url("/public/surfer.gif"), wait, progress' });
	$('body').css({ 'cursor': 'wait' });

}

function setProgressBar(){
	console.log('setProgressBar()');
	$('#progress-bar-container').css({ "display": "flex" });

	progressFileName = protocol_host + '/percentProgress';
	console.log('nom du fichier de progress : ' + progressFileName );

	setInterval(renderProgressBar, 1000);

}

function unsetProgressBar(){
	console.log('unsetProgressBar()');
	$('#progress-bar-container').css({ "display": "none" });


}

 function renderProgressBar(){

	// console.log('1er fetch: ' + fileName);

	// fetch(fileName)
	// // .then(response => response.json())
	// .then((response) => response.text())
	// .then((data) => console.log('data 1er fetch: ' + data));
	const myInit = {
					method: 'GET',
					mode: 'cors',
					cache: 'no-store',
				};


	var myRequest = new Request(progressFileName, myInit);
	console.log(myRequest);
	// console.log('fetch: ' + fileName);
    fetch(myRequest)
    .then(function(response) {
    //   console.log('type: ' + response.type);
    //   console.log('url: ' + response.url);
    //   console.log('useFinalURL: ' + response.useFinalURL);
    //   console.log('status: ' + response.status);
    //   console.log('ok: ' + response.ok);
    //   console.log('statusText: ' + response.statusText);
    //   console.log('headers: ' + response.headers);
      if (!response.ok) {
		console.log("fetch error, status = " + response.status);
    	throw new Error("HTTP error, status = " + response.status);
      }
      return response.text();
    })
    .then(function(text) {

		console.log(text);
		$('#progress-bar').css('width', text);
		$('#progress-bar').text( text );

    })
    .catch(function(error) {
		console.log('Error: ' + error.message);
		alert('blik' + error.message);
	});




	// let response =  fetch(fileName);
	// console.log('response.ok: ' + response.ok);

	// if (response.ok) { // if HTTP-status is 200-299
	// 	// get the response body (the method explained below)
	// 	let text =  response.text();
	// 	console.log('text: ' + text);
	// } else {
		
		
	// 	console.log(response);
	// alert("HTTP-Error: " + response.status);
	// }
 


	// $.ajax({

	// 	url: protocol_host+'/api/book/getParsingProgress',
	// 	method: 'GET',
	// 	async: true,
	// 	cache: false,
	// 	dataType: 'json',
	// 	success: function(data)
	// 	{
	// 		console.log(data);
	// 		$('#progress-bar').css({ "width": data.parsingProgress });
	// 	},
	// 	error: function(object, error, errorThrown)
	// 	{
	// 		console.log('### error ** ' + error + ' (' + errorThrown + ')');
	// 	},
	// 	complete: function(object, status)
	// 	{
	// 		console.log('>> complete :-)' + status);
	// 	}
	// });

}

// $(document).ready(function(){
$(function () {

	//
	// init 
	//
	let href = $(location).attr('href');           // http://127.0.0.1:8000/book/new
	let host = $(location).attr('host');           // 127.0.0.1:8000
	let protocol = $(location).attr('protocol');   // http:	
	
	protocol_host = protocol + '//' + host;        // http://127.0.0.1:8000

	console.log('href: ' + href);
	console.log('protocol_host: ' + protocol_host);


	// Form/SentenceSearchType init
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

		showSpinner();
		console.log('click on ajout, protocol_host: ' + protocol_host);
		setTimeout(setProgressBar, 1000);

	});

	console.log('Document Ready !!');
	// alert();
});
