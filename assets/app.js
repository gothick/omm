/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import 'bootstrap/dist/css/bootstrap.css';
import '@fortawesome/fontawesome-free/css/all.css';

// start the Stimulus application
// TODO: This is confusing and has fuck all to do with actual Bootstrap so I
// should probably remove it.
// import './bootstrap';

const $ = require('jquery');
require('bootstrap');
require('@fortawesome/fontawesome-free/js/fontawesome.js');
require('@fortawesome/fontawesome-free/js/all');
