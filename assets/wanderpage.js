// Used on both admin and public wander show pages
const $ = require('jquery');

import { setUpMap } from './map.js';
import { addWander } from './map.js';

const Masonry = require('masonry-layout');


$(function() {
    var map = setUpMap({
        scrollWheelZoom: false
    });
    addWander(map, $('#mapid').data('wanderId'), true);

    var msnry = new Masonry( '.gallery', {
        itemSelector: '.grid-item'
    });
});

