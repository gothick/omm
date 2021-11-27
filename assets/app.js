/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
//import 'bootstrap/dist/css/bootstrap.css';
// import '@fortawesome/fontawesome-free/css/all.css';
// Still need Font Awesome for the Locate leaflet pin icon. TODO: Really slim this down
// for the front-end.
import './styles/app.scss';

import '@fortawesome/fontawesome-free/css/solid.css';
import '@fortawesome/fontawesome-free/css/fontawesome.css';
import "@fontsource/archivo";

// start the Stimulus application
// TODO: This is confusing and has fuck all to do with actual Bootstrap so I
// should probably remove it.
// import './bootstrap';

const $ = require('jquery');
require('bootstrap');

// We don't need this in the front-end.
// require('@fortawesome/fontawesome-free/js/fontawesome.js');

// This would pull in all the fontawesome icons *as svg*, which is an alternative
// to the CSS ones. I was mistakenly pulling it in *as well*.
// require('@fortawesome/fontawesome-free/js/all');

$(function() {
    if ($('#navigatePrev').length || $('#navigateNext').length)
    window.addEventListener("keydown", function (event) {
        if (event.defaultPrevented) {
            return; // Do nothing if the event was already processed
        }
        var url;
        switch (event.key) {
            case "Left": // IE/Edge specific value
            case "ArrowLeft":
                url = $("#navigatePrev").attr('href');
                if (url) {
                    window.location.href = url;
                }
            break;
            case "Right": // IE/Edge specific value
            case "ArrowRight":
                url = $("#navigateNext").attr('href');
            break;
            default:
            return; // Quit when this doesn't handle the key event.
        }
        if (url) {
            window.location.href = url;
        }
        // Cancel the default action to avoid it being handled twice
        event.preventDefault();
        }, true);
});
