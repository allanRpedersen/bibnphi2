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

//
// import './select_liste_deroulante.js';

//
//
// const fs = require('fs')

// import { readFile } from 'fs/promises';

// import fs from 'fs';

  
  // require('dotenv').config();
  
  // import dotenv from 'dotenv';
  // dotenv.config();
  
  var protocol_host;
  var progressFileName;
  
  console.log('Hello Webpack Encore! Edit me in assets/js/app.js');
  // console.log( process );
  // console.log( process.env );
  // console.log( process.env.DATABASE_URL );  undefined !!-/
  //
  

//   function showSpinner(){

// 	console.log('showSpinner !waouh');
// 	// $('body').css({ 'cursor': 'url("/public/surfer.gif"), wait, progress' });
// 	$('body').css({ 'cursor': 'wait' });

// }

function setProgressBar(){

	$('#progress-bar-container').css({ "display": "flex" });

	progressFileName = protocol_host + '/percentProgress.log';
	console.log('setProgressBar, avec le fichier de progress : ' + progressFileName );

	setInterval(renderProgressBar, 1000); // en millisecondes

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


	// var myHeaders = new Headers({
	// 	"Content-Type": "text/plain",
	// 	// "Content-Length": 4,
	//   });
	
	var myInit = {
					method: 'GET',
					headers: {
						"Content-Type": "text/plain",
					},
					mode: 'same-origin',
					cache: 'no-store',
				};


	var myRequest = new Request(progressFileName, myInit);
	// console.log(myRequest);
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
		alert('blik blik :-<> ' + error.message);
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

	$('.book-row').on('click', function(event){
		$('body').css('cursor', 'wait');
		$('button').css('cursor', 'wait');
		$('.book-row').css('cursor', 'wait');
	});

	$('.book-card').on('click', function(event){
		$('body').css('cursor', 'wait');
		$('.book-card').css('cursor', 'wait');
	});


	$('#user_userRoles').select2({
		width: '17%',
	});

	$('#book_author').select2({
		width: '100%',
	});

	$('#book-search-button').on('click', function(event){
		if ($('#book-selectform').css('display') === 'none'){

			$('#book-selectform').css('display', 'block');

			// Form/BookSelectType init
			$('#book_select_authors').select2({
				width: '100%',
				placeholder: 'du ou des philosophes ...',

			});
			$('#book_select_authors').select2('focus');


			$('#book_select_books').select2({
				width: '100%',
				placeholder: 'et/ou par titre ...',
			})
		}
		else {
			$('#book-selectform').css('display', 'none');
		}
	})

	$('#sentence-search-button').on('click', function(event){


		// $('#frontpage-searchform').toggle();
		if ($('#frontpage-searchform').css('display') === 'none'){

			// $('#frontpage-leftside').css('width', '85%');
			// $('#frontpage-rightside').css('width', '15%');
			// $('#frontpage-rightside').css('display', 'block');

			$('#frontpage-searchform').css('display', 'block');
			$('#sentence-search-button').css('display', 'none');

			$('#sentence_search_stringToSearch').trigger('focus');

			// Form/SentenceSearchType init
			// $('#sentence_search_books').select2({
			// 	width: '100%',
			// 	placeholder: 'parmi les oeuvres ...',
			// });
			// $('#sentence_search_authors').select2({
			// 	width: '100%',
			// 	placeholder: 'parmi les auteurs ...',
			// });
		}
		else {
			// $('#frontpage-rightside').css('width', '0%');
			// $('#frontpage-leftside').css('width', '100%');
			// $('#frontpage-rightside').css('display', 'none');

			$('#frontpage-searchform').css('display', 'none');

		}

	})

	//
	$('#search_button').on('click', function(event){
		console.log('click on .search-button !!')

		// showSpinner();
		let el = document.getElementById("search_button");
		el.innerHTML = 'En cours ';
		el.innerHTML += '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
		el.innerHTML += '<span class="sr-only">Rechercher...</span>';
		//
		$('body').css('cursor', 'wait');
	})

	$('#author_search_button').on('click', function(event){
		console.log('click on author-search-button !!')
		$('body').css('cursor', 'wait');
	})

	
	$('.button-new-update').on('click', function(event) {
		event.stopPropagation();
		event.stopImmediatePropagation();

		console.log('click on button-new-update');

		$('body').css({ 'cursor': 'wait' });
		$('.button-new-update').css({ 'cursor': 'wait' });
		console.log('click on ajout, protocol_host: ' + protocol_host);
		// setTimeout(setProgressBar, 1000);
		setProgressBar();

	});


	$('#log-button').on('click', function(event) {

		
		let logFileName = protocol_host + '/bibnphi.log';
		let params = `scrollbars=no,
						resizable=no,
						status=no,
						location=no,
						toolbar=no,
						menubar=no,
						width=900,
						height=500,
						left=100,
						top=100`;
						
		console.log('#log-button - opening ' + logFileName);
		
		window.open(logFileName, logFileName, params);

// 		fetch(logFileName)
// 			.then(
// 				function(response) {
// 				if (response.status !== 200) {
// 					console.log('Looks like there was a problem. Status Code: ' +
// 					response.status);
// 					return;
// 				}

// 				// Examine the text in the response
// 				response.text().then(function(data) {
// 					console.log(data);
// 				});
// 				}
// 			)
// 			.catch(function(err) {
// 				console.log('Fetch Error :-S', err);
//   });


	});

	//
	//
	console.log('Document Ready !!');
	// alert();
});
