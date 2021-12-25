// Used on both admin and public wander show pages
import { setUpMap, addWander } from './map';
import './gallery';

const $ = require('jquery');

$(() => {
  const map = setUpMap({
    scrollWheelZoom: false,
  });
  addWander(map, $('#mapid').data('wanderId'), true);
});
