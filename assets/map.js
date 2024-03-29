// TODO: Refactor the heck out of this mess

/* LeafletJS */
/** global: L */
/* global L */ /* For ESLint */

import 'leaflet/dist/leaflet.css';
import 'leaflet-loading/src/Control.Loading.css';
import 'leaflet.locatecontrol/dist/L.Control.Locate.css';
import './styles/Leaflet.Photo.css';

const $ = require('jquery');
const escape = require('escape-html');

require('leaflet');
require('leaflet.locatecontrol');
require('leaflet-loading');
require('leaflet.markercluster');
require('polyline-encoded'); // Adds support for Google polyline encoding https://www.npmjs.com/package/polyline-encoded
require('./Leaflet.Photo');

let streetMap;
let satelliteMap;

const base = L.latLng($('#mapid').data('homebaseLat'), $('#mapid').data('homebaseLng'));

export function setUpMap(options) {
  const mapboxAccessToken = $('#mapid').data('mapboxAccessToken');
  const locusRadius = 1609.34; // 1 mile

  streetMap = L.tileLayer(
    `https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}@2x?access_token=${mapboxAccessToken}`,
    {
      // These are mapbox-specific
      id: 'gothick/ckhb31na304g619t67r3gcngx',
      tileSize: 512,
      zoomOffset: -1,
      // More general
      maxZoom: 19,
      attribution: "Map data &copy; <a href='https://www.openstreetmap.org/'>OpenStreetMap</a> contributors, <a href='https://creativecommons.org/licenses/by-sa/2.0/'>CC-BY-SA</a>, Imagery © <a href='https://www.mapbox.com/'>Mapbox</a>",
    },
  );

  satelliteMap = L.tileLayer(
    `https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}@2x?access_token=${mapboxAccessToken}`,
    {
      // These are mapbox-specific
      id: 'gothick/ckhwgr59r0uai19o077hp87w4',
      tileSize: 512,
      zoomOffset: -1,
      // More general
      maxZoom: 19,
      attribution: "Map data &copy; <a href='https://www.openstreetmap.org/'>OpenStreetMap</a> contributors, <a href='https://creativecommons.org/licenses/by-sa/2.0/'>CC-BY-SA</a>, Imagery © <a href='https://www.mapbox.com/'>Mapbox</a>",
    },
  );

  const circle = L.circle(base, {
    color: 'green',
    fillColor: '#faa',
    fillOpacity: 0.15,
    radius: locusRadius,
    interactive: false,
  });

  // Because Object.assign isn't supported in older browsers
  // TODO You can go back to Object.assign now you've started using Babel in
  // Webpack. It'll translate for you.
  // https://stackoverflow.com/a/41455739/300836
  $.extend(options, {
    // Give a bit of wiggle room around the circle, but don"t let the user drift too far away
    maxBounds: base.toBounds(locusRadius * 5),
    layers: [streetMap, circle],
    loadingControl: true, // https://github.com/ebrelsford/Leaflet.loading
    tap: false, // https://github.com/domoritz/leaflet-locatecontrol#safari-does-not-work-with-leaflet-171
  });

  const map = L.map('mapid', options)
    .setView(base, 14);

  const baseMaps = {
    Satellite: satelliteMap,
    Streets: streetMap,
  };

  L.control.layers(baseMaps).addTo(map);
  L.control.scale().addTo(map);

  L.control.locate().addTo(map);

  return map;
}

function selectedWanderStyle() {
  return {
    weight: 5,
    color: '#FFA500',
  };
}

function unselectedWanderStyle() {
  return {
    weight: 4,
    color: '#3388FF',
  };
}

let currentlySelected = null;

let photoLayer = null;

export function addPhotos(map, photos) {
  if (photoLayer) {
    map.removeLayer(photoLayer);
  }

  photoLayer = L.photo.cluster().on('click', (evt) => {
    const { photo } = evt.layer;
    const template = `<a href='{imageShowUri}'><img src='{url}' width='300' /></a><p>${escape(photo.caption)}</p>`;
    // TODO: Video
    evt.layer.bindPopup(L.Util.template(template, photo), {
      className: 'leaflet-popup-photo',
      minWidth: 300,
    }).openPopup();
  });

  photoLayer.add(photos).addTo(map);
}

function addWanderImages(map, images) {
  const photos = [];
  images.filter((i) => i.latlng.length > 0).forEach((image) => {
    photos.push({
      lat: image.latlng[0],
      lng: image.latlng[1],
      url: image.mediumImageUri,
      caption: image.title || '',
      thumbnail: image.markerImageUri,
      imageShowUri: image.imageShowUri,
      // TODO?
      video: null,
    });
  });
  addPhotos(map, photos);
}

function addWanders(url, map) {
  map.fireEvent('dataloading'); // Triggers loading spinner

  $.getJSON(url, (data) => {
    $.each(data, (key, wander) => {
      const wanderLine = L.Polyline.fromEncoded(wander.googlePolyline, {
        wanderId: wander.id,
      });

      wanderLine.setStyle(unselectedWanderStyle());
      wanderLine.bindPopup((layer) => {
        currentlySelected.setStyle(unselectedWanderStyle());
        layer.setStyle(selectedWanderStyle());
        layer.bringToFront();
        currentlySelected = layer;
        $.getJSON(`/api/wanders/${layer.options.wanderId}`, (w) => {
          addWanderImages(map, w.images);
        });
        // Popup
        const template = `<a href='{contentUrl}'>${escape(wander.title)}</a>`;
        return L.Util.template(template, wander);
      });
      wanderLine.addTo(map);
      currentlySelected = wanderLine;
    });
  })
    .done(() => {
      currentlySelected.setStyle(selectedWanderStyle());
    })
    .always(() => {
      map.fireEvent('dataload');
    });
}

export function addAllWanders(map) {
  addWanders('/api/wanders', map);
}

export function addWander(map, wanderId, addImages) {
  $.getJSON(`/api/wanders/${wanderId}`, (wander) => {
    const wanderLine = L.Polyline.fromEncoded(wander.googlePolyline, {
      wanderId: wander.id,
    });
    wanderLine
      .bindPopup((/* layer */) => wander.title)
      .addTo(map);
    map.fitBounds(wanderLine.getBounds());
    // Based on the example at https://github.com/turban/Leaflet.Photo/blob/gh-pages/examples/picasa.html
    if (addImages) {
      addWanderImages(map, wander.images);
    }
  });
}
