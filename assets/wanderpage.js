// Used on both admin and public wander show pages
const $ = require('jquery');

import { setUpMap } from './map.js';
import { addWander } from './map.js';

const Masonry = require('masonry-layout');
const imagesLoaded = require('imagesloaded');


$(function() {
    var map = setUpMap({
        scrollWheelZoom: false
    });
    addWander(map, $('#mapid').data('wanderId'), true);

    var msnry = new Masonry( '.gallery', {
        itemSelector: '.grid-item',
        gutter: 12
    });

    // layout Masonry after each image loads, and also keep it
    // as well-hidden as possible until then by hiding its
    // metadata div
    $('.grid-item .metadata').hide();
    imagesLoaded(document.querySelector('.gallery'))
        .on('progress', function(instance, image) {
            $(image.img).closest('.grid-item').find('.metadata').show();
            msnry.layout();
        });
});

