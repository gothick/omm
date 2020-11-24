var streetMap, satelliteMap;

$(function() {
    var mapbox_access_token = $('#mapid').data('mapboxAccessToken');
    var locus_radius = 1609.34; // 1 mile
    var base = L.latLng(51.4511364, -2.6219148);
    
    streetMap = L.tileLayer(
        'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}@2x?access_token=' + mapbox_access_token,
        {
            // These are mapbox-specific
            id: 'gothick/ckhb31na304g619t67r3gcngx',
            tileSize: 512,
            zoomOffset: -1,
            // More general
            maxZoom: 19,
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>'
        });
    
    satelliteMap = L.tileLayer(
        'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}@2x?access_token=' + mapbox_access_token,
        {
            // These are mapbox-specific
            id: 'gothick/ckhwgr59r0uai19o077hp87w4',
            tileSize: 512,
            zoomOffset: -1,
            // More general
            maxZoom: 19,
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>'
        });

    var circle = L.circle(base, {
        color: 'green',
        fillColor: '#faa',
        fillOpacity: 0.15,
        radius: locus_radius,
        interactive: false
    });
    
    var map = L.map('mapid', {
        maxBounds: base.toBounds(locus_radius * 5), // Give a bit of wiggle room around the circle, but don't let the user drift too far away
        layers: [streetMap, circle]
    }).setView(base, 14);
    
    var baseMaps = {
        "Satellite": satelliteMap,
        "Streets": streetMap
    };

    L.control.layers(baseMaps).addTo(map);
    L.control.scale().addTo(map);

    L.control.locate().addTo(map);

    // Custom layer visually to set the most recent track
    var customLayer = L.geoJson(null, {
        // http://leafletjs.com/reference.html#geojson-style
        style: function(feature) {
        return { color: '#FFA500' };
        }
    });

    $.getJSON("/api/wanders", function(data) {
        var last = data['hydra:totalItems'];
        $.each(data['hydra:member'], function(key, wander) {
            var track = omnivore.gpx(wander.gpxFilename, null, last - 1 == key ? customLayer : null)
                .bindPopup(function(layer) {
                    return wander.title;
                })
                .addTo(map);
            if (last - 1 == key) {
                track.bringToFront();
            }
        });
    });

    // https://gis.stackexchange.com/a/124288/967
    var marker = L.marker(base, {
        draggable:true,
    });
    marker.bindPopup();

    var wgs84 = new GT_WGS84();

    marker.on('dragend', function(event){
        //alert('drag ended');
        var marker = event.target;
        var location = marker.getLatLng();
        wgs84.setDegrees(location.lat, location.lng);
        var popup = marker.getPopup();
        var gridref = wgs84.getOSGB().getGridRef(5);
        popup.setContent(
            location.lat.toFixed(5) + ", " + 
            location.lng.toFixed(5) + 
            "<br>" +
            gridref)
        marker.openPopup();
        });

    marker.addTo(map);
});