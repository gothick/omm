const $ = require('jquery');

import { setUpMap } from './map.js';
import { addAllWanders } from './map.js';

$(function() {
    var map = setUpMap({});
    addAllWanders(map);
});
