/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

import "./fontawesome";

import "@fontsource/archivo";

// start the Stimulus application
import './bootstrap';

const $ = require('jquery');
require('bootstrap');

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
