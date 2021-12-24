import { setUpMap, addPhotos } from './map';

const $ = require('jquery');

$(() => {
  const map = setUpMap({
    scrollWheelZoom: false,
  });

  map.fireEvent('dataloading');
  $.getJSON('/api/images', (images) => {
    const photos = [];
    images.map((image) => {
      photos.push({
        lat: image.latlng[0],
        lng: image.latlng[1],
        url: image.mediumImageUri,
        caption: image.title || '',
        thumbnail: image.markerImageUri,
        imageShowUri: image.imageShowUri
      });
    });
    addPhotos(map, photos);
  })
    .always(() => {
      map.fireEvent('dataload');
    });
});
