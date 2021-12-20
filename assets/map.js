// TODO: Refactor the heck out of this mess

/* LeafletJS */
/** global: L */
/* global L */ /* For ESLint */

import 'leaflet/dist/leaflet.css';
import 'leaflet-loading/src/Control.Loading.css';
import 'leaflet.locatecontrol/dist/L.Control.Locate.css';
import './styles/Leaflet.Photo.css';

const $ = require('jquery');

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
    const template = "<a href='{imageShowUri}'><img src='{url}' width='300' /></a><p>{caption}</p>";
    // TODO: Video
    evt.layer.bindPopup(L.Util.template(template, photo), {
      className: 'leaflet-popup-photo',
      minWidth: 300,
    }).openPopup();
  });

  photoLayer.add(photos).addTo(map);
}

function addWanderImages(map, wanderId) {
  const photos = [];

  // Our API allows us to grab only those photos with co-ordinates set
  $.getJSON(`/api/wanders/${wanderId}/images?exists[latlng]=true`, (images) => {
    $.each(images['hydra:member'], (key, image) => {
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
  });
}

function addWanders(url, map) {
  map.fireEvent('dataloading'); // Triggers loading spinner
  // TODO: We should probably use some kind of Hydra client. This"ll do for now.
  $.getJSON(url, (data) => {
    const nextPage = (data['hydra:view'] || {})['hydra:next'];
    const isLastPage = (typeof nextPage) === 'undefined';
    const last = data['hydra:member'].length - 1;
    $.each(data['hydra:member'], (key, wander) => {
      const isLastWander = isLastPage && (last === key);
      const wanderLine = L.Polyline.fromEncoded(wander.googlePolyline, {
        wanderId: wander.id,
      });

      wanderLine.setStyle(isLastWander ? selectedWanderStyle() : unselectedWanderStyle());
      wanderLine.bindPopup((layer) => {
        currentlySelected.setStyle(unselectedWanderStyle());
        layer.setStyle(selectedWanderStyle());
        layer.bringToFront();
        currentlySelected = layer;
        addWanderImages(map, layer.options.wanderId);
        // Popup
        const template = `<a href='${_routeWandersShow}{id}'>{title}</a>`;
        return L.Util.template(template, wander);
      });
      if (isLastWander) {
        currentlySelected = wanderLine;
      }
      wanderLine.addTo(map);
    });

    // Recurse through all the pages in the pagination we got back.
    map.fireEvent('dataload');
    if (!isLastPage) {
      addWanders(nextPage, map);
    }
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
      addWanderImages(map, wanderId);
    }
  });
}
