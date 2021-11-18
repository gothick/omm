const $ = require('jquery');

import { setUpMap } from './map.js';
import { addPhotos } from './map.js';

$(function() {
    var map = setUpMap({
        scrollWheelZoom: false
    });

    map.fireEvent('dataloading');
    $.getJSON("/api/images?exists[latlng]=true", function(data) {
        var photos = [];
        $.each(data['hydra:member'], function(key, image) {
            photos.push({
                lat: image.latlng[0],
                lng: image.latlng[1],
                url: image.mediumImageUri,
                caption: image.title || '',
                thumbnail: image.markerImageUri,
                imageShowUri: image.imageShowUri,
                imageEntityAdminUri: image.imageEntityAdminUri,
                // TODO?
                video: null
            });
        });
        addPhotos(map, photos);
    })
    .always(function() {
        map.fireEvent("dataload");
    });

});