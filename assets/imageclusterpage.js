import { setUpMap, addPhotos } from './map.js';

const $ = require('jquery');

$(() => {
  const map = setUpMap({
    scrollWheelZoom: false,
  });

  map.fireEvent('dataloading');
  $.getJSON('/api/images', (images) => {
    const photos = [];
    for (const image of images) {
      photos.push({
        lat: image.latlng[0],
        lng: image.latlng[1],
        url: image.mediumImageUri,
        caption: image.title || '',
        thumbnail: image.markerImageUri,
        imageShowUri: image.imageShowUri,
        // imageEntityAdminUri: image.imageEntityAdminUri,
        // TODO?
        video: null,
      });
    }
    addPhotos(map, photos);
  })
    .always(() => {
      map.fireEvent('dataload');
    });
});
